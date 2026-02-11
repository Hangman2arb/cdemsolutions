<?php
/** Home page — keeps all original sections */

$service_images = [
    'brain'    => '/img/service-ai.jpg',
    'code'     => '/img/service-software.jpg',
    'cloud'    => '/img/service-cloud.jpg',
    'strategy' => '/img/service-consulting.jpg',
    'shield'   => '/img/service-security.jpg',
    'chart'    => '/img/service-data.jpg',
];
?>

    <!-- ===== HERO ===== -->
    <section class="hero" id="hero">
        <div class="hero__image" style="background-image: url('/img/hero-bg.jpg')"></div>
        <div class="hero__bg">
            <div class="hero__orb hero__orb--1"></div>
            <div class="hero__orb hero__orb--2"></div>
            <div class="hero__orb hero__orb--3"></div>
            <div class="hero__grid"></div>
        </div>
        <div class="container hero__content">
            <h1 class="hero__title animate-in">
                <?= t('hero.title') ?><br>
                <span class="gradient-text"><?= t('hero.title_highlight') ?></span>
            </h1>
            <p class="hero__subtitle animate-in animate-in--delay-1"><?= t('hero.subtitle') ?></p>
            <div class="hero__cta animate-in animate-in--delay-2">
                <a href="/contact/" class="btn btn--primary"><?= t('hero.cta_primary') ?></a>
                <a href="/services/" class="btn btn--outline"><?= t('hero.cta_secondary') ?></a>
            </div>
        </div>
        <div class="hero__scroll">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M19 12l-7 7-7-7"/></svg>
        </div>
    </section>

    <!-- ===== SERVICES ===== -->
    <section class="section services" id="services">
        <div class="container">
            <div class="section__header" data-animate>
                <span class="section__tag"><?= t('services.tag') ?></span>
                <h2 class="section__title"><?= t('services.title') ?> <span class="gradient-text"><?= t('services.title_highlight') ?></span></h2>
                <p class="section__subtitle"><?= t('services.subtitle') ?></p>
            </div>
            <div class="services__grid">
                <?php foreach (t('services.items') as $i => $service): ?>
                <div class="service-card" data-animate data-delay="<?= $i * 100 ?>">
                    <?php $img = $service_images[$service['icon']] ?? ''; ?>
                    <?php if ($img): ?>
                    <div class="service-card__image">
                        <img src="<?= $img ?>" alt="<?= htmlspecialchars($service['title']) ?>" loading="lazy">
                    </div>
                    <div class="service-card__body">
                        <div class="service-card__icon"><?= icon($service['icon']) ?></div>
                        <h3 class="service-card__title"><?= $service['title'] ?></h3>
                        <p class="service-card__text"><?= $service['description'] ?></p>
                    </div>
                    <?php else: ?>
                    <div class="service-card__icon"><?= icon($service['icon']) ?></div>
                    <h3 class="service-card__title"><?= $service['title'] ?></h3>
                    <p class="service-card__text"><?= $service['description'] ?></p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ===== ABOUT ===== -->
    <section class="section section--alt about" id="about">
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
    <section class="section why-us" id="why-us">
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

    <!-- ===== TECH STACK ===== -->
    <?php require __DIR__ . '/../partials/tech-stack.php'; ?>

    <!-- ===== CTA BANNER ===== -->
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

    <!-- ===== CONTACT ===== -->
    <section class="section contact" id="contact">
        <div class="container">
            <div class="section__header" data-animate>
                <span class="section__tag"><?= t('contact.tag') ?></span>
                <h2 class="section__title"><?= t('contact.title') ?> <span class="gradient-text"><?= t('contact.title_highlight') ?></span></h2>
                <p class="section__subtitle"><?= t('contact.subtitle') ?></p>
            </div>
            <?php require __DIR__ . '/../partials/contact-form.php'; ?>
        </div>
    </section>
