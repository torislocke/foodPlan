<?php

declare(strict_types=1);

require_once __DIR__ . '/init.php';

use Toril\Food\Service\AuthService;

// ─── Revoke "remember me" on this device ──────────────────────────────────────
if (!empty($_COOKIE[REMEMBER_COOKIE])) {
    try {
        (new AuthService($pdo))->forgetRememberToken((string) $_COOKIE[REMEMBER_COOKIE]);
    } catch (Throwable $e) {
        error_log('logout remember-me cleanup: ' . $e->getMessage());
    }
    clear_remember_cookie();
}

// ─── Destroy the session (and its cookie) ─────────────────────────────────────
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires'  => time() - 3600,
        'path'     => $params['path'],
        'domain'   => $params['domain'],
        'secure'   => $params['secure'],
        'httponly' => $params['httponly'],
        'samesite' => $params['samesite'] ?: 'Lax',
    ]);
}
session_destroy();

// Fresh session just to carry the confirmation message
session_start();
session_regenerate_id(true);
flash('info', 'You have been signed out.');
redirect_to('login/login.php');
