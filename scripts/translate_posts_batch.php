<?php
/**
 * Blog Post Translator — OpenAI BATCH API (50% cheaper)
 *
 * Translates all published English blog posts to Spanish.
 * Uses gpt-4o-mini for cost efficiency (~$0.80 for ~424 posts).
 *
 * Usage:
 *   php scripts/translate_posts_batch.php prepare [--limit=424]
 *   php scripts/translate_posts_batch.php status
 *   php scripts/translate_posts_batch.php insert
 *   php scripts/translate_posts_batch.php run [--limit=424]
 *
 * Workflow:
 *   1. prepare  → creates JSONL with translation requests, uploads, submits batch
 *   2. status   → poll until batch completes
 *   3. insert   → downloads results, inserts translations into DB
 *   Or: run     → does all steps automatically (waits for completion)
 */

require __DIR__ . '/../lib/database.php';
require __DIR__ . '/../lib/blog.php';

$config = require __DIR__ . '/../data/config.php';

$apiKey = $config['openai']['api_key'] ?? '';
$model  = 'gpt-4o-mini'; // Much cheaper, good enough for translation

if (empty($apiKey) || $apiKey === 'sk-proj-REPLACE_ME') {
    echo "[ERROR] OpenAI API key not configured\n";
    exit(1);
}

$stateFile = __DIR__ . '/../storage/batch_translate_state.json';
$storageDir = __DIR__ . '/../storage';

// ─── Helper functions ────────────────────────────────────

function openai_request_translate(string $apiKey, string $method, string $endpoint, $body = null, string $contentType = 'application/json'): array
{
    $ch = curl_init("https://api.openai.com/v1{$endpoint}");
    $headers = ["Authorization: Bearer {$apiKey}"];

    if ($method === 'POST' && $body !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        if (is_array($body) && $contentType === 'multipart/form-data') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, is_string($body) ? $body : json_encode($body));
            $headers[] = "Content-Type: {$contentType}";
        }
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 300,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['code' => $httpCode, 'body' => json_decode($response, true) ?? $response];
}

function openai_get(string $apiKey, string $url): string
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ["Authorization: Bearer {$apiKey}"],
        CURLOPT_TIMEOUT => 300,
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

function load_state(string $file): array
{
    if (file_exists($file)) {
        return json_decode(file_get_contents($file), true) ?? [];
    }
    return [];
}

