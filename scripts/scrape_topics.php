<?php
/**
 * Blog Topic Scraper — fetches RSS feeds from tech sources
 * and populates the blog_ideas table.
 *
 * Usage: php scripts/scrape_topics.php
 */

require __DIR__ . '/../lib/database.php';

$pdo = db();

// --- RSS Sources ---
$feeds = [
    ['url' => 'https://www.infoq.com/feed/',                          'site' => 'InfoQ'],
    ['url' => 'https://aws.amazon.com/blogs/aws/feed/',                'site' => 'AWS Blog'],
    ['url' => 'https://cloud.google.com/blog/rss',                     'site' => 'Google Cloud'],
    ['url' => 'https://devblogs.microsoft.com/devops/feed/',           'site' => 'MS DevOps'],
    ['url' => 'https://thenewstack.io/feed/',                          'site' => 'The New Stack'],
    ['url' => 'https://www.smashingmagazine.com/feed/',                'site' => 'Smashing Magazine'],
    ['url' => 'https://hackernoon.com/feed',                           'site' => 'HackerNoon'],
    ['url' => 'https://www.thoughtworks.com/rss/insights.xml',         'site' => 'ThoughtWorks'],
    ['url' => 'https://techcrunch.com/feed/',                          'site' => 'TechCrunch'],
];

// --- Service classification by keywords ---
$serviceKeywords = [
    'AI & ML' => [
        'ai', 'artificial intelligence', 'machine learning', 'ml', 'deep learning',
        'neural', 'nlp', 'llm', 'gpt', 'chatbot', 'generative', 'computer vision',
        'tensorflow', 'pytorch', 'model training', 'inference', 'openai', 'copilot',
        'large language', 'transformer', 'diffusion',
    ],
    'Cloud & DevOps' => [
        'cloud', 'aws', 'azure', 'gcp', 'kubernetes', 'k8s', 'docker', 'devops',
        'ci/cd', 'terraform', 'serverless', 'lambda', 'microservices', 'containers',
        'infrastructure', 'deployment', 'pipeline', 'gitops', 'helm', 'iac',
        'observability', 'monitoring', 'sre', 'platform engineering',
    ],
    'Custom Software' => [
        'software development', 'api', 'rest', 'graphql', 'react', 'angular', 'vue',
        'node', 'python', 'java', 'typescript', 'web development', 'mobile app',
        'frontend', 'backend', 'full-stack', 'microservice', 'architecture',
        'design patterns', 'testing', 'agile', 'scrum', 'low-code', 'no-code',
    ],
    'Cybersecurity' => [
        'security', 'cybersecurity', 'vulnerability', 'ransomware', 'encryption',
        'zero trust', 'authentication', 'oauth', 'penetration', 'firewall', 'soc',
        'threat', 'malware', 'phishing', 'compliance', 'gdpr', 'siem', 'devsecops',
        'identity', 'access management',
    ],
    'Data & Analytics' => [
        'data', 'analytics', 'big data', 'database', 'sql', 'nosql', 'data warehouse',
        'etl', 'data pipeline', 'bi', 'business intelligence', 'visualization',
        'data lake', 'streaming', 'kafka', 'spark', 'snowflake', 'dbt',
        'data engineering', 'data science',
    ],
    'Digital Consulting' => [
        'digital transformation', 'strategy', 'consulting', 'leadership', 'innovation',
        'startup', 'enterprise', 'roi', 'business process', 'change management',
        'product management', 'tech trends', 'remote work', 'culture',
    ],
];

/**
 * Classify a title into a CDEM service based on keyword matching.
 */
function classify_service(string $title, array $serviceKeywords): string
{
    $titleLower = mb_strtolower($title, 'UTF-8');
    $scores = [];

    foreach ($serviceKeywords as $service => $keywords) {
        $score = 0;
        foreach ($keywords as $kw) {
            if (str_contains($titleLower, $kw)) {
                $score++;
            }
        }
        if ($score > 0) {
            $scores[$service] = $score;
        }
    }

    if (empty($scores)) {
        return 'Digital Consulting'; // default fallback
    }

    arsort($scores);
    return array_key_first($scores);
}

/**
 * Fetch and parse an RSS feed, returning an array of items.
 */
