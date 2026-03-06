<?php
$page_title = t('privacy.page_title');
$page_description = t('privacy.page_description');
?>

    <!-- Schema.org — BreadcrumbList -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "BreadcrumbList",
        "itemListElement": [
            {"@type": "ListItem", "position": 1, "name": "<?= t('nav.home') ?>", "item": "https://cdemsolutions.com/"},
            {"@type": "ListItem", "position": 2, "name": "<?= t('privacy.breadcrumb') ?>", "item": "https://cdemsolutions.com/privacy/"}
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
                <span><?= t('privacy.breadcrumb') ?></span>
            </nav>
            <h1 class="page-header__title animate-in"><?= t('privacy.page_heading') ?></h1>
            <p class="page-header__subtitle animate-in animate-in--delay-1"><?= t('privacy.page_subtitle') ?></p>
        </div>
    </section>

    <!-- ===== PRIVACY POLICY CONTENT ===== -->
    <section class="section">
        <div class="container">
            <div class="blog-post__layout">
                <div class="blog-post__content" data-animate>
                    <div class="blog-post__body prose">
                        <p><strong>Last updated:</strong> <?= date('F j, Y') ?></p>

                        <h2>1. Information We Collect</h2>
                        <p>At CDEM Solutions ("we," "us," or "our"), we are committed to protecting your privacy. This Privacy Policy explains how we collect, use, and safeguard your information when you visit our website <a href="https://cdemsolutions.com">cdemsolutions.com</a> (the "Site").</p>
                        <p>We may collect the following types of information:</p>
                        <ul>
                            <li><strong>Personal Information:</strong> Name, email address, and any other information you voluntarily provide through our contact form or other interactions.</li>
                            <li><strong>Usage Data:</strong> Information about how you access and use the Site, including your IP address, browser type, operating system, referring URLs, pages viewed, and the dates and times of your visits.</li>
                            <li><strong>Cookies and Tracking Data:</strong> We use cookies and similar tracking technologies to monitor activity on our Site and store certain information.</li>
                        </ul>

                        <h2>2. How We Use Information</h2>
                        <p>We use the information we collect for various purposes, including:</p>
                        <ul>
                            <li>To provide, operate, and maintain our website</li>
                            <li>To respond to your inquiries and fulfill your requests</li>
                            <li>To improve, personalize, and expand our website</li>
                            <li>To understand and analyze how you use our website</li>
                            <li>To develop new products, services, features, and functionality</li>
                            <li>To communicate with you about updates, offers, and promotions</li>
                            <li>To detect and prevent fraud or technical issues</li>
                        </ul>

                        <h2>3. Cookies and Tracking Technologies</h2>
                        <p>We use cookies and similar tracking technologies to track activity on our Site and hold certain information. Cookies are files with a small amount of data which may include an anonymous unique identifier.</p>
                        <p>We use the following types of cookies:</p>
                        <ul>
                            <li><strong>Essential Cookies:</strong> Necessary for the website to function properly (e.g., session management, language preferences).</li>
                            <li><strong>Analytics Cookies:</strong> We use Google Analytics to collect information about how visitors use our Site. Google Analytics uses cookies to collect information such as the number of visitors, the pages they visit, and the time spent on each page. This data is aggregated and anonymous.</li>
                            <li><strong>Advertising Cookies:</strong> We may use Google AdSense or similar services to display advertisements. These services may use cookies to serve ads based on your prior visits to our Site or other websites.</li>
                        </ul>
                        <p>You can instruct your browser to refuse all cookies or to indicate when a cookie is being sent. However, if you do not accept cookies, some portions of our Site may not function properly.</p>

                        <h2>4. Third-Party Services</h2>
                        <p>We may employ third-party companies and individuals to facilitate our services, provide services on our behalf, perform service-related tasks, or assist us in analyzing how our Site is used. These third parties may include:</p>
                        <ul>
                            <li><strong>Google Analytics:</strong> Web analytics service provided by Google, Inc. For more information, visit <a href="https://policies.google.com/privacy" target="_blank" rel="noopener noreferrer">Google's Privacy Policy</a>.</li>
                            <li><strong>Google AdSense:</strong> Advertising service provided by Google, Inc. For more information, visit <a href="https://policies.google.com/technologies/ads" target="_blank" rel="noopener noreferrer">Google's Advertising Policies</a>.</li>
                            <li><strong>Cloudflare:</strong> Web performance and security service. For more information, visit <a href="https://www.cloudflare.com/privacypolicy/" target="_blank" rel="noopener noreferrer">Cloudflare's Privacy Policy</a>.</li>
                        </ul>
                        <p>These third parties have access to your personal data only to perform these tasks on our behalf and are obligated not to disclose or use it for any other purpose.</p>

                        <h2>5. Data Retention</h2>
                        <p>We will retain your personal information only for as long as is necessary for the purposes set out in this Privacy Policy. We will retain and use your information to the extent necessary to comply with our legal obligations, resolve disputes, and enforce our agreements.</p>
                        <p>Contact form submissions are retained for a reasonable period to allow us to respond to your inquiries and for record-keeping purposes.</p>

                        <h2>6. Your Rights (GDPR)</h2>
                        <p>If you are a resident of the European Economic Area (EEA), you have certain data protection rights under the General Data Protection Regulation (GDPR). These include:</p>
                        <ul>
                            <li><strong>Right of Access:</strong> You have the right to request copies of your personal data.</li>
                            <li><strong>Right to Rectification:</strong> You have the right to request that we correct any information you believe is inaccurate or complete information you believe is incomplete.</li>
                            <li><strong>Right to Erasure:</strong> You have the right to request that we erase your personal data, under certain conditions.</li>
                            <li><strong>Right to Restrict Processing:</strong> You have the right to request that we restrict the processing of your personal data, under certain conditions.</li>
                            <li><strong>Right to Data Portability:</strong> You have the right to request that we transfer the data we have collected to another organization, or directly to you, under certain conditions.</li>
                            <li><strong>Right to Object:</strong> You have the right to object to our processing of your personal data, under certain conditions.</li>
                        </ul>
                        <p>To exercise any of these rights, please contact us at <a href="mailto:hello@cdemsolutions.com">hello@cdemsolutions.com</a>. We will respond to your request within 30 days.</p>

                        <h2>7. Children's Privacy</h2>
                        <p>Our Site is not intended for use by children under the age of 16. We do not knowingly collect personal information from children under 16. If we become aware that we have collected personal data from a child under 16 without verification of parental consent, we will take steps to remove that information from our servers.</p>

                        <h2>8. Changes to This Privacy Policy</h2>
                        <p>We may update our Privacy Policy from time to time. We will notify you of any changes by posting the new Privacy Policy on this page and updating the "Last updated" date at the top of this policy.</p>
                        <p>You are advised to review this Privacy Policy periodically for any changes. Changes to this Privacy Policy are effective when they are posted on this page.</p>

                        <h2>Contact Us</h2>
                        <p>If you have any questions about this Privacy Policy, please contact us:</p>
                        <ul>
                            <li>By email: <a href="mailto:hello@cdemsolutions.com">hello@cdemsolutions.com</a></li>
                            <li>Through our <a href="/contact/">contact form</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>
