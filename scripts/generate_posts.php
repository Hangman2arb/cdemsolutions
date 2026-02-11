<?php
/**
 * Blog Post Generator — uses OpenAI to create SEO-optimized posts
 * from blog_ideas topics. Two-pass approach:
 *   Pass 1: Generate full HTML article (long-form, no JSON constraints)
 *   Pass 2: Generate metadata (title, slug, excerpt, meta, tags) as JSON
 *
 * Usage:
 *   php scripts/generate_posts.php [--limit=10] [--dry-run] [--service=AI]
 */

require __DIR__ . '/../lib/database.php';
require __DIR__ . '/../lib/blog.php';

$config = require __DIR__ . '/../data/config.php';

// --- CLI arguments ---
$options = getopt('', ['limit:', 'dry-run', 'service:']);
$limit   = (int)($options['limit'] ?? 10);
$dryRun  = isset($options['dry-run']);
$filterService = $options['service'] ?? null;

// --- OpenAI config ---
$apiKey = $config['openai']['api_key'] ?? '';
$model  = $config['openai']['model'] ?? 'gpt-4o';

if (!$dryRun && (empty($apiKey) || $apiKey === 'sk-proj-REPLACE_ME')) {
    echo "[ERROR] OpenAI API key not configured in data/config.php\n";
    exit(1);
}

// --- Featured images by service ---
$serviceImages = [
    'AI & ML'            => '/img/service-ai.jpg',
    'Custom Software'    => '/img/service-software.jpg',
    'Cloud & DevOps'     => '/img/service-cloud.jpg',
    'Digital Consulting' => '/img/service-consulting.jpg',
    'Cybersecurity'      => '/img/service-security.jpg',
    'Data & Analytics'   => '/img/service-data.jpg',
];

// --- Service to URL path mapping ---
$servicePages = [
    'AI & ML'            => '/services/#ai-ml',
    'Custom Software'    => '/services/#custom-software',
    'Cloud & DevOps'     => '/services/#cloud-devops',
    'Digital Consulting' => '/services/#digital-consulting',
    'Cybersecurity'      => '/services/#cybersecurity',
    'Data & Analytics'   => '/services/#data-analytics',
];

// --- Backdating: 2 posts/week (Mon+Thu) from 2022-01-10 to 2026-02-01 ---
function generate_publish_dates(int $count): array
{
    $start = new DateTime('2022-01-10');
    $end   = new DateTime('2026-02-01');
    $dates = [];

    $current = clone $start;
    while ($current <= $end) {
        $dow = (int)$current->format('N');
        if ($dow === 1 || $dow === 4) {
            $dates[] = clone $current;
        }
        $current->modify('+1 day');
    }

    $result = [];
    for ($i = 0; $i < $count; $i++) {
        $idx = $i % count($dates);
        $dt = clone $dates[$idx];
        $dt->setTime(rand(8, 18), rand(0, 59), rand(0, 59));
        $result[] = $dt->format('Y-m-d H:i:s');
    }

    return $result;
}

// --- Raw OpenAI API call (returns string or null) ---
function openai_call(string $apiKey, string $model, array $messages, bool $jsonMode = false): ?string
{
    $payload = [
        'model'             => $model,
        'messages'          => $messages,
        'temperature'       => 0.75,
        'max_tokens'        => 16000,
        'top_p'             => 0.95,
        'frequency_penalty' => 0.15,
        'presence_penalty'  => 0.1,
    ];

    if ($jsonMode) {
        $payload['response_format'] = ['type' => 'json_object'];
    }

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ],
        CURLOPT_TIMEOUT => 300,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        echo "    [CURL ERROR] {$curlError}\n";
        return null;
    }
    if ($httpCode === 429) {
        echo "    [RATE LIMITED] Waiting 30s...\n";
        sleep(30);
        return null;
    }
    if ($httpCode !== 200) {
        echo "    [API ERROR] HTTP {$httpCode}: " . substr($response, 0, 300) . "\n";
        return null;
    }

    $data = json_decode($response, true);
    if (!$data || !isset($data['choices'][0]['message']['content'])) {
        echo "    [PARSE ERROR] Unexpected response\n";
        return null;
    }

    $usage = $data['usage'] ?? [];
    if ($usage) {
        echo "    Tokens: {$usage['total_tokens']} (in:{$usage['prompt_tokens']}/out:{$usage['completion_tokens']})\n";
    }

    return $data['choices'][0]['message']['content'];
}

