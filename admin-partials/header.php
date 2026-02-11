    <main class="admin-main">
        <header class="admin-header">
            <button class="admin-header__toggle" id="sidebarToggle">
                <span></span><span></span><span></span>
            </button>
            <div class="admin-header__right">
                <span class="admin-header__user"><?= htmlspecialchars(admin_user()['username'] ?? 'Admin') ?></span>
            </div>
        </header>
        <?php require __DIR__ . '/flash.php'; ?>
        <div class="admin-content">
