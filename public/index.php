<?php
session_start();

// --- Language Detection ---
$supported = ['en', 'es'];
$default = 'en';

if (isset($_GET['lang']) && in_array($_GET['lang'], $supported)) {
    $lang = $_GET['lang'];
    $_SESSION['lang'] = $lang;
    setcookie('lang', $lang, time() + 86400 * 365, '/');
} elseif (isset($_SESSION['lang'])) {
    $lang = $_SESSION['lang'];
} elseif (isset($_COOKIE['lang'])) {
    $lang = $_COOKIE['lang'];
} else {
    $lang = $default;
    if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        $browserLang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
        if (in_array($browserLang, $supported)) {
            $lang = $browserLang;
        }
    }
}

if (!in_array($lang, $supported)) {
    $lang = $default;
}

$t = require __DIR__ . '/../lang/' . $lang . '.php';

function t($key) {
    global $t;
    $keys = explode('.', $key);
    $value = $t;
    foreach ($keys as $k) {
        if (is_array($value) && isset($value[$k])) {
            $value = $value[$k];
        } else {
            return $key;
        }
    }
    return $value;
}

$otherLang = $lang === 'en' ? 'es' : 'en';

// --- SVG Icons ---
$icons = [
    'brain' => '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a4 4 0 0 1 4 4c0 .6-.1 1.1-.4 1.6A4 4 0 0 1 18 11a4 4 0 0 1-1.5 3.1A4 4 0 0 1 14 18h-1v4"/><path d="M12 2a4 4 0 0 0-4 4c0 .6.1 1.1.4 1.6A4 4 0 0 0 6 11a4 4 0 0 0 1.5 3.1A4 4 0 0 0 10 18h1"/><path d="M12 8v2"/><path d="M9 11h6"/></svg>',
    'code' => '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/><line x1="14" y1="4" x2="10" y2="20"/></svg>',
    'cloud' => '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 10h-1.26A8 8 0 1 0 9 20h9a5 5 0 0 0 0-10z"/><polyline points="12 13 12 7"/><polyline points="9 10 12 7 15 10"/></svg>',
    'strategy' => '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>',
    'shield' => '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>',
    'chart' => '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/><line x1="2" y1="20" x2="22" y2="20"/></svg>',
    'lightbulb' => '<svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18h6"/><path d="M10 22h4"/><path d="M12 2a7 7 0 0 0-4 12.7V17h8v-2.3A7 7 0 0 0 12 2z"/></svg>',
    'sync' => '<svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10"/><path d="M20.49 15a9 9 0 0 1-14.85 3.36L1 14"/></svg>',
    'rocket' => '<svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"/><path d="M12 15l-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"/><path d="M9 12H4s.55-3.03 2-4c1.62-1.08 5 0 5 0"/><path d="M12 15v5s3.03-.55 4-2c1.08-1.62 0-5 0-5"/></svg>',
    'target' => '<svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>',
];

function icon($name) {
    global $icons;
    return $icons[$name] ?? '';
}

// --- Handle contact form (AJAX) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'contact') {
    header('Content-Type: application/json');
    $name = htmlspecialchars(trim($_POST['name'] ?? ''));
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $subject = htmlspecialchars(trim($_POST['subject'] ?? ''));
    $message = htmlspecialchars(trim($_POST['message'] ?? ''));

    if (!$name || !$email || !$subject || !$message) {
        echo json_encode(['success' => false, 'error' => 'All fields are required.']);
        exit;
    }

    // Load SMTP config (server-only file, not in git)
    $configFile = __DIR__ . '/../config.php';
    $autoload = __DIR__ . '/../vendor/autoload.php';

    if (file_exists($configFile) && file_exists($autoload)) {
        $config = require $configFile;
        require $autoload;

        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = $config['smtp_host'];
            $mail->SMTPAuth = true;
            $mail->Username = $config['smtp_user'];
            $mail->Password = $config['smtp_pass'];
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $config['smtp_port'];
            $mail->CharSet = 'UTF-8';

            $mail->setFrom($config['smtp_from'], $config['smtp_from_name']);
            $mail->addAddress($config['contact_to']);
            $mail->addReplyTo($email, $name);

            $mail->isHTML(false);
            $mail->Subject = "Contact Form: $subject";
            $mail->Body = "Name: $name\nEmail: $email\nSubject: $subject\n\n$message";

            $mail->send();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Mail error.']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Mail not configured.']);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('meta.title') ?></title>
    <meta name="description" content="<?= t('meta.description') ?>">
    <meta name="keywords" content="<?= t('meta.keywords') ?>">

    <!-- Open Graph -->
    <meta property="og:title" content="<?= t('meta.title') ?>">
    <meta property="og:description" content="<?= t('meta.description') ?>">
    <meta property="og:image" content="/img/logo.png">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://cdemsolutions.com">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/img/logo.png">
    <link rel="apple-touch-icon" href="/img/logo.png">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Styles -->
    <link rel="stylesheet" href="/css/style.css">

    <!-- Schema.org -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Organization",
        "name": "CDEM Solutions",
        "url": "https://cdemsolutions.com",
        "logo": "https://cdemsolutions.com/img/logo.png",
        "description": "<?= t('meta.description') ?>",
        "contactPoint": {
            "@type": "ContactPoint",
            "email": "hello@cdemsolutions.com",
            "contactType": "customer service"
        }
    }
    </script>
