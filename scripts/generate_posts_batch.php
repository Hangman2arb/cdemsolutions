<?php
/**
 * Blog Post Generator — OpenAI BATCH API (50% cheaper)
 *
 * Two-phase batch workflow:
 *   Phase 1: Submit all content generation requests as a batch
 *   Phase 2: Submit all metadata extraction requests as a batch
 *   Final:   Insert all posts into the database
 *
 * Usage:
 *   php scripts/generate_posts_batch.php prepare [--limit=424]
 *   php scripts/generate_posts_batch.php status
 *   php scripts/generate_posts_batch.php process
 *   php scripts/generate_posts_batch.php insert
 *
 * Full workflow:
 *   1. prepare  → creates JSONL, uploads, submits batch for content
 *   2. status   → poll until batch completes (or wait)
 *   3. process  → downloads content results, submits metadata batch
 *   4. status   → poll until metadata batch completes
 *   5. insert   → downloads metadata, inserts all posts into DB
 *
 * Or run everything at once:
 *   php scripts/generate_posts_batch.php run [--limit=424]
 */

require __DIR__ . '/../lib/database.php';
require __DIR__ . '/../lib/blog.php';

$config = require __DIR__ . '/../data/config.php';

$apiKey = $config['openai']['api_key'] ?? '';
$model  = $config['openai']['model'] ?? 'gpt-4o';

if (empty($apiKey) || $apiKey === 'sk-proj-REPLACE_ME') {
    echo "[ERROR] OpenAI API key not configured\n";
    exit(1);
}

$stateFile = __DIR__ . '/../storage/batch_state.json';
$storageDir = __DIR__ . '/../storage';

// --- Service mappings ---
$serviceImages = [
    'AI & ML'            => '/img/service-ai.jpg',
    'Custom Software'    => '/img/service-software.jpg',
    'Cloud & DevOps'     => '/img/service-cloud.jpg',
    'Digital Consulting' => '/img/service-consulting.jpg',
    'Cybersecurity'      => '/img/service-security.jpg',
    'Data & Analytics'   => '/img/service-data.jpg',
];
$servicePages = [
    'AI & ML'            => '/services/#ai-ml',
    'Custom Software'    => '/services/#custom-software',
    'Cloud & DevOps'     => '/services/#cloud-devops',
    'Digital Consulting' => '/services/#digital-consulting',
    'Cybersecurity'      => '/services/#cybersecurity',
    'Data & Analytics'   => '/services/#data-analytics',
];

// ─── Helper functions ────────────────────────────────────

function generate_publish_dates(int $count): array
{
    $start = new DateTime('2022-01-10');
    $end   = new DateTime('2026-02-01');
    $dates = [];
    $current = clone $start;
    while ($current <= $end) {
        if (in_array((int)$current->format('N'), [1, 4])) {
            $dates[] = clone $current;
        }
        $current->modify('+1 day');
    }
    $result = [];
    for ($i = 0; $i < $count; $i++) {
        $dt = clone $dates[$i % count($dates)];
        $dt->setTime(rand(8, 18), rand(0, 59), rand(0, 59));
        $result[] = $dt->format('Y-m-d H:i:s');
    }
    return $result;
}

