<?php

declare(strict_types=1);

require_once __DIR__ . '/init.php';

use Toril\Food\Service\AuthService;

// The token is in the URL: don't leak it to other sites through the Referer header
header('Referrer-Policy: no-referrer');

$auth  = new AuthService($pdo);
$email = trim((string) ($_GET['email'] ?? ''));
$token = trim((string) ($_GET['token'] ?? ''));
$self  = 'login/reset-password.php?' . http_build_query(['email' => $email, 'token' => $token]);

if ($email === '' || $token === '' || !$auth->isValidReset($email, $token)) {
    flash('error', 'This password reset link is invalid or has expired. Please request a new one.');
    redirect_to('login/forget-password.php');
}

// ─── Handle new password ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!csrf_valid()) {
            throw new RuntimeException('Your session expired. Please try again.');
        }
        $password = (string) ($_POST['password'] ?? '');
        if ($password !== (string) ($_POST['confirm_password'] ?? '')) {
            throw new RuntimeException('Passwords do not match.');
        }

        $auth->resetPassword($email, $token, $password);

        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        flash('success', 'Your password has been reset. Please sign in with your new password.');
        redirect_to('login/login.php');
    } catch (RuntimeException $e) {
        flash('error', $e->getMessage());
        redirect_to($self);
    }
}

$pageTitle    = 'Reset Password';
$canonicalUrl = BASE_URL . 'login/reset-password.php';

require_once dirname(__DIR__) . '/common/header.php';
?>

<main class="auth-page">
    <div class="auth-card">
        <h1 class="auth-heading">Set a new password</h1>
        <p class="auth-subheading">For <?= htmlspecialchars($email) ?></p>

        <?= auth_alerts() ?>

        <form method="POST" action="" class="auth-form" novalidate>
            <?= csrf_field() ?>

            <div class="auth-group">
                <label class="auth-label" for="rp_password">New password</label>
                <div class="auth-pw">
                    <input class="auth-input" type="password" id="rp_password" name="password"
                        placeholder="Create a password" minlength="<?= AuthService::MIN_PASSWORD ?>"
                        autocomplete="new-password" required autofocus>
                    <button type="button" class="auth-pw-toggle" data-target="rp_password">Show</button>
                </div>
                <span class="auth-hint">At least <?= AuthService::MIN_PASSWORD ?> characters</span>
            </div>

            <div class="auth-group">
                <label class="auth-label" for="rp_confirm">Confirm new password</label>
                <div class="auth-pw">
                    <input class="auth-input" type="password" id="rp_confirm" name="confirm_password"
                        placeholder="Repeat your password" autocomplete="new-password" required
                        data-match="rp_password">
                    <button type="button" class="auth-pw-toggle" data-target="rp_confirm">Show</button>
                </div>
                <span class="auth-hint auth-match" aria-live="polite"></span>
            </div>

            <button type="submit" class="btn btn-primary auth-submit">Reset password</button>
        </form>

        <p class="auth-footer">
            <a class="auth-link" href="<?= BASE_URL ?>login/login.php">&#8249; Back to sign in</a>
        </p>
    </div>
</main>

<?php require_once dirname(__DIR__) . '/common/footer.php'; ?>
