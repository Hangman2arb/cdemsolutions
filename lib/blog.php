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

function blog_get_by_slug(string $slug, string $lang = 'en'): ?array {
    $pdo = db();

    // If requesting non-English, check translations first
    if ($lang !== 'en') {
        $stmt = $pdo->prepare("SELECT p.*, bt.title AS title, bt.slug AS slug,
            bt.excerpt AS excerpt, bt.content_html AS content_html,
            bt.meta_title AS meta_title, bt.meta_description AS meta_description,
            bt.meta_keywords AS meta_keywords, bt.reading_time AS reading_time,
            p.slug AS en_slug, bt.slug AS translated_slug, :lang AS display_lang,
            (SELECT GROUP_CONCAT(t.name) FROM blog_tags t
             JOIN blog_post_tags pt ON pt.tag_id = t.id
             WHERE pt.post_id = p.id) as tags
            FROM blog_translations bt
            JOIN blog_posts p ON p.id = bt.post_id
            WHERE bt.slug = :slug AND bt.lang = :lang2");
        $stmt->execute([':slug' => $slug, ':lang' => $lang, ':lang2' => $lang]);
        $post = $stmt->fetch();
        if ($post) return $post;
    }

    // Fallback: look up in blog_posts (English master)
    $stmt = $pdo->prepare("SELECT p.*, p.slug AS en_slug, 'en' AS display_lang,
        (SELECT GROUP_CONCAT(t.name) FROM blog_tags t
         JOIN blog_post_tags pt ON pt.tag_id = t.id
         WHERE pt.post_id = p.id) as tags
        FROM blog_posts p WHERE p.slug = :slug");
    $stmt->execute([':slug' => $slug]);
    $post = $stmt->fetch();
    if (!$post) return null;

    // If lang != en, try to find translation for this post (user hit EN slug with ES lang)
    if ($lang !== 'en') {
        $post['translated_slug'] = blog_get_translation_slug($post['id'], $lang);
    }

    return $post;
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

function blog_list_published(int $page = 1, int $perPage = 9, string $tag = '', string $lang = 'en'): array {
    if ($lang !== 'en') {
        // LEFT JOIN translations — COALESCE for title/excerpt/slug/reading_time
        $query = "SELECT p.id, p.featured_image, p.status, p.author, p.published_at, p.created_at, p.updated_at,
            COALESCE(bt.slug, p.slug) AS slug,
            COALESCE(bt.title, p.title) AS title,
            COALESCE(bt.excerpt, p.excerpt) AS excerpt,
            COALESCE(bt.reading_time, p.reading_time) AS reading_time,
            (SELECT GROUP_CONCAT(t.name) FROM blog_tags t
             JOIN blog_post_tags pt ON pt.tag_id = t.id
             WHERE pt.post_id = p.id) as tags
            FROM blog_posts p
            LEFT JOIN blog_translations bt ON bt.post_id = p.id AND bt.lang = :lang
            WHERE p.status = 'published'";
        $params = [':lang' => $lang];
    } else {
        $query = "SELECT p.*,
            (SELECT GROUP_CONCAT(t.name) FROM blog_tags t
             JOIN blog_post_tags pt ON pt.tag_id = t.id
             WHERE pt.post_id = p.id) as tags
            FROM blog_posts p
            WHERE p.status = 'published'";
        $params = [];
    }

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
    $query = "SELECT p.*,
        (SELECT GROUP_CONCAT(bt.lang) FROM blog_translations bt WHERE bt.post_id = p.id) AS translated_langs
        FROM blog_posts p WHERE 1=1";
    $params = [];

    if ($status) {
        $query .= ' AND p.status = :status';
        $params[':status'] = $status;
    }

    $query .= ' ORDER BY p.created_at DESC';

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

// --- Translations ---

function blog_translation_get(int $postId, string $lang): ?array {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM blog_translations WHERE post_id = :post_id AND lang = :lang');
    $stmt->execute([':post_id' => $postId, ':lang' => $lang]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function blog_translation_save(int $postId, string $lang, array $data): void {
    $pdo = db();

    $slug = $data['slug'] ?: blog_slugify($data['title']);
    $slug = blog_ensure_unique_translation_slug($slug, $postId, $lang);

    $stmt = $pdo->prepare("INSERT INTO blog_translations
        (post_id, lang, slug, title, excerpt, content_html, meta_title, meta_description, meta_keywords, reading_time, updated_at)
        VALUES (:post_id, :lang, :slug, :title, :excerpt, :content_html, :meta_title, :meta_description, :meta_keywords, :reading_time, datetime('now'))
        ON CONFLICT(post_id, lang) DO UPDATE SET
            slug = :slug, title = :title, excerpt = :excerpt, content_html = :content_html,
            meta_title = :meta_title, meta_description = :meta_description, meta_keywords = :meta_keywords,
            reading_time = :reading_time, updated_at = datetime('now')");

    $stmt->execute([
        ':post_id' => $postId,
        ':lang' => $lang,
        ':slug' => $slug,
        ':title' => $data['title'],
        ':excerpt' => $data['excerpt'] ?? '',
        ':content_html' => $data['content_html'] ?? '',
        ':meta_title' => $data['meta_title'] ?? '',
        ':meta_description' => $data['meta_description'] ?? '',
        ':meta_keywords' => $data['meta_keywords'] ?? '',
        ':reading_time' => $data['reading_time'] ?? 5,
    ]);
}

function blog_translation_delete(int $postId, string $lang): void {
    $pdo = db();
    $pdo->prepare('DELETE FROM blog_translations WHERE post_id = :post_id AND lang = :lang')
        ->execute([':post_id' => $postId, ':lang' => $lang]);
}

function blog_ensure_unique_translation_slug(string $slug, int $postId = 0, string $lang = 'es'): string {
    $pdo = db();
    $original = $slug;
    $counter = 1;

    while (true) {
        // Check uniqueness in blog_posts
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM blog_posts WHERE slug = :slug');
        $stmt->execute([':slug' => $slug]);
        if ((int)$stmt->fetchColumn() > 0) {
            $slug = $original . '-' . (++$counter);
            continue;
        }

        // Check uniqueness in blog_translations (excluding current record)
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM blog_translations WHERE slug = :slug AND NOT (post_id = :post_id AND lang = :lang)');
        $stmt->execute([':slug' => $slug, ':post_id' => $postId, ':lang' => $lang]);
        if ((int)$stmt->fetchColumn() === 0) break;

        $slug = $original . '-' . (++$counter);
    }

    return $slug;
}

function blog_get_translation_slug(int $postId, string $lang): ?string {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT slug FROM blog_translations WHERE post_id = :post_id AND lang = :lang');
    $stmt->execute([':post_id' => $postId, ':lang' => $lang]);
    $slug = $stmt->fetchColumn();
    return $slug ?: null;
}

function blog_get_en_slug_from_translation(string $translatedSlug, string $lang): ?string {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT p.slug FROM blog_posts p JOIN blog_translations bt ON bt.post_id = p.id WHERE bt.slug = :slug AND bt.lang = :lang');
    $stmt->execute([':slug' => $translatedSlug, ':lang' => $lang]);
    $slug = $stmt->fetchColumn();
    return $slug ?: null;
}