// --- Clean generated HTML ---
function clean_html(string $html): string
{
    // Strip <h1> tags
    $html = preg_replace('/<h1[^>]*>.*?<\/h1>/si', '', $html);
    // Strip wrapper <div class="prose"> (template already provides it)
    $html = preg_replace('/<div[^>]*class=["\']prose["\'][^>]*>/si', '', $html);
    // Remove markdown code fences if the model wrapped HTML in them
    $html = preg_replace('/^```html?\s*/i', '', $html);
    $html = preg_replace('/\s*```\s*$/', '', $html);
    // Remove orphaned closing </div> before blog-cta
    $html = preg_replace('/<\/div>\s*(<div class=["\']blog-cta)/si', '$1', $html);
    // Remove AI repetitive text artifacts (word repeated 4+ times)
    $html = preg_replace('/(\b\w+\b)(\s+\1){3,}/i', '$1', $html);
    return trim($html);
}

// =============================================================
// PROMPTS
// =============================================================

// PASS 1: Content generation (pure HTML, no JSON)
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
- Use question-style H3 headings for featured snippets (e.g., "What Is Zero Trust Architecture?").

## VOICE
Confident, knowledgeable, educational, practical. Written by practitioners who build real solutions — not a content mill.

## DATE AWARENESS
NEVER reference events, tools, or versions released AFTER the publication date provided.
SYSTEM;

// PASS 2: Metadata extraction (light JSON call)
$metaSystemPrompt = <<<'SYSTEM'
You are an SEO specialist. Given an HTML blog article and its topic context, extract/create the metadata.
Return a JSON object with exactly these keys:
- "title": SEO-optimized title, 50-65 characters, contains the primary keyword
- "slug": url-friendly slug, lowercase, hyphens, 4-8 words, includes primary keyword
- "excerpt": Engaging summary for blog listing cards, 130-155 characters
- "meta_title": SEO title tag, 50-60 chars, primary keyword near start. Do NOT include "CDEM Solutions" — the site appends " — CDEM Solutions" automatically
- "meta_description": Action-oriented description, 150-160 chars, includes primary keyword
- "meta_keywords": 5-8 comma-separated keywords/phrases
- "tags": array of 3-5 Title Case tags (1-3 words each). Reuse common tags: "AI", "Machine Learning", "DevOps", "Cloud", "AWS", "Security", "Web Development", "Data Engineering", "Digital Strategy", "Software Architecture", "Python", "React", "Agile", "Kubernetes", "Cybersecurity"
SYSTEM;

// =============================================================
// MAIN
// =============================================================

$pdo = db();

echo "=== CDEM Blog Post Generator (Two-Pass) ===\n";
echo "Mode: " . ($dryRun ? 'DRY RUN' : 'LIVE') . " | Model: {$model}\n";
echo "Limit: {$limit}\n";
if ($filterService) echo "Service filter: {$filterService}\n";
echo "\n";

// Fetch pending ideas
$query = "SELECT * FROM blog_ideas WHERE status = 'pending'";
$params = [];
if ($filterService) {
    $query .= " AND target_service LIKE :service";
    $params[':service'] = '%' . $filterService . '%';
}
$query .= " ORDER BY RANDOM() LIMIT :limit";

$stmt = $pdo->prepare($query);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->execute();
$ideas = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($ideas)) {
    echo "No pending ideas found.\n";
    exit(0);
}

echo "Found " . count($ideas) . " ideas to process.\n\n";

$existingCount = (int)$pdo->query("SELECT COUNT(*) FROM blog_posts WHERE status = 'published'")->fetchColumn();
$publishDates = generate_publish_dates($existingCount + count($ideas));

$generated = 0;
$failed = 0;

