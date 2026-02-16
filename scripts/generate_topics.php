<?php
/**
 * Blog Topic Generator for 2026 Content Pipeline
 *
 * Generates 91 unique blog topics for Feb 17 – Dec 31, 2026.
 * Scheduled on Tuesdays and Fridays (good B2B engagement).
 * Uses GPT-4o, one API call per category for reliability.
 *
 * Usage:
 *   php scripts/generate_topics.php              # Generate and save topics
 *   php scripts/generate_topics.php --dry-run     # Show prompt without calling API
 *   php scripts/generate_topics.php --dates-only  # Just generate the date schedule
 */

require __DIR__ . '/../lib/database.php';
require __DIR__ . '/../lib/blog.php';

$config = require __DIR__ . '/../data/config.php';

$apiKey = $config['openai']['api_key'] ?? '';
if (empty($apiKey) || $apiKey === 'sk-proj-REPLACE_ME') {
    echo "[ERROR] OpenAI API key not configured\n";
    exit(1);
}

$outputFile = __DIR__ . '/../storage/topics_2026.json';

// ─── Parse args ──────────────────────────────────────────

$dryRun    = in_array('--dry-run', $argv);
$datesOnly = in_array('--dates-only', $argv);

// ─── Generate scheduled dates (Tue & Fri, Feb 17 – Dec 31, 2026) ───

function generate_2026_schedule(): array
{
    $dates = [];
    $current = new DateTime('2026-02-17');
    $end = new DateTime('2026-12-31');

    while ($current <= $end) {
        $dow = (int)$current->format('N');
        if ($dow === 2 || $dow === 5) {
            $dt = clone $current;
            $dt->setTime(6, 0, 0);
            $dates[] = $dt->format('Y-m-d H:i:s');
        }
        $current->modify('+1 day');
    }

    return $dates;
}

$scheduledDates = generate_2026_schedule();
$totalSlots = count($scheduledDates);
echo "Generated {$totalSlots} scheduled dates (Tue/Fri)\n";
echo "  First: {$scheduledDates[0]}\n";
echo "  Last:  " . end($scheduledDates) . "\n\n";

if ($datesOnly) {
    foreach ($scheduledDates as $i => $d) {
        $dayName = date('l', strtotime($d));
        echo "  " . ($i + 1) . ". {$d} ({$dayName})\n";
    }
    exit(0);
}

// ─── Get existing titles ─────────────────────────────────

$pdo = db();
$existingTitles = $pdo->query("SELECT title FROM blog_posts ORDER BY published_at DESC")->fetchAll(PDO::FETCH_COLUMN);
echo "Existing blog posts: " . count($existingTitles) . "\n\n";

// Use the most recent 150 titles for dedup context (keeps prompt shorter)
$recentTitles = array_slice($existingTitles, 0, 150);
$titlesSummary = implode("\n", array_map(fn($t) => "- {$t}", $recentTitles));

// ─── Category distribution (total = $totalSlots) ────────

$categories = [
    ['name' => 'Agentic AI & AI in Production',     'count' => 14, 'service' => 'AI & ML',
     'trends' => 'Multi-agent systems, LangGraph, CrewAI, AI orchestration, AI in production, MLOps, LLMOps, model serving, AI agents for enterprise workflows'],
    ['name' => 'AI Governance & Trust',              'count' => 10, 'service' => 'AI & ML',
     'trends' => 'EU AI Act compliance, AI auditing, responsible AI, AI risk management, model cards, AI transparency, bias detection, explainable AI'],
    ['name' => 'Platform Engineering & DevEx',       'count' => 10, 'service' => 'Cloud & DevOps',
     'trends' => 'Internal developer platforms, Backstage, DevEx metrics, SPACE framework, golden paths, self-service infrastructure, developer portals'],
    ['name' => 'Cloud & DevOps Modern',              'count' => 10, 'service' => 'Cloud & DevOps',
     'trends' => 'FinOps, GreenOps, serverless at scale, multi-cloud strategy, GitOps, Kubernetes operators, cloud cost optimization, IaC evolution'],
    ['name' => 'Data Engineering & Architecture',    'count' => 10, 'service' => 'Data & Analytics',
     'trends' => 'Vector databases, RAG architectures, data mesh, data contracts, real-time streaming, lakehouse, dbt, data quality frameworks'],
    ['name' => 'Security & AI Cybersecurity',        'count' => 8,  'service' => 'Cybersecurity',
     'trends' => 'Post-quantum cryptography, AI-powered security, zero trust, supply chain security, SBOM, AI red teaming, preemptive security'],
    ['name' => 'Software Development & Testing',     'count' => 8,  'service' => 'Custom Software',
     'trends' => 'AI code generation, AI-native testing, vibe coding, Copilot workflows, code review automation, shift-left testing, property-based testing'],
    ['name' => 'Digital Transformation by Industry', 'count' => 8,  'service' => 'Digital Consulting',
     'trends' => 'Healthcare AI, fintech modernization, retail personalization, manufacturing IoT, supply chain digitization, public sector digital services'],
    ['name' => 'Quantum Computing & Future Tech',    'count' => 5,  'service' => 'AI & ML',
     'trends' => 'Quantum computing readiness, quantum-safe encryption, quantum algorithms, edge AI, neuromorphic computing, spatial computing'],
    ['name' => 'Tech Leadership & Culture',          'count' => 8,  'service' => 'Digital Consulting',
     'trends' => 'Engineering leadership, DevEx culture, tech talent retention, remote engineering teams, engineering metrics, tech debt management, CTO playbook'],
];

