<?php

declare(strict_types=1);

require_once __DIR__ . '/init.php';

use Toril\Food\Service\AuthService;

// ─── Send a password reset link ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));
    keep_old_input(['email']);

    try {
        if (!csrf_valid()) {
            throw new RuntimeException('Your session expired. Please try again.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Please enter a valid email address.');
        }

        if ($reset = (new AuthService($pdo))->createPasswordReset($email)) {
            send_auth_link('reset', $reset['user']['email'], $reset['user']['first_name'],
                BASE_URL . 'login/reset-password.php?'
                . http_build_query(['email' => $reset['user']['email'], 'token' => $reset['token']]));
        }

        // Same answer either way, so this can't be used to discover accounts
        forget_old_input();
        flash('success', "If that email is registered, you'll receive a reset link shortly. It expires in 1 hour.");
    } catch (RuntimeException $e) {
        flash('error', $e->getMessage());
    }
    redirect_to('login/forget-password.php');
}

$pageTitle    = 'Forgot Password';
$canonicalUrl = BASE_URL . 'login/forget-password.php';

require_once dirname(__DIR__) . '/common/header.php';
?>

<main class="auth-page">
    <div class="auth-card">
        <div class="auth-icon" aria-hidden="true">&#128274;</div>
        <h1 class="auth-heading">Forgot password?</h1>
        <p class="auth-subheading">Enter your email and we'll send you a link to reset your password.</p>

        <?= auth_alerts() ?>

        <form method="POST" action="" class="auth-form" novalidate>
            <?= csrf_field() ?>
            <div class="auth-group">
                <label class="auth-label" for="fp_email">Email address</label>
                <input class="auth-input" type="email" id="fp_email" name="email"
                    placeholder="you@example.com" value="<?= old('email') ?>"
                    autocomplete="email" required autofocus>
            </div>
            <button type="submit" class="btn btn-primary auth-submit">Send reset link</button>
        </form>

        <p class="auth-footer">
            <a class="auth-link" href="<?= BASE_URL ?>login/login.php">&#8249; Back to sign in</a>
        </p>
    </div>
</main>

<?php forget_old_input(); ?>
<?php require_once dirname(__DIR__) . '/common/footer.php'; ?>
