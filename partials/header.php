    <!-- ===== NAVBAR ===== -->
    <nav class="navbar" id="navbar">
        <div class="container navbar__inner">
            <a href="/" class="navbar__logo">
                <?= picture('/img/logo.png', 'CDEM Solutions', ['width' => 88, 'height' => 88]) ?>
                <span>CDEM<strong>Solutions</strong></span>
            </a>
            <div class="navbar__links" id="navLinks">
                <a href="/services/" class="<?= is_active('/services') ? 'active' : '' ?>"><?= t('nav.services') ?></a>
                <a href="/about/" class="<?= is_active('/about') ? 'active' : '' ?>"><?= t('nav.about') ?></a>
                <a href="/blog/" class="<?= is_active('/blog') ? 'active' : '' ?>"><?= t('nav.blog') ?></a>
                <a href="/contact/" class="<?= is_active('/contact') ? 'active' : '' ?>"><?= t('nav.contact') ?></a>
                <a href="/set-lang/<?= $otherLang ?>/" class="navbar__lang" title="<?= t('lang_switch_label') ?>" rel="nofollow">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                    <?= t('lang_switch') ?>
                </a>
            </div>
            <button class="navbar__toggle" id="navToggle" aria-label="Menu">
                <span></span><span></span><span></span>
            </button>
        </div>
    </nav>
