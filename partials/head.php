<?php
$_site_url = 'https://cdemsolutions.com';
$_current_uri = get_uri();
$_abs_og_image = (isset($og_image) && $og_image) ? $og_image : ($seo_default_og_image ?? '/img/og-default.jpg');
if (strpos($_abs_og_image, 'http') !== 0) {
    $_abs_og_image = $_site_url . $_abs_og_image;
}
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title ?? t('meta.title')) ?></title>
    <meta name="description" content="<?= htmlspecialchars($page_description ?? t('meta.description')) ?>">
    <?php if (!empty($page_keywords)): ?>
    <meta name="keywords" content="<?= htmlspecialchars($page_keywords) ?>">
    <?php endif; ?>

    <?php if (!empty($is_404)): ?>
    <meta name="robots" content="noindex, follow">
    <?php endif; ?>

    <?php // Search engine verification tags ?>
    <?php $__google_v = $seo_verification_google ?? ''; if (!empty($__google_v)): ?>
    <meta name="google-site-verification" content="<?= htmlspecialchars($__google_v) ?>">
    <?php endif; ?>
    <?php $__bing_v = $seo_verification_bing ?? ''; if (!empty($__bing_v)): ?>
    <meta name="msvalidate.01" content="<?= htmlspecialchars($__bing_v) ?>">
    <?php endif; ?>

    <?php if (!empty($canonical_url)): ?>
    <link rel="canonical" href="<?= htmlspecialchars($canonical_url) ?>">
    <?php endif; ?>

    <!-- Hreflang alternates -->
    <?php if (!empty($canonical_url)): ?>
    <link rel="alternate" hreflang="en" href="<?= htmlspecialchars($canonical_url) ?>">
    <link rel="alternate" hreflang="es" href="<?= htmlspecialchars($canonical_url) ?>">
    <link rel="alternate" hreflang="x-default" href="<?= htmlspecialchars($canonical_url) ?>">
    <?php endif; ?>

    <!-- Open Graph -->
    <meta property="og:title" content="<?= htmlspecialchars($page_title ?? t('meta.title')) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($page_description ?? t('meta.description')) ?>">
    <meta property="og:image" content="<?= htmlspecialchars($_abs_og_image) ?>">
    <meta property="og:type" content="<?= $og_type ?? 'website' ?>">
    <meta property="og:url" content="<?= htmlspecialchars($canonical_url ?? $_site_url . $_current_uri) ?>">
    <meta property="og:site_name" content="CDEM Solutions">
    <meta property="og:locale" content="<?= $lang === 'es' ? 'es_ES' : 'en_US' ?>">
    <?php if (($og_type ?? 'website') === 'article' && !empty($post)): ?>
    <meta property="article:published_time" content="<?= htmlspecialchars($post['published_at'] ?? '') ?>">
    <meta property="article:author" content="<?= htmlspecialchars($post['author'] ?? 'CDEM Solutions') ?>">
    <?php endif; ?>

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($page_title ?? t('meta.title')) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($page_description ?? t('meta.description')) ?>">
    <meta name="twitter:image" content="<?= htmlspecialchars($_abs_og_image) ?>">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/img/logo.png">
    <link rel="apple-touch-icon" href="/img/logo.png">

    <!-- Fonts (preconnect + async load to avoid render-blocking) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
    <noscript><link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"></noscript>

    <!-- Critical CSS (inline to avoid render-blocking) -->
    <?php
    $critical_file = __DIR__ . '/../public/css/critical.min.css';
    if (file_exists($critical_file)):
    ?>
    <style><?= file_get_contents($critical_file) ?></style>
    <?php endif; ?>

    <!-- Full stylesheet (async load) -->
    <?php $css_file = file_exists(__DIR__ . '/../public/css/style.min.css') ? '/css/style.min.css' : '/css/style.css'; ?>
    <?php $css_ver = filemtime(__DIR__ . '/../public/css/' . basename($css_file)); ?>
    <link rel="preload" href="<?= $css_file ?>?v=<?= $css_ver ?>" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="<?= $css_file ?>?v=<?= $css_ver ?>"></noscript>

    <?php if (($template ?? '') === 'home'): ?>
    <!-- Preload LCP hero image -->
    <link rel="preload" as="image" href="/img/hero-bg.webp" type="image/webp" fetchpriority="high">
    <?php endif; ?>

    <!-- Schema.org — Organization -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Organization",
        "name": "CDEM Solutions",
        "url": "https://cdemsolutions.com",
        "logo": "https://cdemsolutions.com/img/logo.png",
        "description": "<?= htmlspecialchars(t('meta.description')) ?>",
        "contactPoint": {
            "@type": "ContactPoint",
            "email": "hello@cdemsolutions.com",
            "contactType": "customer service"
        },
        "sameAs": []
    }
    </script>

    <?php if (!empty($canonical_url) && !($is_404 ?? false)): ?>
    <!-- Schema.org — WebPage -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebPage",
        "name": "<?= htmlspecialchars($page_title ?? t('meta.title')) ?>",
        "description": "<?= htmlspecialchars($page_description ?? t('meta.description')) ?>",
        "url": "<?= htmlspecialchars($canonical_url) ?>",
        "inLanguage": "<?= $lang ?>",
        "isPartOf": {
            "@type": "WebSite",
            "name": "CDEM Solutions",
            "url": "https://cdemsolutions.com"
        }
    }
    </script>
    <?php endif; ?>

    <?php // Google Analytics ?>
    <?php if (!empty($seo_ga_code)): ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= htmlspecialchars($seo_ga_code) ?>"></script>
    <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','<?= htmlspecialchars($seo_ga_code) ?>');</script>
    <?php endif; ?>

    <?php // Google Tag Manager ?>
    <?php if (!empty($seo_gtm_code)): ?>
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','<?= htmlspecialchars($seo_gtm_code) ?>');</script>
    <?php endif; ?>

    <?php // Custom head scripts from admin ?>
    <?php if (!empty($seo_head_scripts)): ?>
    <?= $seo_head_scripts ?>
    <?php endif; ?>
</head>
<body>
    <?php if (!empty($seo_gtm_code)): ?>
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?= htmlspecialchars($seo_gtm_code) ?>" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <?php endif; ?>
