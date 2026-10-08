<?php

declare(strict_types=1);

require_once __DIR__ . '/init.php';

use Toril\Food\Service\AuthService;

// Where to go after signing in (in-site relative paths only)
$redirect = safe_redirect_path($_GET['redirect'] ?? $_POST['redirect'] ?? '');

if ($currentUser) {
    redirect_to($redirect);
}

// ─── Handle login ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));
    keep_old_input(['email']);

    try {
        if (!csrf_valid()) {
            throw new RuntimeException('Your session expired. Please try again.');
        }
        if ($email === '' || ($_POST['password'] ?? '') === '') {
            throw new RuntimeException('Please enter your email and password.');
        }

        $user = (new AuthService($pdo))->login($email, (string) $_POST['password']);
        login_user($pdo, (int) $user['id'], !empty($_POST['remember_me']));

        forget_old_input();
        unset($_SESSION['unverified_email']);
        flash('success', 'Welcome back, ' . $user['first_name'] . '!');
        redirect_to($redirect);
    } catch (RuntimeException $e) {
        // Unverified accounts get a "resend verification email" option
        if ($e->getCode() === 403) {
            $_SESSION['unverified_email'] = $email;
        }
        flash('error', $e->getMessage());
        redirect_to('login/login.php?redirect=' . urlencode($redirect));
    }
}

$pageTitle    = 'Sign In';
$canonicalUrl = BASE_URL . 'login/login.php';
$unverified   = $_SESSION['unverified_email'] ?? null;

require_once dirname(__DIR__) . '/common/header.php';
?>

<main class="auth-page">
    <div class="auth-card">
        <h1 class="auth-heading">Welcome back</h1>
        <p class="auth-subheading">Sign in to save and track your meal plans</p>

        <?= auth_alerts() ?>

        <?php if ($unverified): ?>
            <form method="POST" action="<?= BASE_URL ?>login/resend-verification.php" class="auth-resend">
                <?= csrf_field() ?>
                <input type="hidden" name="email" value="<?= htmlspecialchars($unverified) ?>">
                Didn't get the email?
                <button type="submit" class="btn-text">Resend verification link</button>
            </form>
        <?php endif; ?>

        <form method="POST" action="" class="auth-form" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">

            <div class="auth-group">
                <label class="auth-label" for="login_email">Email address</label>
                <input class="auth-input" type="email" id="login_email" name="email"
                    placeholder="you@example.com" value="<?= old('email') ?>"
                    autocomplete="email" required autofocus>
            </div>

            <div class="auth-group">
                <label class="auth-label" for="login_password">Password</label>
                <div class="auth-pw">
                    <input class="auth-input" type="password" id="login_password" name="password"
                        placeholder="Your password" autocomplete="current-password" required>
                    <button type="button" class="auth-pw-toggle" data-target="login_password">Show</button>
                </div>
            </div>

            <div class="auth-meta-row">
                <label class="auth-remember">
                    <input type="checkbox" name="remember_me" value="1">
                    Keep me signed in
                </label>
                <a class="auth-link" href="<?= BASE_URL ?>login/forget-password.php">Forgot password?</a>
            </div>

            <button type="submit" class="btn btn-primary auth-submit">Sign in</button>
        </form>

        <div class="auth-divider"><span>New here?</span></div>
        <a class="btn btn-secondary auth-submit"
            href="<?= BASE_URL ?>login/register.php?redirect=<?= urlencode($redirect) ?>">Create a free account</a>
    </div>
</main>

<?php forget_old_input(); ?>
<?php require_once dirname(__DIR__) . '/common/footer.php'; ?>
