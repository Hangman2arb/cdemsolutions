    <!-- ===== FOOTER ===== -->
    <footer class="footer">
        <div class="container footer__inner">
            <div class="footer__brand">
                <a href="/" class="navbar__logo">
                    <?= picture('/img/logo.png', 'CDEM Solutions', ['width' => 88, 'height' => 88, 'loading' => 'lazy']) ?>
                    <span>CDEM<strong>Solutions</strong></span>
                </a>
                <p><?= t('footer.description') ?></p>
            </div>
            <div class="footer__col">
                <p class="footer__heading"><?= t('footer.quick_links') ?></p>
                <a href="/services/"><?= t('nav.services') ?></a>
                <a href="/about/"><?= t('nav.about') ?></a>
                <a href="/blog/"><?= t('nav.blog') ?></a>
                <a href="/contact/"><?= t('nav.contact') ?></a>
            </div>
            <div class="footer__col">
                <p class="footer__heading"><?= t('footer.services_title') ?></p>
                <?php foreach (array_slice(t('services.items'), 0, 4) as $service): ?>
                <a href="/services/"><?= $service['title'] ?></a>
                <?php endforeach; ?>
            </div>
            <div class="footer__col">
                <p class="footer__heading"><?= t('footer.connect') ?></p>
                <a href="mailto:hello@cdemsolutions.com">hello@cdemsolutions.com</a>
                <a href="/blog/"><?= t('nav.blog') ?></a>
                <a href="/set-lang/<?= $otherLang ?>/" rel="nofollow"><?= t('lang_switch_label') ?></a>
            </div>
        </div>
        <div class="footer__bottom">
            <div class="container footer__bottom-inner">
                <span><?= t('footer.copyright') ?></span>
                <div class="footer__legal">
                    <a href="#"><?= t('footer.privacy') ?></a>
                    <a href="#"><?= t('footer.terms') ?></a>
                </div>
            </div>
        </div>
    </footer>

    <!-- ===== COOKIE BANNER ===== -->
    <?php if (empty($_COOKIE['cookies_accepted'])): ?>
    <div class="cookie-banner cookie-banner--visible">
        <p><?= t('cookie.message') ?></p>
        <form action="/accept-cookies/" method="POST" class="cookie-banner__actions">
            <button type="submit" class="btn btn--small btn--primary"><?= t('cookie.accept') ?></button>
        </form>
    </div>
    <?php endif; ?>

    <?php $js_ver = filemtime(__DIR__ . '/../public/js/main.js'); ?>
    <script src="/js/main.js?v=<?= $js_ver ?>" defer></script>
    <?php if (!empty($seo_body_scripts)): ?>
    <?= $seo_body_scripts ?>
    <?php endif; ?>
</body>
</html>
