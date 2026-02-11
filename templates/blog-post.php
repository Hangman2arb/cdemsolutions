<?php
$page_title = htmlspecialchars($post['meta_title'] ?: $post['title']) . ($seo_title_suffix ?? ' — CDEM Solutions');
$page_description = htmlspecialchars($post['meta_description'] ?? $post['excerpt'] ?? '');
$page_keywords = htmlspecialchars($post['meta_keywords'] ?? '');
$og_type = 'article';
$og_image = $post['featured_image'] ?? '';
$canonical_url = 'https://cdemsolutions.com/blog/' . $post['slug'] . '/';
?>

    <!-- Schema.org — Article -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Article",
        "headline": "<?= htmlspecialchars($post['title']) ?>",
        "description": "<?= htmlspecialchars($post['meta_description'] ?? $post['excerpt'] ?? '') ?>",
        "url": "<?= htmlspecialchars($canonical_url) ?>",
        <?php if (!empty($post['featured_image'])): ?>
        "image": "<?= strpos($post['featured_image'], 'http') === 0 ? htmlspecialchars($post['featured_image']) : 'https://cdemsolutions.com' . htmlspecialchars($post['featured_image']) ?>",
        <?php endif; ?>
        "datePublished": "<?= htmlspecialchars($post['published_at'] ?? '') ?>",
        "dateModified": "<?= htmlspecialchars($post['updated_at'] ?? $post['published_at'] ?? '') ?>",
        "author": {
            "@type": "Organization",
            "name": "<?= htmlspecialchars($post['author'] ?? 'CDEM Solutions') ?>"
        },
        "publisher": {
            "@type": "Organization",
            "name": "CDEM Solutions",
            "logo": {
                "@type": "ImageObject",
                "url": "https://cdemsolutions.com/img/logo.png"
            }
        },
        "mainEntityOfPage": {
            "@type": "WebPage",
            "@id": "<?= htmlspecialchars($canonical_url) ?>"
        },
        "wordCount": <?= str_word_count(strip_tags($post['content_html'] ?? '')) ?>,
        "inLanguage": "<?= $lang ?>"
    }
    </script>

    <!-- Schema.org — BreadcrumbList -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "BreadcrumbList",
        "itemListElement": [
            {"@type": "ListItem", "position": 1, "name": "<?= t('nav.home') ?>", "item": "https://cdemsolutions.com/"},
            {"@type": "ListItem", "position": 2, "name": "<?= t('nav.blog') ?>", "item": "https://cdemsolutions.com/blog/"},
            {"@type": "ListItem", "position": 3, "name": "<?= htmlspecialchars($post['title']) ?>", "item": "<?= htmlspecialchars($canonical_url) ?>"}
        ]
    }
    </script>

    <!-- ===== PAGE HEADER ===== -->
    <section class="page-header page-header--blog">
        <div class="page-header__bg">
            <div class="page-header__bg-image" style="background-image: url('/img/blog-bg.jpg')"></div>
            <div class="hero__orb hero__orb--1"></div>
            <div class="hero__orb hero__orb--2"></div>
            <div class="hero__grid"></div>
        </div>
        <div class="container page-header__content">
            <nav class="breadcrumb" aria-label="Breadcrumb" data-animate>
                <a href="/"><?= t('nav.home') ?></a>
                <span><?= icon('chevron-right') ?></span>
                <a href="/blog/"><?= t('nav.blog') ?></a>
                <span><?= icon('chevron-right') ?></span>
                <span><?= htmlspecialchars($post['title']) ?></span>
            </nav>
            <h1 class="page-header__title animate-in"><?= htmlspecialchars($post['title']) ?></h1>
            <div class="blog-post__meta animate-in animate-in--delay-1">
                <span><?= icon('calendar') ?> <time datetime="<?= htmlspecialchars($post['published_at']) ?>"><?= date('M j, Y', strtotime($post['published_at'])) ?></time></span>
                <span><?= icon('clock') ?> <?= $post['reading_time'] ?? 5 ?> min <?= t('blog.read_time') ?></span>
            </div>
        </div>
    </section>

    <!-- ===== BLOG POST CONTENT ===== -->
    <article class="section blog-post">
        <div class="container">
            <div class="blog-post__layout">
                <div class="blog-post__content" data-animate>
                    <?php if (!empty($post['featured_image'])): ?>
                    <div class="blog-post__featured-image">
                        <img src="<?= htmlspecialchars($post['featured_image']) ?>" alt="<?= htmlspecialchars($post['title']) ?>" loading="lazy" width="900" height="500">
                    </div>
                    <?php endif; ?>

                    <div class="blog-post__body prose">
                        <?= $post['content_html'] ?>
                    </div>

                    <?php if (!empty($post['tags'])): ?>
                    <div class="blog-post__tags">
                        <span><?= icon('tag') ?> <?= t('blog.tags') ?>:</span>
                        <?php foreach (explode(',', $post['tags']) as $tag): ?>
                        <a href="/blog/?tag=<?= urlencode(trim($tag)) ?>" class="blog-tag"><?= htmlspecialchars(trim($tag)) ?></a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Share buttons -->
                    <div class="blog-post__share">
                        <span><?= icon('share') ?> <?= t('blog.share') ?>:</span>
                        <a href="https://twitter.com/intent/tweet?url=<?= urlencode($canonical_url) ?>&text=<?= urlencode($post['title']) ?>" target="_blank" rel="noopener noreferrer" class="blog-share-btn">Twitter</a>
                        <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode($canonical_url) ?>" target="_blank" rel="noopener noreferrer" class="blog-share-btn">LinkedIn</a>
                    </div>
                </div>
            </div>
        </div>
    </article>

    <!-- ===== CTA ===== -->
    <section class="cta-banner">
        <div class="cta-banner__bg">
            <div class="cta-banner__bg-image" style="background-image: url('/img/cta-bg.jpg')"></div>
            <div class="hero__orb hero__orb--1"></div>
            <div class="hero__orb hero__orb--2"></div>
        </div>
        <div class="container cta-banner__inner" data-animate>
            <h2 class="cta-banner__title"><?= t('cta.title') ?></h2>
            <p class="cta-banner__subtitle"><?= t('cta.subtitle') ?></p>
            <a href="/contact/" class="btn btn--accent"><?= t('cta.button') ?></a>
        </div>
    </section>
