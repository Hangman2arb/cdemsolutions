<?php
/**
 * Admin bootstrap — session init and security.
 */

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', '1');
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.use_strict_mode', '1');
    session_start();
}

$base = dirname(__DIR__);

require_once $base . '/lib/database.php';
require_once $base . '/admin/functions.php';
require_once $base . '/admin/middleware.php';
