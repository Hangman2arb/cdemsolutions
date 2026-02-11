<?php
$page_title = t('about.page_title');
$page_description = t('about.page_description');
?>

    <!-- Schema.org — BreadcrumbList -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "BreadcrumbList",
        "itemListElement": [
            {"@type": "ListItem", "position": 1, "name": "<?= t('nav.home') ?>", "item": "https://cdemsolutions.com/"},
            {"@type": "ListItem", "position": 2, "name": "<?= t('nav.about') ?>", "item": "https://cdemsolutions.com/about/"}
        ]
    }
    </script>

    <!-- ===== PAGE HEADER ===== -->
    <section class="page-header">
        <div class="page-header__bg">
            <div class="page-header__bg-image" style="background-image: url('/img/about-bg.jpg')"></div>
            <div class="hero__orb hero__orb--1"></div>
            <div class="hero__orb hero__orb--2"></div>
            <div class="hero__grid"></div>
        </div>
        <div class="container page-header__content">
            <nav class="breadcrumb" aria-label="Breadcrumb" data-animate>
                <a href="/"><?= t('nav.home') ?></a>
                <span><?= icon('chevron-right') ?></span>
                <span><?= t('nav.about') ?></span>
            </nav>
            <h1 class="page-header__title animate-in"><?= t('about.page_heading') ?></h1>
            <p class="page-header__subtitle animate-in animate-in--delay-1"><?= t('about.page_subtitle') ?></p>
        </div>
    </section>

    <!-- ===== ABOUT CONTENT ===== -->
    <section class="section">
        <div class="container about__inner--with-image">
            <div class="about__image" data-animate>
                <img src="/img/about-team.jpg" alt="CDEM Solutions Team" loading="lazy">
            </div>
            <div>
                <div class="about__content" data-animate data-delay="100">
                    <span class="section__tag"><?= t('about.tag') ?></span>
                    <h2 class="section__title"><?= t('about.title') ?> <span class="gradient-text"><?= t('about.title_highlight') ?></span></h2>
                    <p class="about__text"><?= t('about.text1') ?></p>
                    <p class="about__text"><?= t('about.text2') ?></p>
                </div>
                <div class="about__stats" data-animate data-delay="300">
                    <?php foreach (t('about.stats') as $stat): ?>
                    <div class="stat">
                        <span class="stat__number" data-count="<?= $stat['number'] ?>" data-suffix="<?= $stat['suffix'] ?>">0<?= $stat['suffix'] ?></span>
                        <span class="stat__label"><?= $stat['label'] ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== WHY US ===== -->
    <section class="section section--alt why-us">
        <div class="container">
            <div class="section__header" data-animate>
                <span class="section__tag"><?= t('why_us.tag') ?></span>
                <h2 class="section__title"><?= t('why_us.title') ?> <span class="gradient-text"><?= t('why_us.title_highlight') ?></span></h2>
                <p class="section__subtitle"><?= t('why_us.subtitle') ?></p>
            </div>
            <div class="why-us__grid">
                <?php foreach (t('why_us.items') as $i => $item): ?>
                <div class="feature-card" data-animate data-delay="<?= $i * 150 ?>">
                    <div class="feature-card__icon"><?= icon($item['icon']) ?></div>
                    <h3 class="feature-card__title"><?= $item['title'] ?></h3>
                    <p class="feature-card__text"><?= $item['description'] ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

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
