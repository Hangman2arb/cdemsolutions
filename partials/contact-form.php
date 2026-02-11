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
