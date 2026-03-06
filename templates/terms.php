<?php
$page_title = t('terms.page_title');
$page_description = t('terms.page_description');
?>

    <!-- Schema.org — BreadcrumbList -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "BreadcrumbList",
        "itemListElement": [
            {"@type": "ListItem", "position": 1, "name": "<?= t('nav.home') ?>", "item": "https://cdemsolutions.com/"},
            {"@type": "ListItem", "position": 2, "name": "<?= t('terms.breadcrumb') ?>", "item": "https://cdemsolutions.com/terms/"}
        ]
    }
    </script>

    <!-- ===== PAGE HEADER ===== -->
    <section class="page-header">
        <div class="page-header__bg">
            <div class="page-header__bg-image" style="background-image: url('/img/contact-bg.jpg')"></div>
            <div class="hero__orb hero__orb--1"></div>
            <div class="hero__orb hero__orb--2"></div>
            <div class="hero__grid"></div>
        </div>
        <div class="container page-header__content">
            <nav class="breadcrumb" aria-label="Breadcrumb" data-animate>
                <a href="/"><?= t('nav.home') ?></a>
                <span><?= icon('chevron-right') ?></span>
                <span><?= t('terms.breadcrumb') ?></span>
            </nav>
            <h1 class="page-header__title animate-in"><?= t('terms.page_heading') ?></h1>
            <p class="page-header__subtitle animate-in animate-in--delay-1"><?= t('terms.page_subtitle') ?></p>
        </div>
    </section>

    <!-- ===== TERMS OF SERVICE CONTENT ===== -->
    <section class="section">
        <div class="container">
            <div class="blog-post__layout">
                <div class="blog-post__content" data-animate>
                    <div class="blog-post__body prose">
                        <p><strong>Last updated:</strong> <?= date('F j, Y') ?></p>

                        <h2>1. Acceptance of Terms</h2>
                        <p>By accessing and using the CDEM Solutions website at <a href="https://cdemsolutions.com">cdemsolutions.com</a> (the "Site"), you accept and agree to be bound by these Terms of Service ("Terms"). If you do not agree to these Terms, you should not use the Site.</p>
                        <p>These Terms apply to all visitors, users, and others who access or use the Site.</p>

                        <h2>2. Use of Service</h2>
                        <p>You agree to use the Site only for lawful purposes and in a way that does not infringe the rights of, restrict, or inhibit anyone else's use and enjoyment of the Site. You agree not to:</p>
                        <ul>
                            <li>Use the Site in any way that violates any applicable local, national, or international law or regulation</li>
                            <li>Attempt to gain unauthorized access to any part of the Site, the server on which the Site is stored, or any server, computer, or database connected to the Site</li>
                            <li>Use the Site to transmit, or procure the sending of, any unsolicited or unauthorized advertising or promotional material</li>
                            <li>Use the Site to send, knowingly receive, upload, download, use, or re-use any material that does not comply with these Terms</li>
                            <li>Introduce any viruses, trojans, worms, logic bombs, or other malicious or technologically harmful material</li>
                        </ul>

                        <h2>3. Intellectual Property</h2>
                        <p>The Site and its original content, features, and functionality are and will remain the exclusive property of CDEM Solutions. The Site is protected by copyright, trademark, and other laws of applicable jurisdictions.</p>
                        <p>Our trademarks and trade dress may not be used in connection with any product or service without the prior written consent of CDEM Solutions. Nothing in these Terms grants you any right to use the CDEM Solutions name, logos, domain names, or other distinctive brand features.</p>

                        <h2>4. User Conduct</h2>
                        <p>When using our contact form or any other interactive features of the Site, you agree to:</p>
                        <ul>
                            <li>Provide accurate, current, and complete information</li>
                            <li>Not impersonate any person or entity, or falsely state or misrepresent your affiliation with any person or entity</li>
                            <li>Not submit any content that is unlawful, harmful, threatening, abusive, harassing, defamatory, vulgar, obscene, or otherwise objectionable</li>
                            <li>Not use the Site for any commercial solicitation purposes without our prior written consent</li>
                        </ul>

                        <h2>5. Disclaimers</h2>
                        <p>The information on the Site is provided on an "as is" and "as available" basis. CDEM Solutions makes no warranties, expressed or implied, and hereby disclaims and negates all other warranties, including without limitation:</p>
                        <ul>
                            <li>Implied warranties of merchantability, fitness for a particular purpose, and non-infringement</li>
                            <li>That the Site will be uninterrupted, timely, secure, or error-free</li>
                            <li>That the results obtained from the use of the Site will be accurate or reliable</li>
                            <li>That the quality of any products, services, information, or other material purchased or obtained through the Site will meet your expectations</li>
                        </ul>

                        <h2>6. Limitation of Liability</h2>
                        <p>In no event shall CDEM Solutions, its directors, employees, partners, agents, suppliers, or affiliates be liable for any indirect, incidental, special, consequential, or punitive damages, including without limitation, loss of profits, data, use, goodwill, or other intangible losses, resulting from:</p>
                        <ul>
                            <li>Your access to or use of (or inability to access or use) the Site</li>
                            <li>Any conduct or content of any third party on the Site</li>
                            <li>Any content obtained from the Site</li>
                            <li>Unauthorized access, use, or alteration of your transmissions or content</li>
                        </ul>
                        <p>This limitation applies whether the alleged liability is based on contract, tort, negligence, strict liability, or any other basis, even if CDEM Solutions has been advised of the possibility of such damage.</p>

                        <h2>7. Changes to Terms</h2>
                        <p>We reserve the right, at our sole discretion, to modify or replace these Terms at any time. If a revision is material, we will make reasonable efforts to provide at least 30 days' notice prior to any new terms taking effect.</p>
                        <p>By continuing to access or use our Site after those revisions become effective, you agree to be bound by the revised terms. If you do not agree to the new terms, please stop using the Site.</p>

                        <h2>8. Governing Law</h2>
                        <p>These Terms shall be governed and construed in accordance with the laws of the European Union and applicable national laws, without regard to its conflict of law provisions.</p>
                        <p>Our failure to enforce any right or provision of these Terms will not be considered a waiver of those rights. If any provision of these Terms is held to be invalid or unenforceable by a court, the remaining provisions of these Terms will remain in effect.</p>

                        <h2>Contact Us</h2>
                        <p>If you have any questions about these Terms of Service, please contact us:</p>
                        <ul>
                            <li>By email: <a href="mailto:hello@cdemsolutions.com">hello@cdemsolutions.com</a></li>
                            <li>Through our <a href="/contact/">contact form</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>
