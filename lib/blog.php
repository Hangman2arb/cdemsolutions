<?php
/**
 * Blog CRUD — posts, tags, slugify, reading time.
 */

require_once __DIR__ . '/database.php';

// --- Slugify ---

function blog_slugify(string $text): string {
    $text = mb_strtolower($text, 'UTF-8');
    // Transliterate common accented characters
    $map = ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n','ü'=>'u',
            'à'=>'a','è'=>'e','ì'=>'i','ò'=>'o','ù'=>'u','ä'=>'a','ö'=>'o',
            'ç'=>'c','ß'=>'ss'];
    $text = strtr($text, $map);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

// --- Reading time ---

function blog_reading_time(string $html): int {
    $text = strip_tags($html);
    $wordCount = str_word_count($text);
    return max(1, (int)ceil($wordCount / 200));
}

// --- Posts CRUD ---

function blog_create(array $data): int {
    $pdo = db();

    // Ensure unique slug
    $data['slug'] = blog_ensure_unique_slug($data['slug'] ?: blog_slugify($data['title']));

    if ($data['status'] === 'published' && empty($data['published_at'])) {
        $data['published_at'] = date('Y-m-d H:i:s');
    }

    $stmt = $pdo->prepare("INSERT INTO blog_posts
        (slug, title, excerpt, content_html, featured_image, status, meta_title, meta_description, meta_keywords, reading_time, author, published_at)
        VALUES (:slug, :title, :excerpt, :content_html, :featured_image, :status, :meta_title, :meta_description, :meta_keywords, :reading_time, :author, :published_at)");

    $stmt->execute([
        ':slug' => $data['slug'],
        ':title' => $data['title'],
        ':excerpt' => $data['excerpt'] ?? '',
        ':content_html' => $data['content_html'] ?? '',
        ':featured_image' => $data['featured_image'] ?? '',
        ':status' => $data['status'] ?? 'draft',
        ':meta_title' => $data['meta_title'] ?? '',
        ':meta_description' => $data['meta_description'] ?? '',
        ':meta_keywords' => $data['meta_keywords'] ?? '',
        ':reading_time' => $data['reading_time'] ?? 5,
        ':author' => $data['author'] ?? 'CDEM Solutions',
        ':published_at' => $data['published_at'] ?? null,
    ]);

    return (int)$pdo->lastInsertId();
}

function blog_update(int $id, array $data): void {
    $pdo = db();

    // Check if status changed to published
    $current = blog_get($id);
    if ($data['status'] === 'published' && (!$current || $current['status'] !== 'published')) {
        $data['published_at'] = date('Y-m-d H:i:s');
    } elseif (isset($current['published_at'])) {
        $data['published_at'] = $current['published_at'];
    }

    // Ensure unique slug (excluding current post)
    $data['slug'] = blog_ensure_unique_slug($data['slug'] ?: blog_slugify($data['title']), $id);

    $stmt = $pdo->prepare("UPDATE blog_posts SET
        slug = :slug, title = :title, excerpt = :excerpt, content_html = :content_html,
        featured_image = :featured_image, status = :status, meta_title = :meta_title,
        meta_description = :meta_description, meta_keywords = :meta_keywords,
        reading_time = :reading_time, author = :author, published_at = :published_at,
        updated_at = datetime('now')
        WHERE id = :id");

    $stmt->execute([
        ':id' => $id,
        ':slug' => $data['slug'],
        ':title' => $data['title'],
        ':excerpt' => $data['excerpt'] ?? '',
        ':content_html' => $data['content_html'] ?? '',
        ':featured_image' => $data['featured_image'] ?? '',
        ':status' => $data['status'] ?? 'draft',
        ':meta_title' => $data['meta_title'] ?? '',
        ':meta_description' => $data['meta_description'] ?? '',
        ':meta_keywords' => $data['meta_keywords'] ?? '',
        ':reading_time' => $data['reading_time'] ?? 5,
        ':author' => $data['author'] ?? 'CDEM Solutions',
        ':published_at' => $data['published_at'] ?? null,
    ]);
}

function blog_delete(int $id): void {
    $pdo = db();
    $pdo->prepare('DELETE FROM blog_posts WHERE id = :id')->execute([':id' => $id]);
}

function blog_get(int $id): ?array {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM blog_posts WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $post = $stmt->fetch();
    return $post ?: null;
}

function blog_get_by_slug(string $slug): ?array {
    $pdo = db();
    $stmt = $pdo->prepare("SELECT p.*,
        (SELECT GROUP_CONCAT(t.name) FROM blog_tags t
         JOIN blog_post_tags pt ON pt.tag_id = t.id
         WHERE pt.post_id = p.id) as tags
        FROM blog_posts p WHERE p.slug = :slug");
    $stmt->execute([':slug' => $slug]);
    $post = $stmt->fetch();
    return $post ?: null;
}

function blog_ensure_unique_slug(string $slug, int $excludeId = 0): string {
    $pdo = db();
    $original = $slug;
    $counter = 1;

    while (true) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM blog_posts WHERE slug = :slug AND id != :id');
        $stmt->execute([':slug' => $slug, ':id' => $excludeId]);
        if ((int)$stmt->fetchColumn() === 0) break;
        $slug = $original . '-' . (++$counter);
    }

    return $slug;
}

// --- Listing ---

function blog_list_published(int $page = 1, int $perPage = 9, string $tag = ''): array {
    $query = "SELECT p.*,
        (SELECT GROUP_CONCAT(t.name) FROM blog_tags t
         JOIN blog_post_tags pt ON pt.tag_id = t.id
         WHERE pt.post_id = p.id) as tags
        FROM blog_posts p
        WHERE p.status = 'published'";
    $params = [];

    if ($tag) {
        $query .= " AND p.id IN (
            SELECT pt.post_id FROM blog_post_tags pt
            JOIN blog_tags t ON t.id = pt.tag_id
            WHERE t.name = :tag
        )";
        $params[':tag'] = $tag;
    }

    $query .= ' ORDER BY p.published_at DESC';

    return db_paginate($query, $params, $page, $perPage);
}

function blog_list_admin(int $page = 1, int $perPage = 20, string $status = ''): array {
    $query = 'SELECT * FROM blog_posts WHERE 1=1';
    $params = [];

    if ($status) {
        $query .= ' AND status = :status';
        $params[':status'] = $status;
    }

    $query .= ' ORDER BY created_at DESC';

    return db_paginate($query, $params, $page, $perPage);
}

// --- Tags ---

function blog_all_tags(): array {
    $pdo = db();
    return $pdo->query('SELECT * FROM blog_tags ORDER BY name')->fetchAll();
}

function blog_tag_create(string $name): int {
    $pdo = db();
    $slug = blog_slugify($name);
    $stmt = $pdo->prepare('INSERT OR IGNORE INTO blog_tags (name, slug) VALUES (:name, :slug)');
    $stmt->execute([':name' => $name, ':slug' => $slug]);
    return (int)$pdo->lastInsertId();
}

function blog_tag_update(int $id, string $name): void {
    $pdo = db();
    $slug = blog_slugify($name);
    $stmt = $pdo->prepare('UPDATE blog_tags SET name = :name, slug = :slug WHERE id = :id');
    $stmt->execute([':name' => $name, ':slug' => $slug, ':id' => $id]);
}

function blog_tag_delete(int $id): void {
    $pdo = db();
    $pdo->prepare('DELETE FROM blog_tags WHERE id = :id')->execute([':id' => $id]);
}

function blog_sync_tags(int $postId, array $tagNames): void {
    $pdo = db();

    // Remove existing
    $pdo->prepare('DELETE FROM blog_post_tags WHERE post_id = :id')->execute([':id' => $postId]);

    foreach ($tagNames as $name) {
        $name = trim($name);
        if (!$name) continue;

        // Find or create tag
        $stmt = $pdo->prepare('SELECT id FROM blog_tags WHERE name = :name');
        $stmt->execute([':name' => $name]);
        $tagId = $stmt->fetchColumn();

        if (!$tagId) {
            $tagId = blog_tag_create($name);
        }

        if ($tagId) {
            $pdo->prepare('INSERT OR IGNORE INTO blog_post_tags (post_id, tag_id) VALUES (:post, :tag)')
                ->execute([':post' => $postId, ':tag' => $tagId]);
        }
    }
}

function blog_get_post_tags_string(int $postId): string {
    $pdo = db();
    $stmt = $pdo->prepare("SELECT GROUP_CONCAT(t.name) FROM blog_tags t
        JOIN blog_post_tags pt ON pt.tag_id = t.id WHERE pt.post_id = :id");
    $stmt->execute([':id' => $postId]);
    return $stmt->fetchColumn() ?: '';
}
