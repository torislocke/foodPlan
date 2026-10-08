<?php

declare(strict_types=1);

require_once __DIR__ . '/init.php';

use Toril\Food\Service\AuthService;

// ─── Activate the account from the emailed link ───────────────────────────────
$email = trim((string) ($_GET['email'] ?? ''));
$token = trim((string) ($_GET['token'] ?? ''));

if ($email !== '' && $token !== '' && (new AuthService($pdo))->verify($email, $token)) {
    unset($_SESSION['unverified_email']);
    flash('success', 'Your email is verified and your account is active. Please sign in.');
} else {
    // Let them request a fresh link from the login page
    if ($email !== '') {
        $_SESSION['unverified_email'] = $email;
    }
    flash('error', 'This activation link is invalid or has expired.');
}

redirect_to('login/login.php');
