<?php
/**
 * Auto-publish scheduled blog posts.
 *
 * Publishes drafts whose published_at date has passed.
 * Intended for cron: 0 6 * * * www-data php /var/www/cdemsolutions/scripts/auto_publish.php >> storage/cron.log 2>&1
 */

require __DIR__ . '/../lib/database.php';

$pdo = db();
$stmt = $pdo->prepare("
    UPDATE blog_posts
    SET status = 'published', updated_at = datetime('now')
    WHERE status = 'draft'
    AND published_at IS NOT NULL
    AND published_at <= datetime('now')
");
$stmt->execute();
$count = $stmt->rowCount();

if ($count > 0) {
    echo date('Y-m-d H:i:s') . " — Published {$count} scheduled post(s)\n";
}
