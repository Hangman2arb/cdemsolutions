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

// --- Accept cookies endpoint ---
if ($uri === '/accept-cookies' || $uri === '/accept-cookies/') {
    setcookie('cookies_accepted', '1', time() + 86400 * 365, '/', '', false, true);
    $referer = $_SERVER['HTTP_REFERER'] ?? '/';
    header('Location: ' . $referer);
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

    // Smart redirect: if switching language on a blog post, redirect to the other slug
    $refPath = parse_url($referer, PHP_URL_PATH);
    if (preg_match('#^/blog/([^/]+)/?$#', $refPath, $blogMatch)) {
        require_once $base . '/lib/blog.php';
        $oldSlug = $blogMatch[1];
        $oldLang = $newLang === 'es' ? 'en' : 'es';

        if ($newLang === 'es') {
            // Switching to ES: find ES slug for this EN post
            $pdo = db();
            $stmt = $pdo->prepare('SELECT bt.slug FROM blog_translations bt JOIN blog_posts p ON p.id = bt.post_id WHERE p.slug = :slug AND bt.lang = :lang');
            $stmt->execute([':slug' => $oldSlug, ':lang' => 'es']);
            $newSlug = $stmt->fetchColumn();
            if ($newSlug) {
                header('Location: /blog/' . $newSlug . '/', true, 302);
                exit;
            }
        } else {
            // Switching to EN: find EN slug for this ES translation
            $enSlug = blog_get_en_slug_from_translation($oldSlug, 'es');
            if ($enSlug) {
                header('Location: /blog/' . $enSlug . '/', true, 302);
                exit;
            }
        }
    }

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
    $result = blog_list_published($page, 9, $active_tag, $lang);
    $posts = $result['data'];
    $pagination = $result['pagination'];
}

if ($template === 'blog-post') {
    $post = blog_get_by_slug($params['slug'] ?? '', $lang);
    if (!$post || $post['status'] !== 'published') {
        http_response_code(404);
        $template = '404';
        $is_404 = true;
    } else {
        // Set hreflang URLs for this blog post
        $hreflang_en = 'https://cdemsolutions.com/blog/' . ($post['en_slug'] ?? $post['slug']) . '/';
        $translatedSlug = $post['translated_slug'] ?? blog_get_translation_slug($post['id'], 'es');
        $hreflang_es = $translatedSlug ? 'https://cdemsolutions.com/blog/' . $translatedSlug . '/' : null;
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
echo '<main id="main-content">';
echo $_template_html;
echo '</main>';
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

    // Blog posts with hreflang for translations
    try {
        $pdo = db();
        $stmt = $pdo->query("SELECT p.id, p.slug, p.updated_at, p.published_at,
            bt.slug AS es_slug, bt.updated_at AS es_updated_at
            FROM blog_posts p
            LEFT JOIN blog_translations bt ON bt.post_id = p.id AND bt.lang = 'es'
            WHERE p.status = 'published'
            ORDER BY p.published_at DESC");
        while ($row = $stmt->fetch()) {
            $date = date('Y-m-d', strtotime($row['updated_at'] ?? $row['published_at']));
            $enUrl = $site . '/blog/' . htmlspecialchars($row['slug']) . '/';

            // English URL
            $xml .= "  <url>\n";
            $xml .= "    <loc>{$enUrl}</loc>\n";
            $xml .= "    <lastmod>{$date}</lastmod>\n";
            $xml .= "    <changefreq>monthly</changefreq>\n";
            $xml .= "    <priority>0.6</priority>\n";
            if (!empty($row['es_slug'])) {
                $esUrl = $site . '/blog/' . htmlspecialchars($row['es_slug']) . '/';
                $xml .= "    <xhtml:link rel=\"alternate\" hreflang=\"en\" href=\"{$enUrl}\" />\n";
                $xml .= "    <xhtml:link rel=\"alternate\" hreflang=\"es\" href=\"{$esUrl}\" />\n";
                $xml .= "    <xhtml:link rel=\"alternate\" hreflang=\"x-default\" href=\"{$enUrl}\" />\n";
            }
            $xml .= "  </url>\n";

            // Spanish URL (if translation exists)
            if (!empty($row['es_slug'])) {
                $esUrl = $site . '/blog/' . htmlspecialchars($row['es_slug']) . '/';
                $esDate = date('Y-m-d', strtotime($row['es_updated_at'] ?? $row['updated_at']));
                $xml .= "  <url>\n";
                $xml .= "    <loc>{$esUrl}</loc>\n";
                $xml .= "    <lastmod>{$esDate}</lastmod>\n";
                $xml .= "    <changefreq>monthly</changefreq>\n";
                $xml .= "    <priority>0.6</priority>\n";
                $xml .= "    <xhtml:link rel=\"alternate\" hreflang=\"en\" href=\"{$enUrl}\" />\n";
                $xml .= "    <xhtml:link rel=\"alternate\" hreflang=\"es\" href=\"{$esUrl}\" />\n";
                $xml .= "    <xhtml:link rel=\"alternate\" hreflang=\"x-default\" href=\"{$enUrl}\" />\n";
                $xml .= "  </url>\n";
            }
        }
    } catch (Exception $e) {
        // DB not available
    }

    $xml .= "</urlset>\n";
    return $xml;
}
