<?php
$page_title = '404 — ' . t('error.not_found_title');
?>

    <!-- ===== 404 ===== -->
    <section class="page-header page-header--full">
        <div class="page-header__bg">
            <div class="hero__orb hero__orb--1"></div>
            <div class="hero__orb hero__orb--2"></div>
            <div class="hero__grid"></div>
        </div>
        <div class="container page-header__content page-header__content--center">
            <h1 class="page-header__title page-header__title--large animate-in">404</h1>
            <p class="page-header__subtitle animate-in animate-in--delay-1"><?= t('error.not_found_message') ?></p>
            <div class="animate-in animate-in--delay-2">
                <a href="/" class="btn btn--primary"><?= t('error.go_home') ?></a>
            </div>
        </div>
    </section>