function openai_request(string $apiKey, string $method, string $endpoint, $body = null, string $contentType = 'application/json'): array
{
    $ch = curl_init("https://api.openai.com/v1{$endpoint}");
    $headers = ["Authorization: Bearer {$apiKey}"];

    if ($method === 'POST' && $body !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        if (is_string($body) && $contentType === 'multipart/form-data') {
            // File upload — body is already set via CURLOPT_POSTFIELDS
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

    if (is_array($body) && $contentType === 'multipart/form-data') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['code' => $httpCode, 'body' => json_decode($response, true) ?? $response];
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

function clean_html(string $html): string
{
    $html = preg_replace('/<h1[^>]*>.*?<\/h1>/si', '', $html);
    $html = preg_replace('/<div[^>]*class=["\']prose["\'][^>]*>/si', '', $html);
    $html = preg_replace('/^```html?\s*/i', '', $html);
    $html = preg_replace('/\s*```\s*$/', '', $html);
    $html = preg_replace('/<\/div>\s*(<div class=["\']blog-cta)/si', '$1', $html);
    $html = preg_replace('/(\b\w+\b)(\s+\1){3,}/i', '$1', $html);
    return trim($html);
}

// ─── Prompts ─────────────────────────────────────────────

$contentSystemPrompt = <<<'SYSTEM'
You are a senior technology writer at CDEM Solutions (cdemsolutions.com), a technology consultancy specializing in AI & Machine Learning, Custom Software Development, Cloud & DevOps, Digital Consulting, Cybersecurity, and Data & Analytics. You write in-depth, long-form blog articles that rank on Google.

## OUTPUT FORMAT
Write ONLY raw HTML. No markdown. No JSON. No code fences. Just pure HTML content.

## REQUIRED STRUCTURE
1. Start with a `<nav class="toc">` containing a "Table of Contents" heading and an anchor-linked `<ul>` to each H2 section.
2. Write exactly **6 <h2> sections**, each with an `id` attribute matching the TOC anchors.
3. Under each H2, write **2-3 <h3> subsections**.
4. Under each H3, write **2-4 full paragraphs** (each paragraph 3-5 sentences).
5. End with a `<div class="blog-cta">` containing a CTA paragraph with a link to `/contact/`.
6. NEVER use `<h1>` tags.

## LENGTH REQUIREMENT — THIS IS CRITICAL
Your article MUST be at least **2,000 words**. Each of the 6 H2 sections should be **350-450 words**.
- Write detailed explanations with real-world examples.
- Include numbered lists for processes, bullet lists for features/benefits.
- Add `<blockquote>` for key insights or expert quotes.
- Use `<code>` or `<pre><code>` for any technical terms, commands, or code snippets.
- Bold (`<strong>`) key concepts on first mention (8-12 per article).
- Include 2-3 internal links: `/services/`, `/contact/`, `/blog/` with descriptive anchor text.

## HTML TAGS ALLOWED
`<nav class="toc">`, `<h2 id="...">`, `<h3>`, `<p>`, `<ul>`, `<ol>`, `<li>`, `<strong>`, `<em>`, `<a>`, `<blockquote>`, `<code>`, `<pre><code>`, `<div class="blog-cta">`

Do NOT use: `<h1>`, `<img>`, `<table>`, `<span>`, `<br>`, inline styles, or any wrapper divs.

## SEO RULES
- H2/H3 headings must contain keywords naturally.
- First paragraph must include the primary keyword within 100 words.
- Use question-style H3 headings for featured snippets.

## VOICE
Confident, knowledgeable, educational, practical. Written by practitioners who build real solutions — not a content mill.

## DATE AWARENESS
NEVER reference events, tools, or versions released AFTER the publication date provided.
SYSTEM;

$metaSystemPrompt = <<<'SYSTEM'
You are an SEO specialist. Given an HTML blog article and its topic context, extract/create the metadata.
Return a JSON object with exactly these keys:
- "title": SEO-optimized title, 50-65 characters, contains the primary keyword
- "slug": url-friendly slug, lowercase, hyphens, 4-8 words, includes primary keyword
- "excerpt": Engaging summary for blog listing cards, 130-155 characters
- "meta_title": SEO title tag, 50-60 chars, primary keyword near start. Do NOT include "CDEM Solutions" — the site appends it automatically
- "meta_description": Action-oriented description, 150-160 chars, includes primary keyword
- "meta_keywords": 5-8 comma-separated keywords/phrases
- "tags": array of 3-5 Title Case tags (1-3 words each). Reuse common tags: "AI", "Machine Learning", "DevOps", "Cloud", "AWS", "Security", "Web Development", "Data Engineering", "Digital Strategy", "Software Architecture", "Python", "React", "Agile", "Kubernetes", "Cybersecurity"
SYSTEM;

// ─── COMMANDS ────────────────────────────────────────────

$command = $argv[1] ?? 'run';
// Parse --limit from argv manually (getopt doesn't play well with subcommands)
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
    echo "=== Phase 1: Preparing content batch ===\n";

    $pdo = db();
    $stmt = $pdo->prepare("SELECT * FROM blog_ideas WHERE status = 'pending' ORDER BY RANDOM() LIMIT :limit");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $ideas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($ideas)) {
        echo "No pending ideas.\n";
        exit(0);
    }

    echo "Selected " . count($ideas) . " ideas.\n";

    // Generate dates
    $existingCount = (int)$pdo->query("SELECT COUNT(*) FROM blog_posts WHERE status = 'published'")->fetchColumn();
    $publishDates = generate_publish_dates($existingCount + count($ideas));

    // Build JSONL for content generation
    $jsonlFile = "{$storageDir}/batch_content_requests.jsonl";
    $fh = fopen($jsonlFile, 'w');
    $ideaMap = []; // custom_id => idea data + date

    foreach ($ideas as $i => $idea) {
        $dateIndex = $existingCount + $i;
        $publishDate = $publishDates[$dateIndex];
        $dateContext = date('F Y', strtotime($publishDate));
        $serviceUrl = $servicePages[$idea['target_service']] ?? '/services/';
        $customId = "idea-{$idea['id']}";

        $userPrompt = "Write a comprehensive, in-depth blog article inspired by this topic:\n\"{$idea['source_title']}\"\n\nTarget service: {$idea['target_service']}\nPublication date: {$dateContext} — do NOT reference anything after this date.\nService page URL: {$serviceUrl}\n\nIMPORTANT REMINDERS:\n- Write ONLY raw HTML. No markdown. No JSON wrapper. No code fences.\n- You MUST write at least 2,000 words across 6 H2 sections.\n- Each H2 section needs 2-3 H3 subsections with multiple paragraphs each.\n- Include real examples, practical implementation advice, and technical depth.\n- Start with <nav class=\"toc\"> and end with <div class=\"blog-cta\">.\n- Do NOT use the source title as your title — create an original angle.";

        $line = json_encode([
            'custom_id' => $customId,
            'method' => 'POST',
            'url' => '/v1/chat/completions',
            'body' => [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $contentSystemPrompt],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
                'max_tokens' => 16000,
                'temperature' => 0.75,
                'top_p' => 0.95,
                'frequency_penalty' => 0.15,
                'presence_penalty' => 0.1,
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        fwrite($fh, $line . "\n");

        $ideaMap[$customId] = [
            'idea_id' => $idea['id'],
            'source_title' => $idea['source_title'],
            'target_service' => $idea['target_service'],
            'publish_date' => $publishDate,
        ];
    }
    fclose($fh);
    echo "JSONL written: {$jsonlFile} (" . count($ideaMap) . " requests)\n";

    // Upload file
    echo "Uploading JSONL to OpenAI...\n";
    $uploadResult = openai_request($apiKey, 'POST', '/files', [
        'purpose' => 'batch',
        'file' => new CURLFile($jsonlFile, 'application/jsonl', 'batch_content_requests.jsonl'),
    ], 'multipart/form-data');

    if ($uploadResult['code'] !== 200 || !isset($uploadResult['body']['id'])) {
        echo "[ERROR] File upload failed: " . json_encode($uploadResult['body']) . "\n";
        exit(1);
    }

    $fileId = $uploadResult['body']['id'];
    echo "File uploaded: {$fileId}\n";

    // Create batch
    echo "Creating batch...\n";
    $batchResult = openai_request($apiKey, 'POST', '/batches', [
        'input_file_id' => $fileId,
        'endpoint' => '/v1/chat/completions',
        'completion_window' => '24h',
        'metadata' => ['description' => 'CDEM blog content generation'],
    ]);

    if ($batchResult['code'] !== 200 || !isset($batchResult['body']['id'])) {
        echo "[ERROR] Batch creation failed: " . json_encode($batchResult['body']) . "\n";
        exit(1);
    }

    $batchId = $batchResult['body']['id'];
    echo "Batch created: {$batchId}\n";
    echo "Status: {$batchResult['body']['status']}\n";

    // Save state
    save_state($stateFile, [
        'phase' => 'content',
        'batch_id' => $batchId,
        'file_id' => $fileId,
        'idea_map' => $ideaMap,
        'created_at' => date('Y-m-d H:i:s'),
    ]);

    echo "\nBatch submitted! Run 'php scripts/generate_posts_batch.php status' to check progress.\n";
    echo "Or run 'php scripts/generate_posts_batch.php run --limit={$limit}' to wait automatically.\n";
    break;

// ═════════════════════════════════════════════════════════
case 'status':
// ═════════════════════════════════════════════════════════
    $state = load_state($stateFile);
    if (empty($state['batch_id'])) {
        echo "No active batch. Run 'prepare' first.\n";
        exit(1);
    }

    $result = openai_request($apiKey, 'POST', "/batches/{$state['batch_id']}", null);
    // GET request workaround — openai_request needs adjustment
    $ch = curl_init("https://api.openai.com/v1/batches/{$state['batch_id']}");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ["Authorization: Bearer {$apiKey}"],
        CURLOPT_TIMEOUT => 30,
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    $batch = json_decode($response, true);

    echo "Phase: {$state['phase']}\n";
    echo "Batch ID: {$state['batch_id']}\n";
    echo "Status: {$batch['status']}\n";

    $counts = $batch['request_counts'] ?? [];
    echo "Completed: " . ($counts['completed'] ?? 0) . " / " . ($counts['total'] ?? '?') . "\n";
    echo "Failed: " . ($counts['failed'] ?? 0) . "\n";

    if ($batch['status'] === 'completed') {
        echo "\nBatch complete! Output file: {$batch['output_file_id']}\n";
        if ($state['phase'] === 'content') {
            echo "Run 'php scripts/generate_posts_batch.php process' to continue.\n";
        } else {
            echo "Run 'php scripts/generate_posts_batch.php insert' to finish.\n";
        }
    }
    break;

// ═════════════════════════════════════════════════════════
case 'process':
// ═════════════════════════════════════════════════════════
    echo "=== Phase 2: Processing content results & submitting metadata batch ===\n";

    $state = load_state($stateFile);
    if (empty($state['batch_id']) || $state['phase'] !== 'content') {
        echo "No content batch to process. Run 'prepare' first.\n";
        exit(1);
    }

    // Get batch info
    $ch = curl_init("https://api.openai.com/v1/batches/{$state['batch_id']}");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ["Authorization: Bearer {$apiKey}"],
    ]);
    $batch = json_decode(curl_exec($ch), true);
    curl_close($ch);

    if ($batch['status'] !== 'completed') {
        echo "Batch not complete yet. Status: {$batch['status']}\n";
        exit(1);
    }

    $outputFileId = $batch['output_file_id'];
    echo "Downloading results from {$outputFileId}...\n";

    // Download output file
    $ch = curl_init("https://api.openai.com/v1/files/{$outputFileId}/content");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ["Authorization: Bearer {$apiKey}"],
        CURLOPT_TIMEOUT => 300,
    ]);
    $outputContent = curl_exec($ch);
    curl_close($ch);

    $outputFile = "{$storageDir}/batch_content_results.jsonl";
    file_put_contents($outputFile, $outputContent);

    // Parse results
    $lines = explode("\n", trim($outputContent));
    echo "Got " . count($lines) . " results.\n";

    $contentResults = []; // custom_id => html content
    $succeeded = 0;
    $failed = 0;

    foreach ($lines as $line) {
        $entry = json_decode($line, true);
        if (!$entry) continue;

        $customId = $entry['custom_id'];
        $response = $entry['response']['body'] ?? null;

        if (!$response || !isset($response['choices'][0]['message']['content'])) {
            $failed++;
            echo "  [FAIL] {$customId}: no content in response\n";
            continue;
        }

        $html = clean_html($response['choices'][0]['message']['content']);
        $wordCount = str_word_count(strip_tags($html));

        if ($wordCount < 300) {
            $failed++;
            echo "  [SKIP] {$customId}: too short ({$wordCount} words)\n";
            continue;
        }

        $contentResults[$customId] = $html;
        $succeeded++;
    }

    echo "Content parsed: {$succeeded} succeeded, {$failed} failed.\n\n";

    // Build metadata JSONL
    echo "Building metadata batch...\n";
    $metaJsonlFile = "{$storageDir}/batch_meta_requests.jsonl";
    $fh = fopen($metaJsonlFile, 'w');
    $metaCount = 0;

    foreach ($contentResults as $customId => $html) {
        $ideaData = $state['idea_map'][$customId] ?? null;
        if (!$ideaData) continue;

        $contentPreview = substr(strip_tags($html), 0, 1500);
        $dateContext = date('F Y', strtotime($ideaData['publish_date']));

        $userPrompt = "Here is the blog article topic and a preview of its content:\n\nTopic inspiration: \"{$ideaData['source_title']}\"\nCDEM Service: {$ideaData['target_service']}\nDate context: {$dateContext}\n\nContent preview (first 1500 chars of text):\n{$contentPreview}\n\nGenerate the metadata JSON object for this article.";

        $line = json_encode([
            'custom_id' => $customId,
            'method' => 'POST',
            'url' => '/v1/chat/completions',
            'body' => [
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => $metaSystemPrompt],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
                'max_tokens' => 500,
                'temperature' => 0.5,
                'response_format' => ['type' => 'json_object'],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        fwrite($fh, $line . "\n");
        $metaCount++;
    }
    fclose($fh);
    echo "Metadata JSONL: {$metaCount} requests.\n";

    // Upload metadata JSONL
    echo "Uploading metadata JSONL...\n";
    $uploadResult = openai_request($apiKey, 'POST', '/files', [
        'purpose' => 'batch',
        'file' => new CURLFile($metaJsonlFile, 'application/jsonl', 'batch_meta_requests.jsonl'),
    ], 'multipart/form-data');

    if ($uploadResult['code'] !== 200 || !isset($uploadResult['body']['id'])) {
        echo "[ERROR] Upload failed: " . json_encode($uploadResult['body']) . "\n";
        exit(1);
    }

    $metaFileId = $uploadResult['body']['id'];
    echo "File uploaded: {$metaFileId}\n";

    // Create metadata batch
    $batchResult = openai_request($apiKey, 'POST', '/batches', [
        'input_file_id' => $metaFileId,
        'endpoint' => '/v1/chat/completions',
        'completion_window' => '24h',
        'metadata' => ['description' => 'CDEM blog metadata extraction'],
    ]);

    if ($batchResult['code'] !== 200 || !isset($batchResult['body']['id'])) {
        echo "[ERROR] Batch creation failed: " . json_encode($batchResult['body']) . "\n";
        exit(1);
    }

    $metaBatchId = $batchResult['body']['id'];
    echo "Metadata batch created: {$metaBatchId}\n";

    // Update state
    $state['phase'] = 'metadata';
    $state['batch_id'] = $metaBatchId;
    $state['meta_file_id'] = $metaFileId;
    $state['content_results'] = $contentResults;
    save_state($stateFile, $state);

    echo "\nMetadata batch submitted! Check with 'status', then run 'insert' when done.\n";
    break;

// ═════════════════════════════════════════════════════════
case 'insert':
// ═════════════════════════════════════════════════════════
    echo "=== Final: Inserting posts into database ===\n";

    $state = load_state($stateFile);
    if (empty($state['batch_id']) || $state['phase'] !== 'metadata') {
        echo "No metadata batch to process.\n";
        exit(1);
    }

    // Get batch info
    $ch = curl_init("https://api.openai.com/v1/batches/{$state['batch_id']}");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ["Authorization: Bearer {$apiKey}"],
    ]);
    $batch = json_decode(curl_exec($ch), true);
    curl_close($ch);

    if ($batch['status'] !== 'completed') {
        echo "Metadata batch not complete. Status: {$batch['status']}\n";
        exit(1);
    }

    // Download metadata results
    $outputFileId = $batch['output_file_id'];
    echo "Downloading metadata results...\n";

    $ch = curl_init("https://api.openai.com/v1/files/{$outputFileId}/content");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ["Authorization: Bearer {$apiKey}"],
        CURLOPT_TIMEOUT => 300,
    ]);
    $outputContent = curl_exec($ch);
    curl_close($ch);

    file_put_contents("{$storageDir}/batch_meta_results.jsonl", $outputContent);

    $lines = explode("\n", trim($outputContent));
    echo "Got " . count($lines) . " metadata results.\n";

    $pdo = db();
    $contentResults = $state['content_results'] ?? [];
    $ideaMap = $state['idea_map'] ?? [];
    $inserted = 0;
    $errors = 0;

    foreach ($lines as $line) {
        $entry = json_decode($line, true);
        if (!$entry) continue;

        $customId = $entry['custom_id'];
        $html = $contentResults[$customId] ?? null;
        $ideaData = $ideaMap[$customId] ?? null;

        if (!$html || !$ideaData) {
            $errors++;
            continue;
        }

        $metaContent = $entry['response']['body']['choices'][0]['message']['content'] ?? null;
        if (!$metaContent) {
            $errors++;
            continue;
        }

        $meta = json_decode($metaContent, true);
        if (!$meta || !isset($meta['title'])) {
            $errors++;
            echo "  [SKIP] {$customId}: invalid metadata JSON\n";
            continue;
        }

        // Clean slug
        $slug = $meta['slug'] ?? blog_slugify($meta['title']);
        $slug = preg_replace('/[^a-z0-9-]/', '', strtolower($slug));
        $slug = preg_replace('/-+/', '-', trim($slug, '-'));

        $featuredImage = $serviceImages[$ideaData['target_service']] ?? '/img/service-consulting.jpg';
        $readingTime = blog_reading_time($html);
        $wordCount = str_word_count(strip_tags($html));

        $postData = [
            'title'            => $meta['title'],
            'slug'             => $slug,
            'excerpt'          => $meta['excerpt'] ?? '',
            'content_html'     => $html,
            'featured_image'   => $featuredImage,
            'status'           => 'published',
            'meta_title'       => $meta['meta_title'] ?? '',
            'meta_description' => $meta['meta_description'] ?? '',
            'meta_keywords'    => $meta['meta_keywords'] ?? '',
            'reading_time'     => $readingTime,
            'author'           => 'CDEM Solutions',
            'published_at'     => $ideaData['publish_date'],
        ];

        $postId = blog_create($postData);

        $tags = $meta['tags'] ?? [];
        if (!empty($tags) && is_array($tags)) {
            blog_sync_tags($postId, $tags);
        }

        // Mark idea as generated
        $ideaId = $ideaData['idea_id'];
        $pdo->prepare("UPDATE blog_ideas SET status = 'generated' WHERE id = :id")
            ->execute([':id' => $ideaId]);

        $inserted++;
        if ($inserted % 50 === 0) {
            echo "  Inserted {$inserted} posts...\n";
        }
    }

    echo "\n═══════════════════════════════════════════════\n";
    echo "  DONE! Inserted: {$inserted} | Errors: {$errors}\n";
    echo "═══════════════════════════════════════════════\n";

    $totalPosts = (int)$pdo->query("SELECT COUNT(*) FROM blog_posts WHERE status = 'published'")->fetchColumn();
    $totalTags = (int)$pdo->query("SELECT COUNT(*) FROM blog_tags")->fetchColumn();
    echo "Total published posts: {$totalPosts}\n";
    echo "Total tags: {$totalTags}\n";

    // Cleanup state
    unlink($stateFile);
    echo "State cleaned up.\n";
    break;