// Adjust distribution to match actual date slots
$totalCategorized = array_sum(array_column($categories, 'count'));
if ($totalCategorized > $totalSlots) {
    // Trim from largest category
    $categories[0]['count'] -= ($totalCategorized - $totalSlots);
}

$totalNeeded = array_sum(array_column($categories, 'count'));
echo "Topics to generate: {$totalNeeded} across " . count($categories) . " categories\n\n";

if ($dryRun) {
    foreach ($categories as $cat) {
        echo "  {$cat['name']}: {$cat['count']} posts → {$cat['service']}\n";
    }
    echo "\n[DRY RUN] No API calls made.\n";
    exit(0);
}

// ─── Generate topics per category ────────────────────────

$systemPrompt = <<<'SYSTEM'
You are a senior content strategist for CDEM Solutions, a technology consultancy. Generate unique blog topic ideas that do NOT overlap with existing published content.

## RULES
1. Each topic must be clearly different from existing titles
2. Topics must reflect current 2026 trends
3. Titles: 50-75 characters, specific, keyword-rich, SEO-optimized
4. Mix formats: "how-to", "guide", "comparison", "trend analysis", "case study"
5. Competitive with ThoughtWorks, Accenture, Deloitte, McKinsey tech blogs
6. Make titles SPECIFIC. Bad: "The Future of AI". Good: "Building Multi-Agent Systems with LangGraph: Architecture Patterns"

## OUTPUT FORMAT
Return a JSON object: {"topics": [...]} where each topic has:
- "title": Blog post title (50-75 chars)
- "tags": Array of 3-5 tags in Title Case
- "brief": One-sentence angle/hook description

Return ONLY valid JSON. No markdown. No code fences.
SYSTEM;

$allTopics = [];
$totalCost = 0;

// If output file already exists with topics, load and skip completed categories
$existingOutput = [];
if (file_exists($outputFile)) {
    $existingOutput = json_decode(file_get_contents($outputFile), true) ?? [];
    if (!empty($existingOutput)) {
        echo "Found " . count($existingOutput) . " existing topics in output file — will resume.\n\n";
    }
}

$existingCategoryCounts = [];
foreach ($existingOutput as $t) {
    $cat = $t['category'] ?? '';
    $existingCategoryCounts[$cat] = ($existingCategoryCounts[$cat] ?? 0) + 1;
    $allTopics[] = $t;
}

// Collect previously generated titles for dedup within new topics
$generatedTitles = array_map(fn($t) => $t['title'], $allTopics);