function save_state(string $file, array $state): void
{
    file_put_contents($file, json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

// ─── Translation prompt ──────────────────────────────────

$systemPrompt = <<<'SYSTEM'
You are a professional translator specializing in technology content. Translate the given English blog post to Spanish (Spain/Latin America neutral).

## OUTPUT FORMAT
Return a JSON object with exactly these keys:
- "title": Translated title (maintain similar length)
- "slug": URL-friendly slug in Spanish, lowercase, hyphens only, 4-8 words (translate the concept, don't transliterate)
- "excerpt": Translated excerpt/summary
- "content_html": Full translated HTML content
- "meta_title": Translated SEO title (50-60 chars). Do NOT include "CDEM Solutions" — the site appends it automatically
- "meta_description": Translated SEO description (150-160 chars)
- "meta_keywords": Translated keywords (comma-separated)

## TRANSLATION RULES
1. Preserve ALL HTML tags, attributes, classes, IDs, and structure exactly as-is
2. Do NOT translate: URLs, CSS classes, HTML attributes, code snippets inside <code>/<pre>, brand names (CDEM Solutions, AWS, Google Cloud, Kubernetes, Docker, etc.)
3. Do NOT translate anchor href values or id attributes
4. Keep technical terms that are commonly used in English in the tech world (e.g., "cloud computing", "machine learning", "DevOps", "pipeline") — use the Spanish equivalent only when there's a well-established one
5. Maintain the same tone: professional, confident, educational
6. Adapt idioms and expressions naturally — don't translate literally
7. Use "usted" form (formal) but keep it natural and approachable
8. For the slug: translate the core concept to Spanish, use common search terms
SYSTEM;

// ─── COMMANDS ────────────────────────────────────────────

$command = $argv[1] ?? 'run';
$limit = 424;
foreach ($argv as $arg) {
    if (preg_match('/^--limit=(\d+)$/', $arg, $m)) {
        $limit = (int)$m[1];
    }
}

switch ($command) {

// ═════════════════════════════════════════════════════════
case 'prepare':
// ═════════════════════════════════════════════════════════
    echo "=== Preparing translation batch ===\n";

    $pdo = db();

    // Get published posts that don't have a Spanish translation yet
    $stmt = $pdo->prepare("SELECT p.id, p.slug, p.title, p.excerpt, p.content_html,
        p.meta_title, p.meta_description, p.meta_keywords
        FROM blog_posts p
        WHERE p.status = 'published'
        AND p.id NOT IN (SELECT post_id FROM blog_translations WHERE lang = 'es')
        ORDER BY p.published_at DESC
        LIMIT :limit");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($posts)) {
        echo "No posts to translate (all already have Spanish translations).\n";
        exit(0);
    }

    echo "Found " . count($posts) . " posts to translate.\n";

    // Build JSONL
    $jsonlFile = "{$storageDir}/batch_translate_requests.jsonl";
    $fh = fopen($jsonlFile, 'w');
    $postMap = [];

    foreach ($posts as $post) {
        $customId = "post-{$post['id']}";

        // Truncate very long content to stay within token limits
        $contentHtml = $post['content_html'];
        if (strlen($contentHtml) > 60000) {
            $contentHtml = substr($contentHtml, 0, 60000);
            // Close any unclosed tags roughly
            $contentHtml .= "\n<!-- truncated -->";
        }

        $userPrompt = "Translate the following blog post to Spanish:\n\n"
            . "Title: {$post['title']}\n"
            . "Slug: {$post['slug']}\n"
            . "Excerpt: {$post['excerpt']}\n"
            . "Meta Title: {$post['meta_title']}\n"
            . "Meta Description: {$post['meta_description']}\n"
            . "Meta Keywords: {$post['meta_keywords']}\n\n"
            . "HTML Content:\n{$contentHtml}";

        $line = json_encode([
            'custom_id' => $customId,
            'method' => 'POST',
            'url' => '/v1/chat/completions',
            'body' => [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
                'max_tokens' => 16000,
                'temperature' => 0.3,
                'response_format' => ['type' => 'json_object'],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        fwrite($fh, $line . "\n");

        $postMap[$customId] = [
            'post_id' => $post['id'],
            'en_slug' => $post['slug'],
            'en_title' => $post['title'],
        ];
    }
    fclose($fh);
    echo "JSONL written: {$jsonlFile} (" . count($postMap) . " requests)\n";

    // Upload file
    echo "Uploading JSONL to OpenAI...\n";
    $uploadResult = openai_request_translate($apiKey, 'POST', '/files', [
        'purpose' => 'batch',
        'file' => new CURLFile($jsonlFile, 'application/jsonl', 'batch_translate_requests.jsonl'),
    ], 'multipart/form-data');

    if ($uploadResult['code'] !== 200 || !isset($uploadResult['body']['id'])) {
        echo "[ERROR] File upload failed: " . json_encode($uploadResult['body']) . "\n";
        exit(1);
    }

    $fileId = $uploadResult['body']['id'];
    echo "File uploaded: {$fileId}\n";

    // Create batch
    echo "Creating batch...\n";
    $batchResult = openai_request_translate($apiKey, 'POST', '/batches', [
        'input_file_id' => $fileId,
        'endpoint' => '/v1/chat/completions',
        'completion_window' => '24h',
        'metadata' => ['description' => 'CDEM blog Spanish translations'],
    ]);

    if ($batchResult['code'] !== 200 || !isset($batchResult['body']['id'])) {
        echo "[ERROR] Batch creation failed: " . json_encode($batchResult['body']) . "\n";
        exit(1);
    }

    $batchId = $batchResult['body']['id'];
    echo "Batch created: {$batchId}\n";
    echo "Status: {$batchResult['body']['status']}\n";

    save_state($stateFile, [
        'batch_id' => $batchId,
        'file_id' => $fileId,
        'post_map' => $postMap,
        'created_at' => date('Y-m-d H:i:s'),
    ]);

    echo "\nBatch submitted! Run 'php scripts/translate_posts_batch.php status' to check progress.\n";
    break;

// ═════════════════════════════════════════════════════════
case 'status':
// ═════════════════════════════════════════════════════════
    $state = load_state($stateFile);
    if (empty($state['batch_id'])) {
        echo "No active batch. Run 'prepare' first.\n";
        exit(1);
    }

    $response = openai_get($apiKey, "https://api.openai.com/v1/batches/{$state['batch_id']}");
    $batch = json_decode($response, true);

    echo "Batch ID: {$state['batch_id']}\n";
    echo "Status: {$batch['status']}\n";

    $counts = $batch['request_counts'] ?? [];
    echo "Completed: " . ($counts['completed'] ?? 0) . " / " . ($counts['total'] ?? '?') . "\n";
    echo "Failed: " . ($counts['failed'] ?? 0) . "\n";

    if ($batch['status'] === 'completed') {
        echo "\nBatch complete! Run 'php scripts/translate_posts_batch.php insert' to save translations.\n";
    }
    break;

// ═════════════════════════════════════════════════════════
case 'insert':
// ═════════════════════════════════════════════════════════
    echo "=== Inserting translations into database ===\n";

    $state = load_state($stateFile);
    if (empty($state['batch_id'])) {
        echo "No batch to process. Run 'prepare' first.\n";
        exit(1);
    }

    // Get batch info
    $response = openai_get($apiKey, "https://api.openai.com/v1/batches/{$state['batch_id']}");
    $batch = json_decode($response, true);

    if ($batch['status'] !== 'completed') {
        echo "Batch not complete yet. Status: {$batch['status']}\n";
        exit(1);
    }

    $outputFileId = $batch['output_file_id'];
    echo "Downloading results from {$outputFileId}...\n";

    $outputContent = openai_get($apiKey, "https://api.openai.com/v1/files/{$outputFileId}/content");

    $outputFile = "{$storageDir}/batch_translate_results.jsonl";
    file_put_contents($outputFile, $outputContent);

    $lines = explode("\n", trim($outputContent));
    echo "Got " . count($lines) . " results.\n\n";

    $postMap = $state['post_map'] ?? [];
    $inserted = 0;
    $errors = 0;

    foreach ($lines as $line) {
        $entry = json_decode($line, true);
        if (!$entry) continue;

        $customId = $entry['custom_id'];
        $postData = $postMap[$customId] ?? null;

        if (!$postData) {
            $errors++;
            echo "  [SKIP] {$customId}: not in post map\n";
            continue;
        }

        $content = $entry['response']['body']['choices'][0]['message']['content'] ?? null;
        if (!$content) {
            $errors++;
            echo "  [FAIL] {$customId}: no content in response\n";
            continue;
        }

        $translation = json_decode($content, true);
        if (!$translation || !isset($translation['title'])) {
            $errors++;
            echo "  [SKIP] {$customId}: invalid JSON — " . substr($content, 0, 100) . "\n";
            continue;
        }

        // Clean slug
        $slug = $translation['slug'] ?? blog_slugify($translation['title']);
        $slug = preg_replace('/[^a-z0-9-]/', '', strtolower($slug));
        $slug = preg_replace('/-+/', '-', trim($slug, '-'));

        $readingTime = !empty($translation['content_html'])
            ? blog_reading_time($translation['content_html'])
            : 5;

        $data = [
            'title' => $translation['title'],
            'slug' => $slug,
            'excerpt' => $translation['excerpt'] ?? '',
            'content_html' => $translation['content_html'] ?? '',
            'meta_title' => $translation['meta_title'] ?? '',
            'meta_description' => $translation['meta_description'] ?? '',
            'meta_keywords' => $translation['meta_keywords'] ?? '',
            'reading_time' => $readingTime,
        ];

        blog_translation_save($postData['post_id'], 'es', $data);

        $inserted++;
        if ($inserted % 50 === 0) {
            echo "  Inserted {$inserted} translations...\n";
        }
    }

    echo "\n===================================================\n";
    echo "  DONE! Inserted: {$inserted} | Errors: {$errors}\n";
    echo "===================================================\n";

    $pdo = db();
    $totalTranslations = (int)$pdo->query("SELECT COUNT(*) FROM blog_translations WHERE lang = 'es'")->fetchColumn();
    echo "Total Spanish translations: {$totalTranslations}\n";

    // Cleanup state
    if (file_exists($stateFile)) unlink($stateFile);
    echo "State cleaned up.\n";
    break;

// ═════════════════════════════════════════════════════════
case 'run':
// ═════════════════════════════════════════════════════════
    echo "=== Full automated translation batch run ===\n\n";

    // Step 1: Prepare
    echo "---- STEP 1: Prepare & submit batch ----\n";
    passthru("php " . __FILE__ . " prepare --limit={$limit}");

    // Step 2: Poll
    echo "\n---- STEP 2: Waiting for batch completion ----\n";
    $maxWait = 7200; // 2 hours
    $waited = 0;
    $pollInterval = 30;

    while ($waited < $maxWait) {
        sleep($pollInterval);
        $waited += $pollInterval;

        $state = load_state($stateFile);
        if (empty($state['batch_id'])) {
            echo "  [ERROR] No batch ID in state\n";
            exit(1);
        }

        $response = openai_get($apiKey, "https://api.openai.com/v1/batches/{$state['batch_id']}");
        $batch = json_decode($response, true);

        $counts = $batch['request_counts'] ?? [];
        $completed = $counts['completed'] ?? 0;
        $total = $counts['total'] ?? '?';
        $status = $batch['status'];

        $mins = round($waited / 60, 1);
        echo "  [{$mins}m] Status: {$status} | Progress: {$completed}/{$total}\n";

        if ($status === 'completed') {
            echo "  Batch complete!\n\n";
            break;
        }
        if (in_array($status, ['failed', 'expired', 'cancelled'])) {
            echo "  [ERROR] Batch {$status}!\n";
            exit(1);
        }

        if ($waited > 300) $pollInterval = 60;
    }

    // Step 3: Insert
    echo "---- STEP 3: Inserting translations ----\n";
    passthru("php " . __FILE__ . " insert");

    echo "\nAll done!\n";
    break;

default:
    echo "Usage: php scripts/translate_posts_batch.php {prepare|status|insert|run} [--limit=424]\n";
    echo "\nCommands:\n";
    echo "  prepare  - Create JSONL, upload, submit batch to OpenAI\n";
    echo "  status   - Check batch progress\n";
    echo "  insert   - Download results and insert translations into DB\n";
    echo "  run      - Do all steps automatically (waits for completion)\n";
    echo "\nOptions:\n";
    echo "  --limit=N  - Limit number of posts to translate (default: 424)\n";
    break;
}
