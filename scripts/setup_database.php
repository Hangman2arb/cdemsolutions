<?php
/**
 * Database setup script — creates tables and seeds admin user.
 * Run: php scripts/setup_database.php
 */

require __DIR__ . '/../lib/database.php';

$pdo = db();

echo "Setting up CDEM Solutions database...\n\n";

// --- Admin users ---
$pdo->exec("CREATE TABLE IF NOT EXISTS admin_users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    role TEXT NOT NULL DEFAULT 'admin',
    is_locked INTEGER NOT NULL DEFAULT 0,
    locked_until TEXT NULL,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now'))
)");
echo "[OK] admin_users\n";

// --- Login attempts (rate limiting) ---
$pdo->exec("CREATE TABLE IF NOT EXISTS admin_login_attempts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ip_address TEXT NOT NULL,
    username TEXT NULL,
    success INTEGER NOT NULL DEFAULT 0,
    attempted_at TEXT NOT NULL DEFAULT (datetime('now'))
)");
$pdo->exec("CREATE INDEX IF NOT EXISTS idx_login_attempts_ip ON admin_login_attempts(ip_address, attempted_at)");
$pdo->exec("CREATE INDEX IF NOT EXISTS idx_login_attempts_user ON admin_login_attempts(username, attempted_at)");
echo "[OK] admin_login_attempts\n";

// --- Admin settings (key-value store) ---
$pdo->exec("CREATE TABLE IF NOT EXISTS admin_settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    setting_key TEXT NOT NULL UNIQUE,
    setting_value TEXT NULL,
    setting_type TEXT NOT NULL DEFAULT 'string',
    setting_group TEXT NOT NULL DEFAULT 'general',
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now'))
)");
echo "[OK] admin_settings\n";

// --- Leads (contact form submissions) ---
$pdo->exec("CREATE TABLE IF NOT EXISTS leads (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL,
    subject TEXT NOT NULL,
    message TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'new',
    notes TEXT NULL,
    ip_address TEXT NULL,
    user_agent TEXT NULL,
    email_sent INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now'))
)");
$pdo->exec("CREATE INDEX IF NOT EXISTS idx_leads_status ON leads(status)");
$pdo->exec("CREATE INDEX IF NOT EXISTS idx_leads_created ON leads(created_at DESC)");
echo "[OK] leads\n";

// --- Blog posts ---
$pdo->exec("CREATE TABLE IF NOT EXISTS blog_posts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    slug TEXT NOT NULL UNIQUE,
    title TEXT NOT NULL,
    excerpt TEXT NULL,
    content_blocks TEXT NULL,
    content_html TEXT NULL,
    featured_image TEXT NULL,
    status TEXT NOT NULL DEFAULT 'draft',
    meta_title TEXT NULL,
    meta_description TEXT NULL,
    meta_keywords TEXT NULL,
    reading_time INTEGER NOT NULL DEFAULT 5,
    author TEXT NOT NULL DEFAULT 'CDEM Solutions',
    published_at TEXT NULL,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now'))
)");
$pdo->exec("CREATE INDEX IF NOT EXISTS idx_blog_status ON blog_posts(status, published_at DESC)");
$pdo->exec("CREATE INDEX IF NOT EXISTS idx_blog_slug ON blog_posts(slug)");
echo "[OK] blog_posts\n";

// --- Blog tags ---
$pdo->exec("CREATE TABLE IF NOT EXISTS blog_tags (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    slug TEXT NOT NULL UNIQUE,
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
)");
echo "[OK] blog_tags\n";

// --- Blog post-tag relationship ---
$pdo->exec("CREATE TABLE IF NOT EXISTS blog_post_tags (
    post_id INTEGER NOT NULL,
    tag_id INTEGER NOT NULL,
    PRIMARY KEY (post_id, tag_id),
    FOREIGN KEY (post_id) REFERENCES blog_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES blog_tags(id) ON DELETE CASCADE
)");
echo "[OK] blog_post_tags\n";

// --- Blog ideas (topic tracking for content generation) ---
$pdo->exec("CREATE TABLE IF NOT EXISTS blog_ideas (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    source_url TEXT NULL,
    source_title TEXT NOT NULL,
    source_date TEXT NULL,
    source_site TEXT NULL,
    target_service TEXT NULL,
    status TEXT NOT NULL DEFAULT 'pending',
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
)");
echo "[OK] blog_ideas\n";

// --- SEO redirects ---
$pdo->exec("CREATE TABLE IF NOT EXISTS seo_redirects (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    from_path TEXT NOT NULL UNIQUE,
    to_url TEXT NOT NULL,
    redirect_type INTEGER NOT NULL DEFAULT 301,
    is_active INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
)");
$pdo->exec("CREATE INDEX IF NOT EXISTS idx_redirects_active ON seo_redirects(is_active, from_path)");
echo "[OK] seo_redirects\n";

// --- Seed admin user ---
$stmt = $pdo->prepare('SELECT COUNT(*) FROM admin_users WHERE username = :username');
$stmt->execute([':username' => 'admin']);
$exists = (int)$stmt->fetchColumn();

if ($exists === 0) {
    $hash = password_hash('CdemAdmin2026!', PASSWORD_ARGON2ID);
    $stmt = $pdo->prepare('INSERT INTO admin_users (username, password_hash, role) VALUES (:username, :hash, :role)');
    $stmt->execute([
        ':username' => 'admin',
        ':hash' => $hash,
        ':role' => 'admin',
    ]);
    echo "\n[SEED] Admin user created (admin / CdemAdmin2026!)\n";
} else {
    echo "\n[SKIP] Admin user already exists\n";
}

// --- Seed default settings ---
$defaults = [
    ['smtp_host', '', 'string', 'smtp'],
    ['smtp_port', '587', 'integer', 'smtp'],
    ['smtp_user', '', 'string', 'smtp'],
    ['smtp_pass', '', 'string', 'smtp'],
    ['smtp_from', 'noreply@cdemsolutions.com', 'string', 'smtp'],
    ['smtp_from_name', 'CDEM Solutions', 'string', 'smtp'],
    ['contact_to', 'hello@cdemsolutions.com', 'string', 'contact'],
    ['site_name', 'CDEM Solutions', 'string', 'general'],
    ['site_description', 'Empowering Your Digital Future', 'string', 'general'],
    ['seo_title_suffix', ' — CDEM Solutions', 'string', 'seo'],
    ['seo_default_og_image', '/img/logo.png', 'string', 'seo'],
    ['seo_ga_code', '', 'string', 'seo'],
    ['seo_gtm_code', '', 'string', 'seo'],
    ['seo_head_scripts', '', 'string', 'seo'],
    ['seo_body_scripts', '', 'string', 'seo'],
    ['seo_robots_txt', '', 'string', 'seo'],
    ['seo_verification_google', '', 'string', 'seo'],
    ['seo_verification_bing', '', 'string', 'seo'],
];

$insertSetting = $pdo->prepare('INSERT OR IGNORE INTO admin_settings (setting_key, setting_value, setting_type, setting_group) VALUES (?, ?, ?, ?)');
foreach ($defaults as $setting) {
    $insertSetting->execute($setting);
}
echo "[SEED] Default settings created\n";

echo "\nDatabase setup complete!\n";