</head>
<body>

    <!-- ===== NAVBAR ===== -->
    <nav class="navbar" id="navbar">
        <div class="container navbar__inner">
            <a href="/" class="navbar__logo">
                <img src="/img/logo.png" alt="CDEM Solutions" width="44" height="44">
                <span>CDEM<strong>Solutions</strong></span>
            </a>
            <div class="navbar__links" id="navLinks">
                <a href="#services"><?= t('nav.services') ?></a>
                <a href="#about"><?= t('nav.about') ?></a>
                <a href="#why-us"><?= t('nav.why_us') ?></a>
                <a href="#contact"><?= t('nav.contact') ?></a>
                <a href="?lang=<?= $otherLang ?>" class="navbar__lang" title="<?= t('lang_switch_label') ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                    <?= t('lang_switch') ?>
                </a>
            </div>
            <button class="navbar__toggle" id="navToggle" aria-label="Menu">
                <span></span><span></span><span></span>
            </button>
        </div>
    </nav>

    <!-- ===== HERO ===== -->
    <section class="hero" id="hero">
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
                <a href="#contact" class="btn btn--primary"><?= t('hero.cta_primary') ?></a>
                <a href="#services" class="btn btn--outline"><?= t('hero.cta_secondary') ?></a>
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
                    <div class="service-card__icon"><?= icon($service['icon']) ?></div>
                    <h3 class="service-card__title"><?= $service['title'] ?></h3>
                    <p class="service-card__text"><?= $service['description'] ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ===== ABOUT ===== -->
    <section class="section section--alt about" id="about">
        <div class="container about__inner">
            <div class="about__content" data-animate>
                <span class="section__tag"><?= t('about.tag') ?></span>
                <h2 class="section__title"><?= t('about.title') ?> <span class="gradient-text"><?= t('about.title_highlight') ?></span></h2>
                <p class="about__text"><?= t('about.text1') ?></p>
                <p class="about__text"><?= t('about.text2') ?></p>
            </div>
            <div class="about__stats" data-animate data-delay="200">
                <?php foreach (t('about.stats') as $stat): ?>
                <div class="stat">
                    <span class="stat__number" data-count="<?= $stat['number'] ?>" data-suffix="<?= $stat['suffix'] ?>">0<?= $stat['suffix'] ?></span>
                    <span class="stat__label"><?= $stat['label'] ?></span>
                </div>
                <?php endforeach; ?>
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

    <!-- ===== CTA BANNER ===== -->
    <section class="cta-banner">
        <div class="cta-banner__bg">
            <div class="hero__orb hero__orb--1"></div>
            <div class="hero__orb hero__orb--2"></div>
        </div>
        <div class="container cta-banner__inner" data-animate>
            <h2 class="cta-banner__title"><?= t('cta.title') ?></h2>
            <p class="cta-banner__subtitle"><?= t('cta.subtitle') ?></p>
            <a href="#contact" class="btn btn--accent"><?= t('cta.button') ?></a>
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
            <div class="contact__inner">
                <form class="contact__form" id="contactForm" data-animate>
                    <div class="form-row">
                        <div class="form-group">
                            <input type="text" name="name" placeholder="<?= t('contact.form.name') ?>" required>
                        </div>
                        <div class="form-group">
                            <input type="email" name="email" placeholder="<?= t('contact.form.email') ?>" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <input type="text" name="subject" placeholder="<?= t('contact.form.subject') ?>" required>
                    </div>
                    <div class="form-group">
                        <textarea name="message" rows="5" placeholder="<?= t('contact.form.message') ?>" required></textarea>
                    </div>
                    <button type="submit" class="btn btn--primary btn--full">
                        <span class="btn__text"><?= t('contact.form.send') ?></span>
                        <span class="btn__loading"><?= t('contact.form.sending') ?></span>
                    </button>
                    <div class="form-feedback form-feedback--success"><?= t('contact.form.success') ?></div>
                    <div class="form-feedback form-feedback--error"><?= t('contact.form.error') ?></div>
                </form>
                <div class="contact__info" data-animate data-delay="200">
                    <div class="contact__info-item">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                        <div>
                            <strong><?= t('contact.info.email_label') ?></strong>
                            <a href="mailto:<?= t('contact.info.email') ?>"><?= t('contact.info.email') ?></a>
                        </div>
                    </div>
                    <div class="contact__info-item">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                        <div>
                            <strong><?= t('contact.info.location_label') ?></strong>
                            <span><?= t('contact.info.location') ?></span>
                        </div>
                    </div>
                    <div class="contact__info-item">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></svg>
                        <div>
                            <strong><?= t('contact.info.social_label') ?></strong>
                            <span>LinkedIn / GitHub</span>
                        </div>
                    </div>
                    <div class="contact__trust">
                        <div class="contact__trust-item">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>
                            <span>SSL Secured</span>
                        </div>
                        <div class="contact__trust-item">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            <span>GDPR Compliant</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

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
                <a href="#services"><?= t('nav.services') ?></a>
                <a href="#about"><?= t('nav.about') ?></a>
                <a href="#why-us"><?= t('nav.why_us') ?></a>
                <a href="#contact"><?= t('nav.contact') ?></a>
            </div>
            <div class="footer__col">
                <h4><?= t('footer.services_title') ?></h4>
                <?php foreach (array_slice(t('services.items'), 0, 4) as $service): ?>
                <a href="#services"><?= $service['title'] ?></a>
                <?php endforeach; ?>
            </div>
            <div class="footer__col">
                <h4><?= t('footer.connect') ?></h4>
                <a href="mailto:hello@cdemsolutions.com">hello@cdemsolutions.com</a>
                <a href="?lang=<?= $otherLang ?>"><?= t('lang_switch_label') ?></a>
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
</body>
</html>
