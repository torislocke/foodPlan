<?php

declare(strict_types=1);

namespace Toril\Food\Service;

/**
 * Accounts: registration with email verification, login, password reset and
 * "remember me" tokens. Emailed and cookie tokens are random; only their
 * sha256 hashes are stored, so a database leak can't be used to log in.
 */
class AuthService
{
    public const MIN_PASSWORD   = 8;
    private const VERIFY_HOURS  = 24;
    private const RESET_MINUTES = 60;
    private const REMEMBER_DAYS = 30;

    public function __construct(private \PDO $pdo) {}

    // ── Registration & verification ──────────────────────────────────────── //

    /**
     * Creates an Inactive account and returns the raw verification token to email.
     * @throws \RuntimeException with a user-facing message
     */
    public function register(string $firstName, string $lastName, string $email, string $password): string
    {
        $email = strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('Please enter a valid email address.');
        }
        if (strlen($password) < self::MIN_PASSWORD) {
            throw new \RuntimeException('Password must be at least ' . self::MIN_PASSWORD . ' characters.');
        }
        if ($this->findByEmail($email)) {
            throw new \RuntimeException('An account with that email already exists.');
        }

        $token = $this->newToken();
        $this->pdo->prepare('
            INSERT INTO users (first_name, last_name, email, password, token, token_expires, status)
            VALUES (?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL ? HOUR), "Inactive")
        ')->execute([
            trim($firstName), trim($lastName), $email,
            password_hash($password, PASSWORD_DEFAULT),
            hash('sha256', $token), self::VERIFY_HOURS,
        ]);

        return $token;
    }

    /** New verification token for an Inactive account, or null if there is none. */
    public function renewVerification(string $email): ?array
    {
        $user = $this->findByEmail($email);
        if (!$user || $user['status'] !== 'Inactive') {
            return null;
        }
        $token = $this->setToken((int) $user['id'], 'HOUR', self::VERIFY_HOURS);
        return ['user' => $user, 'token' => $token];
    }

    public function verify(string $email, string $token): bool
    {
        $st = $this->pdo->prepare('
            UPDATE users SET status = "Active", token = NULL, token_expires = NULL
            WHERE email = ? AND token = ? AND token_expires > NOW() AND status = "Inactive"
        ');
        $st->execute([strtolower(trim($email)), hash('sha256', $token)]);
        return $st->rowCount() === 1;
    }

    // ── Login ────────────────────────────────────────────────────────────── //

    /**
     * @return array the user row on success
     * @throws \RuntimeException with a user-facing message ('inactive' code for unverified)
     */
    public function login(string $email, string $password): array
    {
        $user = $this->findByEmail($email);
        if (!$user || !password_verify($password, $user['password'])) {
            throw new \RuntimeException('Invalid email or password.');
        }
        if ($user['status'] !== 'Active') {
            throw new \RuntimeException('Please verify your email address before signing in.', 403);
        }
        if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
            $this->pdo->prepare('UPDATE users SET password = ? WHERE id = ?')
                ->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);
        }
        return $user;
    }

    public function getUser(int $id): ?array
    {
        $st = $this->pdo->prepare('
            SELECT id, first_name, last_name, email, CONCAT_WS(" ", first_name, last_name) AS name
            FROM users WHERE id = ? AND status = "Active" LIMIT 1
        ');
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    // Link any session-based records (guest meal plans, goals, foods) to the account.
    public function migrateSession(int $userId, string $sessionToken): void
    {
        foreach (['nutritional_goals', 'meal_plans', 'custom_foods'] as $table) {
            $this->pdo->prepare(
                "UPDATE {$table} SET user_id = ? WHERE session_token = ? AND user_id IS NULL"
            )->execute([$userId, $sessionToken]);
        }
    }

    // ── Password reset ───────────────────────────────────────────────────── //

    /** Raw reset token to email, or null when no Active account has that email. */
    public function createPasswordReset(string $email): ?array
    {
        $user = $this->findByEmail($email);
        if (!$user || $user['status'] !== 'Active') {
            return null;
        }
        $token = $this->setToken((int) $user['id'], 'MINUTE', self::RESET_MINUTES);
        return ['user' => $user, 'token' => $token];
    }

    public function isValidReset(string $email, string $token): bool
    {
        $st = $this->pdo->prepare('
            SELECT id FROM users
            WHERE email = ? AND token = ? AND token_expires > NOW() AND status = "Active" LIMIT 1
        ');
        $st->execute([strtolower(trim($email)), hash('sha256', $token)]);
        return (bool) $st->fetch();
    }

    /** @throws \RuntimeException */
    public function resetPassword(string $email, string $token, string $password): void
    {
        if (strlen($password) < self::MIN_PASSWORD) {
            throw new \RuntimeException('Password must be at least ' . self::MIN_PASSWORD . ' characters.');
        }
        $st = $this->pdo->prepare('
            UPDATE users SET password = ?, token = NULL, token_expires = NULL
            WHERE email = ? AND token = ? AND token_expires > NOW() AND status = "Active"
        ');
        $st->execute([password_hash($password, PASSWORD_DEFAULT), strtolower(trim($email)), hash('sha256', $token)]);
        if ($st->rowCount() !== 1) {
            throw new \RuntimeException('This reset link is invalid or has expired. Please request a new one.');
        }

        // A new password signs out every "remember me" device
        $this->pdo->prepare('DELETE FROM remember_tokens WHERE user_id = (SELECT id FROM users WHERE email = ?)')
            ->execute([strtolower(trim($email))]);
    }

    // ── Remember me ──────────────────────────────────────────────────────── //

    /** Stores a hashed token and returns the raw value for the cookie. */
    public function issueRememberToken(int $userId): string
    {
        $token = $this->newToken();
        $this->pdo->prepare('DELETE FROM remember_tokens WHERE user_id = ? AND expires_at <= NOW()')
            ->execute([$userId]);
        $this->pdo->prepare('
            INSERT INTO remember_tokens (user_id, token_hash, expires_at)
            VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? DAY))
        ')->execute([$userId, hash('sha256', $token), self::REMEMBER_DAYS]);
        return $token;
    }

    public function userIdFromRememberToken(string $token): ?int
    {
        $st = $this->pdo->prepare('
            SELECT rt.user_id FROM remember_tokens rt
            JOIN users u ON u.id = rt.user_id AND u.status = "Active"
            WHERE rt.token_hash = ? AND rt.expires_at > NOW() LIMIT 1
        ');
        $st->execute([hash('sha256', $token)]);
        $id = $st->fetchColumn();
        return $id === false ? null : (int) $id;
    }

    public function forgetRememberToken(string $token): void
    {
        $this->pdo->prepare('DELETE FROM remember_tokens WHERE token_hash = ?')
            ->execute([hash('sha256', $token)]);
    }

    public static function rememberDays(): int
    {
        return self::REMEMBER_DAYS;
    }

    // ── Helpers ──────────────────────────────────────────────────────────── //

    private function findByEmail(string $email): ?array
    {
        $st = $this->pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $st->execute([strtolower(trim($email))]);
        return $st->fetch() ?: null;
    }

    /** Stores a fresh hashed token on the user; returns the raw token. */
    private function setToken(int $userId, string $unit, int $amount): string
    {
        $token = $this->newToken();
        $unit  = $unit === 'MINUTE' ? 'MINUTE' : 'HOUR';
        $this->pdo->prepare("
            UPDATE users SET token = ?, token_expires = DATE_ADD(NOW(), INTERVAL ? {$unit}) WHERE id = ?
        ")->execute([hash('sha256', $token), $amount, $userId]);
        return $token;
    }

    private function newToken(): string
    {
        return bin2hex(random_bytes(32));
    }
}