foreach ($categories as $catIndex => $cat) {
    $alreadyHave = $existingCategoryCounts[$cat['name']] ?? 0;
    $needed = $cat['count'] - $alreadyHave;

    if ($needed <= 0) {
        echo "[{$cat['name']}] Already have {$alreadyHave}/{$cat['count']} — skipping.\n";
        continue;
    }

    echo "[{$cat['name']}] Generating {$needed} topics (have {$alreadyHave}/{$cat['count']})...\n";

    // Build titles to avoid (recent existing + already generated in this run)
    $avoidTitles = array_merge($recentTitles, $generatedTitles);
    $avoidList = implode("\n", array_map(fn($t) => "- {$t}", array_slice($avoidTitles, 0, 200)));

    $userPrompt = "Generate exactly {$needed} blog topics for the category: \"{$cat['name']}\"\n\n"
        . "CDEM Service: {$cat['service']}\n"
        . "Trending topics in this category: {$cat['trends']}\n\n"
        . "AVOID THESE EXISTING TITLES:\n{$avoidList}\n\n"
        . "Return exactly {$needed} topics as JSON.";

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer {$apiKey}",
            'Content-Type: application/json',
        ],
        CURLOPT_POST => true,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_POSTFIELDS => json_encode([
            'model' => 'gpt-4o',
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user',   'content' => $userPrompt],
            ],
            'max_tokens' => 4000,
            'temperature' => 0.85,
            'response_format' => ['type' => 'json_object'],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        echo "  [ERROR] HTTP {$httpCode} — skipping category\n";
        continue;
    }

    $result = json_decode($response, true);
    $content = $result['choices'][0]['message']['content'] ?? null;
    if (!$content) {
        echo "  [ERROR] No content — skipping\n";
        continue;
    }

    $parsed = json_decode($content, true);
    $topics = $parsed['topics'] ?? (isset($parsed[0]) ? $parsed : []);

    if (empty($topics)) {
        echo "  [ERROR] No topics parsed — skipping\n";
        continue;
    }

    $added = 0;
    foreach ($topics as $topic) {
        if ($added >= $needed) break;

        $title = $topic['title'] ?? '';
        if (!$title) continue;

        $allTopics[] = [
            'title'            => $title,
            'slug'             => blog_slugify($title),
            'category'         => $cat['name'],
            'service_category' => $cat['service'],
            'tags'             => $topic['tags'] ?? [],
            'brief'            => $topic['brief'] ?? '',
        ];

        $generatedTitles[] = $title;
        $added++;
    }

    // Track cost
    $usage = $result['usage'] ?? [];
    $cost = (($usage['prompt_tokens'] ?? 0) * 2.5 / 1_000_000) + (($usage['completion_tokens'] ?? 0) * 10 / 1_000_000);
    $totalCost += $cost;

    echo "  Got {$added} topics (~\$" . number_format($cost, 3) . ")\n";

    // Save intermediate progress
    $intermediate = [];
    foreach ($allTopics as $i => $t) {
        $t['index'] = $i + 1;
        if ($i < count($scheduledDates)) {
            $t['scheduled_date'] = $scheduledDates[$i];
        }
        $intermediate[] = $t;
    }
    file_put_contents($outputFile, json_encode($intermediate, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
}

// ─── Final: assign indices and dates ─────────────────────

$output = [];
foreach ($allTopics as $i => $t) {
    if ($i >= count($scheduledDates)) {
        echo "[WARN] More topics (" . count($allTopics) . ") than dates ({$totalSlots}), truncating.\n";
        break;
    }

    $t['index'] = $i + 1;
    $t['scheduled_date'] = $scheduledDates[$i];
    $output[] = $t;
}

file_put_contents($outputFile, json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

// ─── Summary ─────────────────────────────────────────────

echo "\n===================================================\n";
echo "  DONE! Generated " . count($output) . " topics\n";
echo "===================================================\n\n";

echo "=== Category Distribution ===\n";
$catCounts = [];
foreach ($output as $t) {
    $catCounts[$t['category']] = ($catCounts[$t['category']] ?? 0) + 1;
}
foreach ($catCounts as $cat => $count) {
    echo "  {$cat}: {$count}\n";
}

echo "\n=== Sample Topics ===\n";
for ($i = 0; $i < min(10, count($output)); $i++) {
    $t = $output[$i];
    $day = date('l', strtotime($t['scheduled_date']));
    echo "  {$t['index']}. [{$t['service_category']}] {$t['title']}\n";
    echo "     {$t['scheduled_date']} ({$day}) | " . implode(', ', $t['tags']) . "\n";
}
echo "  ... (" . count($output) . " total)\n";

echo "\nSaved to: {$outputFile}\n";
echo "Total API cost: ~\$" . number_format($totalCost, 3) . "\n";
echo "\nNext: php scripts/generate_scheduled_posts.php run\n";
