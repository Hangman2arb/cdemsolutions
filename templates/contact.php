<?php
$page_title = t('contact.page_title');
$page_description = t('contact.page_description');
?>

    <!-- Schema.org — BreadcrumbList -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "BreadcrumbList",
        "itemListElement": [
            {"@type": "ListItem", "position": 1, "name": "<?= t('nav.home') ?>", "item": "https://cdemsolutions.com/"},
            {"@type": "ListItem", "position": 2, "name": "<?= t('nav.contact') ?>", "item": "https://cdemsolutions.com/contact/"}
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
                <span><?= t('nav.contact') ?></span>
            </nav>
            <h1 class="page-header__title animate-in"><?= t('contact.page_heading') ?></h1>
            <p class="page-header__subtitle animate-in animate-in--delay-1"><?= t('contact.subtitle') ?></p>
        </div>
    </section>

    <!-- ===== CONTACT FORM ===== -->
    <section class="section contact">
        <div class="container">
            <?php require __DIR__ . '/../partials/contact-form.php'; ?>
        </div>
    </section>