function fetch_rss(string $url): array
{
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 15,
            'user_agent' => 'CDEM-Blog-Scraper/1.0',
            'follow_location' => true,
        ],
        'ssl' => [
            'verify_peer' => true,
        ],
    ]);

    $xml = @file_get_contents($url, false, $ctx);
    if ($xml === false) {
        return [];
    }

    // Suppress XML warnings for malformed feeds
    libxml_use_internal_errors(true);
    $feed = @simplexml_load_string($xml);
    libxml_clear_errors();

    if (!$feed) {
        return [];
    }

    $items = [];

    // RSS 2.0 format
    if (isset($feed->channel->item)) {
        foreach ($feed->channel->item as $item) {
            $items[] = [
                'title' => trim((string)$item->title),
                'link'  => trim((string)$item->link),
                'date'  => !empty((string)$item->pubDate) ? date('Y-m-d', strtotime((string)$item->pubDate)) : null,
            ];
        }
    }
    // Atom format
    elseif (isset($feed->entry)) {
        foreach ($feed->entry as $entry) {
            $link = '';
            if (isset($entry->link['href'])) {
                $link = (string)$entry->link['href'];
            }
            $items[] = [
                'title' => trim((string)$entry->title),
                'link'  => $link,
                'date'  => !empty((string)$entry->published)
                    ? date('Y-m-d', strtotime((string)$entry->published))
                    : (!empty((string)$entry->updated) ? date('Y-m-d', strtotime((string)$entry->updated)) : null),
            ];
        }
    }

    return $items;
}

// --- Main ---

echo "=== CDEM Blog Topic Scraper ===\n\n";

// Build existing title set for deduplication
$existingStmt = $pdo->query('SELECT LOWER(source_title) FROM blog_ideas');
$existingTitles = [];
while ($row = $existingStmt->fetchColumn()) {
    $existingTitles[$row] = true;
}
echo "Existing ideas in DB: " . count($existingTitles) . "\n\n";

$totalInserted = 0;
$totalSkipped = 0;
$allIdeas = [];

foreach ($feeds as $feed) {
    echo "Fetching: {$feed['site']} ({$feed['url']})...\n";

    $items = fetch_rss($feed['url']);
    if (empty($items)) {
        echo "  [WARN] No items fetched\n";
        continue;
    }

    $inserted = 0;
    $skipped = 0;

    foreach ($items as $item) {
        if (empty($item['title'])) {
            continue;
        }

        $titleLower = mb_strtolower($item['title'], 'UTF-8');

        // Skip duplicates
        if (isset($existingTitles[$titleLower])) {
            $skipped++;
            continue;
        }

        $service = classify_service($item['title'], $serviceKeywords);

        $stmt = $pdo->prepare('INSERT INTO blog_ideas (source_url, source_title, source_date, source_site, target_service, status) VALUES (:url, :title, :date, :site, :service, :status)');
        $stmt->execute([
            ':url'     => $item['link'],
            ':title'   => $item['title'],
            ':date'    => $item['date'],
            ':site'    => $feed['site'],
            ':service' => $service,
            ':status'  => 'pending',
        ]);

        $existingTitles[$titleLower] = true;
        $inserted++;

        $allIdeas[] = [
            'source_title'   => $item['title'],
            'source_url'     => $item['link'],
            'source_date'    => $item['date'],
            'source_site'    => $feed['site'],
            'target_service' => $service,
        ];
    }

    echo "  Inserted: {$inserted}, Skipped (dupes): {$skipped}, Total items: " . count($items) . "\n";
    $totalInserted += $inserted;
    $totalSkipped += $skipped;
}

// Save JSON backup
$storagePath = __DIR__ . '/../storage';
if (!is_dir($storagePath)) {
    mkdir($storagePath, 0755, true);
}
file_put_contents(
    $storagePath . '/blog_ideas.json',
    json_encode($allIdeas, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
);

// Summary
$countStmt = $pdo->query("SELECT target_service, COUNT(*) as cnt FROM blog_ideas WHERE status='pending' GROUP BY target_service ORDER BY cnt DESC");
echo "\n=== Summary ===\n";
echo "New ideas inserted: {$totalInserted}\n";
echo "Duplicates skipped: {$totalSkipped}\n";
echo "JSON backup: storage/blog_ideas.json\n\n";
echo "Pending ideas by service:\n";
while ($row = $countStmt->fetch(PDO::FETCH_ASSOC)) {
    echo "  {$row['target_service']}: {$row['cnt']}\n";
}

$totalPending = $pdo->query("SELECT COUNT(*) FROM blog_ideas WHERE status='pending'")->fetchColumn();
echo "\nTotal pending: {$totalPending}\n";
echo "Done!\n";