foreach ($ideas as $i => $idea) {
    $num = $i + 1;
    $dateIndex = $existingCount + $i;
    $publishDate = $publishDates[$dateIndex] ?? date('Y-m-d H:i:s');
    $dateContext = date('F Y', strtotime($publishDate));
    $serviceUrl = $servicePages[$idea['target_service']] ?? '/services/';

    echo "═══════════════════════════════════════════════\n";
    echo "[{$num}/" . count($ideas) . "] {$idea['source_title']}\n";
    echo "  Service: {$idea['target_service']} | Date: {$publishDate}\n";

    if ($dryRun) {
        echo "  [DRY RUN] Would generate post\n\n";
        $generated++;
        continue;
    }

    // ──────────────────────────────────────────────
    // PASS 1: Generate HTML content
    // ──────────────────────────────────────────────
    echo "  [Pass 1] Generating HTML content...\n";

    $contentUserPrompt = <<<PROMPT
Write a comprehensive, in-depth blog article inspired by this topic:
"{$idea['source_title']}"

Target service: {$idea['target_service']}
Publication date: {$dateContext} — do NOT reference anything after this date.
Service page URL: {$serviceUrl}

IMPORTANT REMINDERS:
- Write ONLY raw HTML. No markdown. No JSON wrapper. No code fences.
- You MUST write at least 2,000 words across 6 H2 sections.
- Each H2 section needs 2-3 H3 subsections with multiple paragraphs each.
- Include real examples, practical implementation advice, and technical depth.
- Start with <nav class="toc"> and end with <div class="blog-cta">.
- Do NOT use the source title as your title — create an original angle.
PROMPT;

    $htmlContent = null;
    for ($attempt = 1; $attempt <= 2; $attempt++) {
        if ($attempt > 1) {
            echo "    [RETRY] Attempt {$attempt}...\n";
            sleep(5);
        }
        $htmlContent = openai_call($apiKey, $model, [
            ['role' => 'system', 'content' => $contentSystemPrompt],
            ['role' => 'user', 'content' => $contentUserPrompt],
        ]);
        if ($htmlContent) break;
    }

    if (!$htmlContent) {
        echo "  [FAILED] Content generation failed\n\n";
        $failed++;
        sleep(3);
        continue;
    }

    $htmlContent = clean_html($htmlContent);
    $wordCount = str_word_count(strip_tags($htmlContent));
    echo "  [Pass 1] Done: {$wordCount} words, " . strlen($htmlContent) . " chars HTML\n";

    if ($wordCount < 500) {
        echo "  [SKIP] Content too short ({$wordCount} words)\n\n";
        $failed++;
        continue;
    }

    // ──────────────────────────────────────────────
    // PASS 2: Generate metadata (JSON)
    // ──────────────────────────────────────────────
    echo "  [Pass 2] Generating metadata...\n";

    // Send a trimmed version of content for metadata extraction (first 1500 chars)
    $contentPreview = substr(strip_tags($htmlContent), 0, 1500);

    $metaUserPrompt = <<<PROMPT
Here is the blog article topic and a preview of its content:

Topic inspiration: "{$idea['source_title']}"
CDEM Service: {$idea['target_service']}
Date context: {$dateContext}

Content preview (first 1500 chars of text):
{$contentPreview}

Generate the metadata JSON object for this article.
PROMPT;

    $metaRaw = openai_call($apiKey, 'gpt-4o-mini', [
        ['role' => 'system', 'content' => $metaSystemPrompt],
        ['role' => 'user', 'content' => $metaUserPrompt],
    ], true);

    if (!$metaRaw) {
        echo "  [FAILED] Metadata generation failed\n\n";
        $failed++;
        continue;
    }

    $meta = json_decode($metaRaw, true);
    if (!$meta || !isset($meta['title'])) {
        echo "  [FAILED] Invalid metadata JSON\n\n";
        $failed++;
        continue;
    }

    // ──────────────────────────────────────────────
    // INSERT POST
    // ──────────────────────────────────────────────
    $featuredImage = $serviceImages[$idea['target_service']] ?? '/img/service-consulting.jpg';
    $readingTime = blog_reading_time($htmlContent);

    // Clean slug
    $slug = $meta['slug'] ?? blog_slugify($meta['title']);
    $slug = preg_replace('/[^a-z0-9-]/', '', strtolower($slug));
    $slug = preg_replace('/-+/', '-', trim($slug, '-'));

    $postData = [
        'title'            => $meta['title'],
        'slug'             => $slug,
        'excerpt'          => $meta['excerpt'] ?? '',
        'content_html'     => $htmlContent,
        'featured_image'   => $featuredImage,
        'status'           => 'published',
        'meta_title'       => $meta['meta_title'] ?? '',
        'meta_description' => $meta['meta_description'] ?? '',
        'meta_keywords'    => $meta['meta_keywords'] ?? '',
        'reading_time'     => $readingTime,
        'author'           => 'CDEM Solutions',
        'published_at'     => $publishDate,
    ];

    $postId = blog_create($postData);

    $tags = $meta['tags'] ?? [];
    if (!empty($tags) && is_array($tags)) {
        blog_sync_tags($postId, $tags);
    }

    $pdo->prepare("UPDATE blog_ideas SET status = 'generated' WHERE id = :id")
        ->execute([':id' => $idea['id']]);

    echo "  [OK] Post #{$postId}: \"{$meta['title']}\"\n";
    echo "  Words: {$wordCount} | Reading: {$readingTime} min | Tags: " . implode(', ', $tags) . "\n\n";
    $generated++;

    // Rate limiting between posts
    if ($i < count($ideas) - 1) {
        $pause = rand(2, 4);
        echo "  (pausing {$pause}s)\n";
        sleep($pause);
    }
}

// Summary
echo "\n═══════════════════════════════════════════════\n";
echo "          GENERATION COMPLETE\n";
echo "═══════════════════════════════════════════════\n";
echo "Generated: {$generated} | Failed: {$failed}\n";

$totalPublished = (int)$pdo->query("SELECT COUNT(*) FROM blog_posts WHERE status = 'published'")->fetchColumn();
$totalPending = (int)$pdo->query("SELECT COUNT(*) FROM blog_ideas WHERE status = 'pending'")->fetchColumn();
echo "Total published: {$totalPublished} | Remaining ideas: {$totalPending}\n";

if ($totalPending > 0) {
    echo "\nRun again: php scripts/generate_posts.php --limit=50\n";
}
