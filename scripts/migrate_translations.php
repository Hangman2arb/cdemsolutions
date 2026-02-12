<?php
/**
 * Migration: Add blog_translations table to existing database.
 * Safe to run multiple times (IF NOT EXISTS).
 *
 * Usage: php scripts/migrate_translations.php
 */

require __DIR__ . '/../lib/database.php';

$pdo = db();

echo "Migrating: blog_translations table...\n";

$pdo->exec("CREATE TABLE IF NOT EXISTS blog_translations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    post_id INTEGER NOT NULL,
    lang TEXT NOT NULL,
    slug TEXT NOT NULL UNIQUE,
    title TEXT NOT NULL,
    excerpt TEXT NULL,
    content_html TEXT NULL,
    meta_title TEXT NULL,
    meta_description TEXT NULL,
    meta_keywords TEXT NULL,
    reading_time INTEGER NOT NULL DEFAULT 5,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (post_id) REFERENCES blog_posts(id) ON DELETE CASCADE,
    UNIQUE(post_id, lang)
)");

$pdo->exec("CREATE INDEX IF NOT EXISTS idx_translations_post_lang ON blog_translations(post_id, lang)");
$pdo->exec("CREATE INDEX IF NOT EXISTS idx_translations_slug ON blog_translations(slug)");
$pdo->exec("CREATE INDEX IF NOT EXISTS idx_translations_lang ON blog_translations(lang)");

echo "[OK] blog_translations table created.\n";
echo "Migration complete!\n";
