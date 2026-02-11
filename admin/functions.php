<?php
/**
 * Admin helper functions — auth, CSRF, flash, settings, leads.
 */

// --- Authentication ---

function admin_login(string $username, string $password): bool {
    $ip = get_client_ip();

    if (is_rate_limited($ip, $username)) {
        return false;
    }

    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM admin_users WHERE username = :username AND is_locked = 0');
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        record_login_attempt($ip, $username, true);
        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['admin_username'] = $user['username'];
        $_SESSION['admin_role'] = $user['role'];
        $_SESSION['admin_ip'] = $ip;
        session_regenerate_id(true);
        return true;
    }

    record_login_attempt($ip, $username, false);
    return false;
}

function admin_logout(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function is_admin_logged_in(): bool {
    return isset($_SESSION['admin_id']);
}

function require_admin_auth(): void {
    if (!is_admin_logged_in()) {
        header('Location: /admin/login/');
        exit;
    }
}

function admin_user(): ?array {
    if (!is_admin_logged_in()) return null;
    return [
        'id' => $_SESSION['admin_id'],
        'username' => $_SESSION['admin_username'],
        'role' => $_SESSION['admin_role'],
    ];
}

// --- CSRF ---

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="_token" value="' . csrf_token() . '">';
}

function verify_csrf(): bool {
    $token = $_POST['_token'] ?? '';
    return hash_equals(csrf_token(), $token);
}

function require_csrf(): void {
    if (!verify_csrf()) {
        http_response_code(403);
        die('Invalid CSRF token');
    }
}

// --- Flash messages ---

function flash(string $type, string $message): void {
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function get_flashes(): array {
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

// --- Settings ---

function get_setting(string $key, $default = null): ?string {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT setting_value FROM admin_settings WHERE setting_key = :key');
    $stmt->execute([':key' => $key]);
    $val = $stmt->fetchColumn();
    return $val !== false ? $val : $default;
}

function set_setting(string $key, ?string $value, string $type = 'string', string $group = 'general'): void {
    $pdo = db();
    $stmt = $pdo->prepare("INSERT INTO admin_settings (setting_key, setting_value, setting_type, setting_group, updated_at)
        VALUES (:key, :val, :type, :group, datetime('now'))
        ON CONFLICT(setting_key) DO UPDATE SET setting_value = :val, updated_at = datetime('now')");
    $stmt->execute([':key' => $key, ':val' => $value, ':type' => $type, ':group' => $group]);
}

function get_settings_by_group(string $group): array {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM admin_settings WHERE setting_group = :group ORDER BY id');
    $stmt->execute([':group' => $group]);
    return $stmt->fetchAll();
}

// --- Leads ---

function leads_list(int $page = 1, int $perPage = 20, string $status = '', string $search = ''): array {
    $query = 'SELECT * FROM leads WHERE 1=1';
    $params = [];

    if ($status) {
        $query .= ' AND status = :status';
        $params[':status'] = $status;
    }

    if ($search) {
        $query .= ' AND (name LIKE :search OR email LIKE :search2 OR subject LIKE :search3)';
        $params[':search'] = "%{$search}%";
        $params[':search2'] = "%{$search}%";
        $params[':search3'] = "%{$search}%";
    }

    $query .= ' ORDER BY created_at DESC';

    return db_paginate($query, $params, $page, $perPage);
}

function lead_get(int $id): ?array {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM leads WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $lead = $stmt->fetch();
    return $lead ?: null;
}

function lead_update_status(int $id, string $status): void {
    $pdo = db();
    $stmt = $pdo->prepare("UPDATE leads SET status = :status, updated_at = datetime('now') WHERE id = :id");
    $stmt->execute([':status' => $status, ':id' => $id]);
}

function lead_update_notes(int $id, string $notes): void {
    $pdo = db();
    $stmt = $pdo->prepare("UPDATE leads SET notes = :notes, updated_at = datetime('now') WHERE id = :id");
    $stmt->execute([':notes' => $notes, ':id' => $id]);
}

function lead_delete(int $id): void {
    $pdo = db();
    $stmt = $pdo->prepare('DELETE FROM leads WHERE id = :id');
    $stmt->execute([':id' => $id]);
}

function leads_count_by_status(): array {
    $pdo = db();
    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM leads GROUP BY status");
    $counts = [];
    while ($row = $stmt->fetch()) {
        $counts[$row['status']] = (int)$row['count'];
    }
    return $counts;
}

// --- Dashboard stats ---

function admin_dashboard_stats(): array {
    $pdo = db();

    $totalLeads = (int)$pdo->query('SELECT COUNT(*) FROM leads')->fetchColumn();
    $newLeads = (int)$pdo->query("SELECT COUNT(*) FROM leads WHERE status = 'new'")->fetchColumn();
    $totalPosts = (int)$pdo->query('SELECT COUNT(*) FROM blog_posts')->fetchColumn();
    $publishedPosts = (int)$pdo->query("SELECT COUNT(*) FROM blog_posts WHERE status = 'published'")->fetchColumn();

    $recentLeads = $pdo->query('SELECT * FROM leads ORDER BY created_at DESC LIMIT 5')->fetchAll();
    $recentPosts = $pdo->query('SELECT * FROM blog_posts ORDER BY created_at DESC LIMIT 5')->fetchAll();

    return [
        'total_leads' => $totalLeads,
        'new_leads' => $newLeads,
        'total_posts' => $totalPosts,
        'published_posts' => $publishedPosts,
        'recent_leads' => $recentLeads,
        'recent_posts' => $recentPosts,
    ];
}

// --- Password change ---

function admin_change_password(int $userId, string $newPassword): void {
    $pdo = db();
    $hash = password_hash($newPassword, PASSWORD_ARGON2ID);
    $stmt = $pdo->prepare("UPDATE admin_users SET password_hash = :hash, updated_at = datetime('now') WHERE id = :id");
    $stmt->execute([':hash' => $hash, ':id' => $userId]);
}
