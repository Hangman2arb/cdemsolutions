<?php
$page_title = t('services.page_title');
$page_description = t('services.page_description');

$service_images = [
    'brain'    => '/img/service-ai.jpg',
    'code'     => '/img/service-software.jpg',
    'cloud'    => '/img/service-cloud.jpg',
    'strategy' => '/img/service-consulting.jpg',
    'shield'   => '/img/service-security.jpg',
    'chart'    => '/img/service-data.jpg',
];
?>

    <!-- Schema.org — BreadcrumbList -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "BreadcrumbList",
        "itemListElement": [
            {"@type": "ListItem", "position": 1, "name": "<?= t('nav.home') ?>", "item": "https://cdemsolutions.com/"},
            {"@type": "ListItem", "position": 2, "name": "<?= t('nav.services') ?>", "item": "https://cdemsolutions.com/services/"}
        ]
    }
    </script>

    <!-- ===== PAGE HEADER ===== -->
    <section class="page-header">
        <div class="page-header__bg">
            <div class="page-header__bg-image" style="background-image: url('/img/services-bg.jpg')"></div>
            <div class="hero__orb hero__orb--1"></div>
            <div class="hero__orb hero__orb--2"></div>
            <div class="hero__grid"></div>
        </div>
        <div class="container page-header__content">
            <nav class="breadcrumb" aria-label="Breadcrumb" data-animate>
                <a href="/"><?= t('nav.home') ?></a>
                <span><?= icon('chevron-right') ?></span>
                <span><?= t('nav.services') ?></span>
            </nav>
            <h1 class="page-header__title animate-in"><?= t('services.page_heading') ?></h1>
            <p class="page-header__subtitle animate-in animate-in--delay-1"><?= t('services.subtitle') ?></p>
        </div>
    </section>

    <!-- ===== SERVICES GRID ===== -->
    <section class="section services">
        <div class="container">
            <div class="services__grid">
                <?php foreach (t('services.items') as $i => $service): ?>
                <div class="service-card service-card--expanded" data-animate data-delay="<?= $i * 100 ?>">
                    <?php $img = $service_images[$service['icon']] ?? ''; ?>
                    <?php if ($img): ?>
                    <div class="service-card__image">
                        <img src="<?= $img ?>" alt="<?= htmlspecialchars($service['title']) ?>" loading="lazy">
                    </div>
                    <?php endif; ?>
                    <div class="service-card__body">
                        <div class="service-card__icon"><?= icon($service['icon']) ?></div>
                        <h3 class="service-card__title"><?= $service['title'] ?></h3>
                        <p class="service-card__text"><?= $service['description'] ?></p>
                        <?php if (!empty($service['features'])): ?>
                        <ul class="service-card__features">
                            <?php foreach ($service['features'] as $feature): ?>
                            <li><?= $feature ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <?php endif; ?>
                    </div>
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
