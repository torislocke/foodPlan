<?php

declare(strict_types=1);

require_once __DIR__ . '/init.php';

use Toril\Food\Service\AuthService;

// ─── Send a fresh verification link (POST from the login page) ────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valid()) {
    redirect_to('login/login.php');
}

$email = trim((string) ($_POST['email'] ?? ''));
if ($renewal = (new AuthService($pdo))->renewVerification($email)) {
    send_auth_link('verify', $renewal['user']['email'], $renewal['user']['first_name'],
        BASE_URL . 'login/register_verify.php?'
        . http_build_query(['email' => $renewal['user']['email'], 'token' => $renewal['token']]));
}

// Same answer either way, so this can't be used to discover accounts
unset($_SESSION['unverified_email']);
flash('info', 'If that account still needs activating, a new link is on its way. Check your inbox and spam folder.');
redirect_to('login/login.php');
