<?php
$page_title = t('blog.page_title');
$page_description = t('blog.page_description');
?>

    <!-- Schema.org — BreadcrumbList -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "BreadcrumbList",
        "itemListElement": [
            {"@type": "ListItem", "position": 1, "name": "<?= t('nav.home') ?>", "item": "https://cdemsolutions.com/"},
            {"@type": "ListItem", "position": 2, "name": "<?= t('nav.blog') ?>", "item": "https://cdemsolutions.com/blog/"}
        ]
    }
    </script>

    <!-- ===== PAGE HEADER ===== -->
    <section class="page-header">
        <div class="page-header__bg">
            <div class="hero__orb hero__orb--1"></div>
            <div class="hero__orb hero__orb--2"></div>
            <div class="hero__grid"></div>
        </div>
        <div class="container page-header__content">
            <nav class="breadcrumb" aria-label="Breadcrumb" data-animate>
                <a href="/"><?= t('nav.home') ?></a>
                <span><?= icon('chevron-right') ?></span>
                <span><?= t('nav.blog') ?></span>
            </nav>
            <h1 class="page-header__title animate-in"><?= t('blog.page_heading') ?></h1>
            <p class="page-header__subtitle animate-in animate-in--delay-1"><?= t('blog.page_subtitle') ?></p>
        </div>
    </section>

    <!-- ===== BLOG LISTING ===== -->
    <section class="section blog-listing">
        <div class="container">
            <?php if (!empty($active_tag)): ?>
            <div class="blog-listing__filter" data-animate>
                <span><?= t('blog.filtering_by') ?>:</span>
                <span class="blog-tag blog-tag--active"><?= htmlspecialchars($active_tag) ?></span>
                <a href="/blog/" class="blog-listing__clear"><?= t('blog.clear_filter') ?></a>
            </div>
            <?php endif; ?>

            <?php if (!empty($posts)): ?>
            <div class="blog-grid">
                <?php foreach ($posts as $i => $post): ?>
                <article class="blog-card" data-animate data-delay="<?= $i * 100 ?>">
                    <?php if (!empty($post['featured_image'])): ?>
                    <a href="/blog/<?= htmlspecialchars($post['slug']) ?>/" class="blog-card__image">
                        <img src="<?= htmlspecialchars($post['featured_image']) ?>" alt="<?= htmlspecialchars($post['title']) ?>" loading="lazy">
                    </a>
                    <?php endif; ?>
                    <div class="blog-card__body">
                        <?php if (!empty($post['tags'])): ?>
                        <div class="blog-card__tags">
                            <?php foreach (explode(',', $post['tags']) as $tag): ?>
                            <a href="/blog/?tag=<?= urlencode(trim($tag)) ?>" class="blog-tag"><?= htmlspecialchars(trim($tag)) ?></a>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        <h2 class="blog-card__title">
                            <a href="/blog/<?= htmlspecialchars($post['slug']) ?>/"><?= htmlspecialchars($post['title']) ?></a>
                        </h2>
                        <p class="blog-card__excerpt"><?= htmlspecialchars($post['excerpt'] ?? '') ?></p>
                        <div class="blog-card__meta">
                            <span><?= icon('calendar') ?> <?= date('M j, Y', strtotime($post['published_at'])) ?></span>
                            <span><?= icon('clock') ?> <?= $post['reading_time'] ?? 5 ?> min</span>
                        </div>
                        <a href="/blog/<?= htmlspecialchars($post['slug']) ?>/" class="blog-card__link">
                            <?= t('blog.read_more') ?> <?= icon('arrow-right') ?>
                        </a>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>

            <?php if (!empty($pagination)): ?>
            <nav class="pagination" data-animate>
                <?php if ($pagination['current'] > 1): ?>
                <a href="?page=<?= $pagination['current'] - 1 ?><?= !empty($active_tag) ? '&tag=' . urlencode($active_tag) : '' ?>" class="pagination__link">&laquo; <?= t('blog.prev') ?></a>
                <?php endif; ?>

                <?php for ($p = 1; $p <= $pagination['total_pages']; $p++): ?>
                <a href="?page=<?= $p ?><?= !empty($active_tag) ? '&tag=' . urlencode($active_tag) : '' ?>"
                   class="pagination__link <?= $p === $pagination['current'] ? 'active' : '' ?>"><?= $p ?></a>
                <?php endfor; ?>

                <?php if ($pagination['current'] < $pagination['total_pages']): ?>
                <a href="?page=<?= $pagination['current'] + 1 ?><?= !empty($active_tag) ? '&tag=' . urlencode($active_tag) : '' ?>" class="pagination__link"><?= t('blog.next') ?> &raquo;</a>
                <?php endif; ?>
            </nav>
            <?php endif; ?>

            <?php else: ?>
            <div class="blog-empty" data-animate>
                <p><?= t('blog.no_posts') ?></p>
            </div>
            <?php endif; ?>
        </div>
    </section>
