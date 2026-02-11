<?php
session_start();

$base = dirname(__DIR__);

// --- Serve static files for built-in PHP server ---
if (php_sapi_name() === 'cli-server') {
    $file = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($file)) return false;
}

// --- Bootstrap ---
require $base . '/lib/router.php';
require_once $base . '/lib/database.php';

$uri = get_uri();

// --- SEO redirects (301/302 from admin) ---
try {
    $redirects = db_get_redirects();
    $cleanUri = '/' . trim($uri, '/');
    foreach ($redirects as $r) {
        if ($cleanUri === $r['from_path'] || $cleanUri . '/' === $r['from_path']) {
            $code = (int)$r['redirect_type'] === 302 ? 302 : 301;
            header('Location: ' . $r['to_url'], true, $code);
            exit;
        }
    }
} catch (Exception $e) {
    // DB not yet set up — skip redirects
}

// --- Admin routes → delegate to admin.php ---
if (strpos($uri, '/admin') === 0) {
    require __DIR__ . '/admin.php';
    exit;
}

// --- Language switch endpoint (SEO-clean, no ?lang= in URLs) ---
if (preg_match('#^/set-lang/(en|es)/?$#', $uri, $langMatch)) {
    $newLang = $langMatch[1];
    $_SESSION['lang'] = $newLang;
    setcookie('lang', $newLang, time() + 86400 * 365, '/', '', false, true);
    $referer = $_SERVER['HTTP_REFERER'] ?? '/';
    // Strip any old ?lang= from referer
    $referer = preg_replace('/[?&]lang=[a-z]{2}/', '', $referer);
    header('Location: ' . $referer, true, 302);
    exit;
}

// --- API routes ---
if ($uri === '/api/contact' || $uri === '/api/contact/') {
    require $base . '/lib/contact.php';
    handle_contact_form();
    exit;
}

// --- Dynamic sitemap.xml ---
if ($uri === '/sitemap.xml') {
    require_once $base . '/lib/blog.php';
    header('Content-Type: application/xml; charset=UTF-8');
    echo generate_sitemap();
    exit;
}

// --- Dynamic robots.txt ---
if ($uri === '/robots.txt') {
    header('Content-Type: text/plain; charset=UTF-8');
    try {
        $custom = db_get_setting('seo_robots_txt', '');
    } catch (Exception $e) {
        $custom = '';
    }
    if ($custom) {
        echo $custom;
    } else {
        echo "User-agent: *\n";
        echo "Allow: /\n";
        echo "Disallow: /admin/\n";
        echo "Disallow: /api/\n";
        echo "Disallow: /set-lang/\n\n";
        echo "Sitemap: https://cdemsolutions.com/sitemap.xml\n";
    }
    exit;
}

// --- Frontend bootstrap ---
require $base . '/lib/lang.php';
require $base . '/lib/picture.php';
require $base . '/partials/icons.php';

// --- Page routes ---
$routes = [
    '/'                => 'home',
    '/services'        => 'services',
    '/about'           => 'about',
    '/contact'         => 'contact',
    '/blog'            => 'blog-list',
    '/blog/{slug}'     => 'blog-post',
];

$match = route_match($uri, $routes);

if (!$match) {
    http_response_code(404);
    $template = '404';
    $is_404 = true;
} else {
    $template = $match['handler'];
    $params = $match['params'];
    $is_404 = false;
}

// --- Load data for specific templates ---
if ($template === 'blog-list' || $template === 'blog-post') {
    require_once $base . '/lib/blog.php';
}

if ($template === 'blog-list') {
    $page = max(1, intval($_GET['page'] ?? 1));
    $active_tag = trim($_GET['tag'] ?? '');
    $result = blog_list_published($page, 9, $active_tag);
    $posts = $result['data'];
    $pagination = $result['pagination'];
}

if ($template === 'blog-post') {
    $post = blog_get_by_slug($params['slug'] ?? '');
    if (!$post || $post['status'] !== 'published') {
        http_response_code(404);
        $template = '404';
        $is_404 = true;
    }
}

// --- Set canonical URL for all pages (SEO) ---
$site_url = 'https://cdemsolutions.com';
if (!isset($canonical_url) && !($is_404 ?? false)) {
    $canonical_path = '/' . trim($uri, '/');
    if ($canonical_path !== '/') $canonical_path .= '/';
    $canonical_url = $site_url . $canonical_path;
}

// --- Load SEO settings from DB ---
try {
    $seo_title_suffix = db_get_setting('seo_title_suffix', ' — CDEM Solutions');
    $seo_default_og_image = db_get_setting('seo_default_og_image', '/img/logo.png');
    $seo_head_scripts = db_get_setting('seo_head_scripts', '');
    $seo_body_scripts = db_get_setting('seo_body_scripts', '');
    $seo_ga_code = db_get_setting('seo_ga_code', '');
    $seo_gtm_code = db_get_setting('seo_gtm_code', '');
    $seo_verification_google = db_get_setting('seo_verification_google', '');
    $seo_verification_bing = db_get_setting('seo_verification_bing', '');
} catch (Exception $e) {
    $seo_title_suffix = ' — CDEM Solutions';
    $seo_default_og_image = '/img/logo.png';
    $seo_head_scripts = '';
    $seo_body_scripts = '';
    $seo_ga_code = '';
    $seo_gtm_code = '';
    $seo_verification_google = '';
    $seo_verification_bing = '';
}

// --- Render page (buffer template first so it can set $page_title etc. before head) ---
ob_start();
require $base . '/templates/' . $template . '.php';
$_template_html = ob_get_clean();

require $base . '/partials/head.php';
require $base . '/partials/header.php';
echo $_template_html;
require $base . '/partials/footer.php';

// --- Sitemap generator ---
function generate_sitemap(): string {
    $site = 'https://cdemsolutions.com';
    $now = date('Y-m-d');

    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
    $xml .= '        xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";

    // Static pages
    $pages = [
        ['/', '1.0', 'weekly'],
        ['/services/', '0.8', 'monthly'],
        ['/about/', '0.7', 'monthly'],
        ['/contact/', '0.7', 'monthly'],
        ['/blog/', '0.9', 'daily'],
    ];

    foreach ($pages as [$path, $priority, $freq]) {
        $xml .= "  <url>\n";
        $xml .= "    <loc>{$site}{$path}</loc>\n";
        $xml .= "    <lastmod>{$now}</lastmod>\n";
        $xml .= "    <changefreq>{$freq}</changefreq>\n";
        $xml .= "    <priority>{$priority}</priority>\n";
        $xml .= "  </url>\n";
    }

    // Blog posts
    try {
        $pdo = db();
        $stmt = $pdo->query("SELECT slug, updated_at, published_at FROM blog_posts WHERE status = 'published' ORDER BY published_at DESC");
        while ($row = $stmt->fetch()) {
            $date = date('Y-m-d', strtotime($row['updated_at'] ?? $row['published_at']));
            $xml .= "  <url>\n";
            $xml .= "    <loc>{$site}/blog/" . htmlspecialchars($row['slug']) . "/</loc>\n";
            $xml .= "    <lastmod>{$date}</lastmod>\n";
            $xml .= "    <changefreq>monthly</changefreq>\n";
            $xml .= "    <priority>0.6</priority>\n";
            $xml .= "  </url>\n";
        }
    } catch (Exception $e) {
        // DB not available
    }

    $xml .= "</urlset>\n";
    return $xml;
}