// ═════════════════════════════════════════════════════════
case 'run':
// ═════════════════════════════════════════════════════════
    echo "=== Full automated batch run ===\n\n";

    // Phase 1: Prepare
    echo "──── STEP 1: Prepare & submit content batch ────\n";
    passthru("php " . __FILE__ . " prepare --limit={$limit}");

    // Phase 2: Poll content batch
    echo "\n──── STEP 2: Waiting for content batch ────\n";
    $maxWait = 7200; // 2 hours max
    $waited = 0;
    $pollInterval = 30;

    while ($waited < $maxWait) {
        sleep($pollInterval);
        $waited += $pollInterval;

        $state = load_state($stateFile);
        $ch = curl_init("https://api.openai.com/v1/batches/{$state['batch_id']}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ["Authorization: Bearer {$apiKey}"],
        ]);
        $batch = json_decode(curl_exec($ch), true);
        curl_close($ch);

        $counts = $batch['request_counts'] ?? [];
        $completed = $counts['completed'] ?? 0;
        $total = $counts['total'] ?? '?';
        $status = $batch['status'];

        $mins = round($waited / 60, 1);
        echo "  [{$mins}m] Status: {$status} | Progress: {$completed}/{$total}\n";

        if ($status === 'completed') {
            echo "  Content batch complete!\n\n";
            break;
        }
        if (in_array($status, ['failed', 'expired', 'cancelled'])) {
            echo "  [ERROR] Batch {$status}!\n";
            exit(1);
        }

        // Increase poll interval over time
        if ($waited > 300) $pollInterval = 60;
    }

    // Phase 3: Process content & submit metadata
    echo "──── STEP 3: Process content & submit metadata batch ────\n";
    passthru("php " . __FILE__ . " process");

    // Phase 4: Poll metadata batch
    echo "\n──── STEP 4: Waiting for metadata batch ────\n";
    $waited = 0;
    $pollInterval = 20;

    while ($waited < $maxWait) {
        sleep($pollInterval);
        $waited += $pollInterval;

        $state = load_state($stateFile);
        $ch = curl_init("https://api.openai.com/v1/batches/{$state['batch_id']}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ["Authorization: Bearer {$apiKey}"],
        ]);
        $batch = json_decode(curl_exec($ch), true);
        curl_close($ch);

        $counts = $batch['request_counts'] ?? [];
        $completed = $counts['completed'] ?? 0;
        $total = $counts['total'] ?? '?';
        $status = $batch['status'];

        $mins = round($waited / 60, 1);
        echo "  [{$mins}m] Status: {$status} | Progress: {$completed}/{$total}\n";

        if ($status === 'completed') {
            echo "  Metadata batch complete!\n\n";
            break;
        }
        if (in_array($status, ['failed', 'expired', 'cancelled'])) {
            echo "  [ERROR] Batch {$status}!\n";
            exit(1);
        }
    }

    // Phase 5: Insert all posts
    echo "──── STEP 5: Inserting all posts ────\n";
    passthru("php " . __FILE__ . " insert");

    echo "\n🎉 All done!\n";
    break;

default:
    echo "Usage: php scripts/generate_posts_batch.php {prepare|status|process|insert|run} [--limit=424]\n";
    break;
}
