<?php
/**
 * SQLite database connection (singleton) and pagination helper.
 */

function db(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $config = require __DIR__ . '/../data/config.php';
        $dbPath = $config['db_path'];

        $dir = dirname($dbPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $pdo = new PDO('sqlite:' . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA journal_mode = WAL');
        $pdo->exec('PRAGMA foreign_keys = ON');
    }

    return $pdo;
}

/**
 * Paginate a query. Returns ['data' => [...], 'pagination' => [...]].
 */
function db_paginate(string $query, array $params = [], int $page = 1, int $perPage = 10): array {
    $pdo = db();
    $page = max(1, $page);

    // Count total
    $countQuery = 'SELECT COUNT(*) FROM (' . $query . ') AS count_table';
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($params);
    $total = (int)$stmt->fetchColumn();

    $totalPages = max(1, (int)ceil($total / $perPage));
    $offset = ($page - 1) * $perPage;

    // Fetch page
    $dataQuery = $query . ' LIMIT :_limit OFFSET :_offset';
    $stmt = $pdo->prepare($dataQuery);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':_limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':_offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $data = $stmt->fetchAll();

    return [
        'data' => $data,
        'pagination' => [
            'current' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => $totalPages,
        ],
    ];
}

/**
 * Get a setting value from admin_settings (frontend-safe).
 */
function db_get_setting(string $key, ?string $default = null): ?string {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT setting_value FROM admin_settings WHERE setting_key = :key');
    $stmt->execute([':key' => $key]);
    $val = $stmt->fetchColumn();
    return $val !== false ? $val : $default;
}

/**
 * Get all SEO redirects.
 */
function db_get_redirects(): array {
    $pdo = db();
    return $pdo->query('SELECT from_path, to_url, redirect_type FROM seo_redirects WHERE is_active = 1 ORDER BY from_path')->fetchAll();
}
