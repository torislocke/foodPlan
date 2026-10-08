<?php

declare(strict_types=1);

require_once __DIR__ . '/init.php';

use Toril\Food\Service\AuthService;

$redirect = safe_redirect_path($_GET['redirect'] ?? $_POST['redirect'] ?? '');

if ($currentUser) {
    redirect_to($redirect);
}

// ─── Handle registration ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    keep_old_input(['first_name', 'last_name', 'email']);

    try {
        if (!csrf_valid()) {
            throw new RuntimeException('Your session expired. Please try again.');
        }
        $labels = [
            'first_name'       => 'First name',
            'last_name'        => 'Last name',
            'email'            => 'Email',
            'password'         => 'Password',
            'confirm_password' => 'Confirm password',
        ];
        foreach ($labels as $field => $label) {
            if (trim((string) ($_POST[$field] ?? '')) === '') {
                throw new RuntimeException("$label is required.");
            }
        }
        if ($_POST['password'] !== $_POST['confirm_password']) {
            throw new RuntimeException('Passwords do not match.');
        }

        $email     = trim((string) $_POST['email']);
        $firstName = trim((string) $_POST['first_name']);
        $token     = (new AuthService($pdo))->register(
            $firstName,
            trim((string) $_POST['last_name']),
            $email,
            (string) $_POST['password']
        );

        send_auth_link('verify', $email, $firstName, BASE_URL . 'login/register_verify.php?'
            . http_build_query(['email' => strtolower($email), 'token' => $token]));

        forget_old_input();
        flash('success', 'Account created! Check your email for a link to activate it, then sign in.');
        redirect_to('login/login.php?redirect=' . urlencode($redirect));
    } catch (RuntimeException $e) {
        flash('error', $e->getMessage());
        redirect_to('login/register.php?redirect=' . urlencode($redirect));
    }
}

$pageTitle    = 'Create Account';
$canonicalUrl = BASE_URL . 'login/register.php';

require_once dirname(__DIR__) . '/common/header.php';
?>

<main class="auth-page">
    <div class="auth-card">
        <h1 class="auth-heading">Create account</h1>
        <p class="auth-subheading">Save your meal plans, goals and profile on any device</p>

        <?= auth_alerts() ?>

        <form method="POST" action="" class="auth-form" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">

            <div class="auth-name-row">
                <div class="auth-group">
                    <label class="auth-label" for="first_name">First name</label>
                    <input class="auth-input" type="text" id="first_name" name="first_name"
                        placeholder="e.g. Jane" value="<?= old('first_name') ?>"
                        autocomplete="given-name" required autofocus>
                </div>
                <div class="auth-group">
                    <label class="auth-label" for="last_name">Last name</label>
                    <input class="auth-input" type="text" id="last_name" name="last_name"
                        placeholder="e.g. Smith" value="<?= old('last_name') ?>"
                        autocomplete="family-name" required>
                </div>
            </div>

            <div class="auth-group">
                <label class="auth-label" for="reg_email">Email address</label>
                <input class="auth-input" type="email" id="reg_email" name="email"
                    placeholder="you@example.com" value="<?= old('email') ?>"
                    autocomplete="email" required>
            </div>

            <hr class="auth-rule">

            <div class="auth-group">
                <label class="auth-label" for="reg_password">Password</label>
                <div class="auth-pw">
                    <input class="auth-input" type="password" id="reg_password" name="password"
                        placeholder="Create a password" minlength="<?= AuthService::MIN_PASSWORD ?>"
                        autocomplete="new-password" required>
                    <button type="button" class="auth-pw-toggle" data-target="reg_password">Show</button>
                </div>
                <span class="auth-hint">At least <?= AuthService::MIN_PASSWORD ?> characters</span>
            </div>

            <div class="auth-group">
                <label class="auth-label" for="confirm_password">Confirm password</label>
                <div class="auth-pw">
                    <input class="auth-input" type="password" id="confirm_password" name="confirm_password"
                        placeholder="Repeat your password" autocomplete="new-password" required
                        data-match="reg_password">
                    <button type="button" class="auth-pw-toggle" data-target="confirm_password">Show</button>
                </div>
                <span class="auth-hint auth-match" aria-live="polite"></span>
            </div>

            <button type="submit" class="btn btn-primary auth-submit">Create account</button>
        </form>

        <p class="auth-footer">
            Already have an account?
            <a class="auth-link" href="<?= BASE_URL ?>login/login.php?redirect=<?= urlencode($redirect) ?>">Sign in</a>
        </p>
    </div>
</main>

<?php forget_old_input(); ?>
<?php require_once dirname(__DIR__) . '/common/footer.php'; ?>
