            <div class="admin-title-bar">
                <h1 class="admin-title">Settings</h1>
            </div>

            <div class="admin-filters">
                <a href="/admin/settings/?group=smtp" class="filter-tab <?= $group === 'smtp' ? 'active' : '' ?>">SMTP / Email</a>
                <a href="/admin/settings/?group=contact" class="filter-tab <?= $group === 'contact' ? 'active' : '' ?>">Contact</a>
                <a href="/admin/settings/?group=general" class="filter-tab <?= $group === 'general' ? 'active' : '' ?>">General</a>
                <a href="/admin/settings/?group=security" class="filter-tab <?= $group === 'security' ? 'active' : '' ?>">Security</a>
            </div>

            <?php if ($group === 'security'): ?>
            <!-- Password change -->
            <div class="admin-card">
                <div class="admin-card__header"><h2>Change Password</h2></div>
                <form method="POST" action="/admin/settings/password/">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" required autocomplete="current-password">
                    </div>
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" required autocomplete="new-password" minlength="8">
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password">
                    </div>
                    <button type="submit" class="btn btn--primary btn--small">Change Password</button>
                </form>
            </div>

            <?php else: ?>
            <!-- Key-value settings -->
            <div class="admin-card">
                <div class="admin-card__header"><h2><?= ucfirst($group) ?> Settings</h2></div>
                <form method="POST" action="/admin/settings/save/">
                    <?= csrf_field() ?>
                    <input type="hidden" name="group" value="<?= htmlspecialchars($group) ?>">

                    <?php if (empty($settings)): ?>
                    <p class="admin-card__empty">No settings in this group.</p>
                    <?php else: ?>
                    <?php foreach ($settings as $setting): ?>
                    <div class="form-group">
                        <label for="setting_<?= htmlspecialchars($setting['setting_key']) ?>">
                            <?= ucwords(str_replace('_', ' ', $setting['setting_key'])) ?>
                        </label>
                        <?php if (strpos($setting['setting_key'], 'pass') !== false): ?>
                        <input type="password" id="setting_<?= htmlspecialchars($setting['setting_key']) ?>"
                               name="settings[<?= htmlspecialchars($setting['setting_key']) ?>]"
                               value="<?= htmlspecialchars($setting['setting_value'] ?? '') ?>">
                        <?php else: ?>
                        <input type="text" id="setting_<?= htmlspecialchars($setting['setting_key']) ?>"
                               name="settings[<?= htmlspecialchars($setting['setting_key']) ?>]"
                               value="<?= htmlspecialchars($setting['setting_value'] ?? '') ?>">
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>

                    <button type="submit" class="btn btn--primary btn--small">Save Settings</button>
                </form>
            </div>
            <?php endif; ?>
