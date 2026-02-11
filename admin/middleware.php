<?php
/**
 * Admin middleware — rate limiting and session security.
 */

function get_client_ip(): string {
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    }
    return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}

/**
 * Check if IP is rate-limited for login attempts.
 */
function is_rate_limited(string $ip, ?string $username = null): bool {
    $config = require __DIR__ . '/../data/config.php';
    $pdo = db();
    $window = $config['admin']['lockout_duration']; // 15 min

    // Check IP attempts
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin_login_attempts
        WHERE ip_address = :ip AND success = 0
        AND attempted_at > datetime('now', :window)");
    $stmt->execute([':ip' => $ip, ':window' => "-{$window} seconds"]);
    $ipAttempts = (int)$stmt->fetchColumn();

    if ($ipAttempts >= $config['admin']['max_login_attempts_ip']) {
        return true;
    }

    // Check username attempts
    if ($username) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin_login_attempts
            WHERE username = :user AND success = 0
            AND attempted_at > datetime('now', :window)");
        $stmt->execute([':user' => $username, ':window' => "-{$window} seconds"]);
        $userAttempts = (int)$stmt->fetchColumn();

        if ($userAttempts >= $config['admin']['max_login_attempts_user']) {
            return true;
        }
    }

    return false;
}

/**
 * Record a login attempt.
 */
function record_login_attempt(string $ip, string $username, bool $success): void {
    $pdo = db();
    $stmt = $pdo->prepare('INSERT INTO admin_login_attempts (ip_address, username, success) VALUES (:ip, :user, :success)');
    $stmt->execute([':ip' => $ip, ':user' => $username, ':success' => $success ? 1 : 0]);
}

/**
 * Clean old login attempts (> 1 hour).
 */
function cleanup_login_attempts(): void {
    $pdo = db();
    $pdo->exec("DELETE FROM admin_login_attempts WHERE attempted_at < datetime('now', '-1 hour')");
}
