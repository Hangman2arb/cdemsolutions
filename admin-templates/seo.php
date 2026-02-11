            <div class="admin-title-bar">
                <h1 class="admin-title">SEO Manager</h1>
            </div>

            <div class="admin-filters">
                <a href="/admin/seo/?tab=general" class="filter-tab <?= $seo_tab === 'general' ? 'active' : '' ?>">Global SEO</a>
                <a href="/admin/seo/?tab=scripts" class="filter-tab <?= $seo_tab === 'scripts' ? 'active' : '' ?>">Scripts & Analytics</a>
                <a href="/admin/seo/?tab=robots" class="filter-tab <?= $seo_tab === 'robots' ? 'active' : '' ?>">Robots.txt</a>
                <a href="/admin/seo/?tab=redirects" class="filter-tab <?= $seo_tab === 'redirects' ? 'active' : '' ?>">Redirects</a>
            </div>

            <?php if ($seo_tab === 'general'): ?>
            <!-- Global SEO settings -->
            <div class="admin-card">
                <div class="admin-card__header"><h2>Global SEO Settings</h2></div>
                <form method="POST" action="/admin/seo/save/">
                    <?= csrf_field() ?>
                    <input type="hidden" name="tab" value="general">

                    <div class="form-group">
                        <label for="seo_title_suffix">Title Suffix</label>
                        <input type="text" id="seo_title_suffix" name="settings[seo_title_suffix]"
                               value="<?= htmlspecialchars(get_setting('seo_title_suffix') ?? ' — CDEM Solutions') ?>"
                               placeholder=" — CDEM Solutions">
                        <small class="form-help">Appended to all page titles (e.g., "Services — CDEM Solutions")</small>
                    </div>

                    <div class="form-group">
                        <label for="seo_default_og_image">Default OG Image URL</label>
                        <input type="text" id="seo_default_og_image" name="settings[seo_default_og_image]"
                               value="<?= htmlspecialchars(get_setting('seo_default_og_image') ?? '/img/logo.png') ?>"
                               placeholder="/img/logo.png">
                        <small class="form-help">Default image for social sharing when no page-specific image is set</small>
                    </div>

                    <div class="form-group">
                        <label for="seo_verification_google">Google Search Console Verification</label>
                        <input type="text" id="seo_verification_google" name="settings[seo_verification_google]"
                               value="<?= htmlspecialchars(get_setting('seo_verification_google') ?? '') ?>"
                               placeholder="google-site-verification content value">
                        <small class="form-help">Content value from Google Search Console meta tag</small>
                    </div>

                    <div class="form-group">
                        <label for="seo_verification_bing">Bing Webmaster Verification</label>
                        <input type="text" id="seo_verification_bing" name="settings[seo_verification_bing]"
                               value="<?= htmlspecialchars(get_setting('seo_verification_bing') ?? '') ?>"
                               placeholder="msvalidate.01 content value">
                        <small class="form-help">Content value from Bing Webmaster Tools meta tag</small>
                    </div>

                    <button type="submit" class="btn btn--primary btn--small">Save SEO Settings</button>
                </form>
            </div>

            <div class="admin-card" style="margin-top: 1.5rem;">
                <div class="admin-card__header"><h2>Quick Links</h2></div>
                <div style="padding: 1rem; display: flex; gap: 1rem; flex-wrap: wrap;">
                    <a href="/sitemap.xml" target="_blank" class="btn btn--outline btn--small">View Sitemap</a>
                    <a href="/robots.txt" target="_blank" class="btn btn--outline btn--small">View Robots.txt</a>
                </div>
            </div>

            <?php elseif ($seo_tab === 'scripts'): ?>
            <!-- Scripts & Analytics -->
            <div class="admin-card">
                <div class="admin-card__header"><h2>Analytics</h2></div>
                <form method="POST" action="/admin/seo/save/">
                    <?= csrf_field() ?>
                    <input type="hidden" name="tab" value="scripts">

                    <div class="form-group">
                        <label for="seo_ga_code">Google Analytics ID</label>
                        <input type="text" id="seo_ga_code" name="settings[seo_ga_code]"
                               value="<?= htmlspecialchars(get_setting('seo_ga_code') ?? '') ?>"
                               placeholder="G-XXXXXXXXXX">
                        <small class="form-help">Google Analytics 4 measurement ID</small>
                    </div>

                    <div class="form-group">
                        <label for="seo_gtm_code">Google Tag Manager ID</label>
                        <input type="text" id="seo_gtm_code" name="settings[seo_gtm_code]"
                               value="<?= htmlspecialchars(get_setting('seo_gtm_code') ?? '') ?>"
                               placeholder="GTM-XXXXXXX">
                        <small class="form-help">GTM container ID — injected in &lt;head&gt; and &lt;body&gt;</small>
                    </div>

                    <div class="form-group">
                        <label for="seo_head_scripts">Custom Head Scripts</label>
                        <textarea id="seo_head_scripts" name="settings[seo_head_scripts]" rows="6"
                                  placeholder="<!-- Paste any scripts here (e.g., schema markup, verification tags, pixels) -->"><?= htmlspecialchars(get_setting('seo_head_scripts') ?? '') ?></textarea>
                        <small class="form-help">Injected before &lt;/head&gt; on every page. Use for verification meta tags, tracking pixels, etc.</small>
                    </div>

                    <div class="form-group">
                        <label for="seo_body_scripts">Custom Body Scripts</label>
                        <textarea id="seo_body_scripts" name="settings[seo_body_scripts]" rows="6"
                                  placeholder="<!-- Paste any scripts here (e.g., chat widgets, conversion scripts) -->"><?= htmlspecialchars(get_setting('seo_body_scripts') ?? '') ?></textarea>
                        <small class="form-help">Injected before &lt;/body&gt; on every page. Use for chat widgets, noscript pixels, etc.</small>
                    </div>

                    <button type="submit" class="btn btn--primary btn--small">Save Scripts</button>
                </form>
            </div>

            <?php elseif ($seo_tab === 'robots'): ?>
            <!-- Robots.txt editor -->
            <div class="admin-card">
                <div class="admin-card__header"><h2>Robots.txt Editor</h2></div>
                <form method="POST" action="/admin/seo/save/">
                    <?= csrf_field() ?>
                    <input type="hidden" name="tab" value="robots">

                    <div class="form-group">
                        <label for="seo_robots_txt">Robots.txt Content</label>
                        <textarea id="seo_robots_txt" name="settings[seo_robots_txt]" rows="12"
                                  style="font-family: monospace; font-size: 0.85rem;"
                                  placeholder="Leave empty to use the default robots.txt"><?= htmlspecialchars(get_setting('seo_robots_txt') ?? '') ?></textarea>
                        <small class="form-help">
                            Leave empty to use the auto-generated default. The default blocks /admin/ and /api/ and includes the sitemap URL.
                            <a href="/robots.txt" target="_blank">Preview current robots.txt</a>
                        </small>
                    </div>

                    <button type="submit" class="btn btn--primary btn--small">Save Robots.txt</button>
                </form>
            </div>

            <?php elseif ($seo_tab === 'redirects'): ?>
            <!-- 301/302 Redirect Manager -->
            <div class="admin-card">
                <div class="admin-card__header">
                    <h2>301/302 Redirects</h2>
                </div>

                <!-- Add new redirect -->
                <form method="POST" action="/admin/seo/redirects/save/" style="padding: 1rem; border-bottom: 1px solid var(--admin-border);">
                    <?= csrf_field() ?>
                    <div style="display: grid; grid-template-columns: 1fr 1fr auto auto; gap: 0.75rem; align-items: end;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="from_path">From Path</label>
                            <input type="text" id="from_path" name="from_path" placeholder="/old-page/" required>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="to_url">To URL</label>
                            <input type="text" id="to_url" name="to_url" placeholder="/new-page/ or https://..." required>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="redirect_type">Type</label>
                            <select id="redirect_type" name="redirect_type">
                                <option value="301">301 (Permanent)</option>
                                <option value="302">302 (Temporary)</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn--primary btn--small">Add</button>
                    </div>
                </form>

                <!-- Existing redirects -->
                <?php if (!empty($redirects)): ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>From</th>
                            <th>To</th>
                            <th>Type</th>
                            <th>Active</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($redirects as $r): ?>
                        <tr>
                            <td><code><?= htmlspecialchars($r['from_path']) ?></code></td>
                            <td><code><?= htmlspecialchars($r['to_url']) ?></code></td>
                            <td><span class="badge badge--<?= $r['redirect_type'] == 301 ? 'success' : 'warning' ?>"><?= $r['redirect_type'] ?></span></td>
                            <td><span class="badge badge--<?= $r['is_active'] ? 'success' : 'secondary' ?>"><?= $r['is_active'] ? 'Yes' : 'No' ?></span></td>
                            <td>
                                <form method="POST" action="/admin/seo/redirects/delete/" style="display:inline;">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                    <button type="submit" class="btn btn--danger btn--small" onclick="return confirm('Delete this redirect?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p class="admin-card__empty">No redirects configured.</p>
                <?php endif; ?>
            </div>
            <?php endif; ?>
