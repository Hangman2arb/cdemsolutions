    <!-- ===== FOOTER ===== -->
    <footer class="footer">
        <div class="container footer__inner">
            <div class="footer__brand">
                <a href="/" class="navbar__logo">
                    <img src="/img/logo.png" alt="CDEM Solutions" width="36" height="36">
                    <span>CDEM<strong>Solutions</strong></span>
                </a>
                <p><?= t('footer.description') ?></p>
            </div>
            <div class="footer__col">
                <h4><?= t('footer.quick_links') ?></h4>
                <a href="/services/"><?= t('nav.services') ?></a>
                <a href="/about/"><?= t('nav.about') ?></a>
                <a href="/blog/"><?= t('nav.blog') ?></a>
                <a href="/contact/"><?= t('nav.contact') ?></a>
            </div>
            <div class="footer__col">
                <h4><?= t('footer.services_title') ?></h4>
                <?php foreach (array_slice(t('services.items'), 0, 4) as $service): ?>
                <a href="/services/"><?= $service['title'] ?></a>
                <?php endforeach; ?>
            </div>
            <div class="footer__col">
                <h4><?= t('footer.connect') ?></h4>
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
    <div class="cookie-banner" id="cookieBanner">
        <p><?= t('cookie.message') ?></p>
        <div class="cookie-banner__actions">
            <button class="btn btn--small btn--primary" id="cookieAccept"><?= t('cookie.accept') ?></button>
        </div>
    </div>

    <script src="/js/main.js"></script>
    <?php if (!empty($seo_body_scripts)): ?>
    <?= $seo_body_scripts ?>
    <?php endif; ?>
</body>
</html>
