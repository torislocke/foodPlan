<?php

declare(strict_types=1);

namespace Toril\Food\Service;

class AuthService
{
    public function __construct(private \PDO $pdo) {}

    public function register(string $name, string $email, string $password): array
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['error' => 'Invalid email address.'];
        }
        if (strlen($password) < 8) {
            return ['error' => 'Password must be at least 8 characters.'];
        }

        $st = $this->pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $st->execute([strtolower($email)]);
        if ($st->fetch()) {
            return ['error' => 'An account with that email already exists.'];
        }

        $this->pdo->prepare('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)')->execute([
            trim($name),
            strtolower($email),
            password_hash($password, PASSWORD_BCRYPT),
        ]);

        return ['success' => true, 'user_id' => (int) $this->pdo->lastInsertId(), 'name' => trim($name)];
    }

    public function login(string $email, string $password): array
    {
        $st = $this->pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $st->execute([strtolower($email)]);
        $user = $st->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return ['error' => 'Invalid email or password.'];
        }

        return ['success' => true, 'user' => $user];
    }

    public function getUser(int $id): ?array
    {
        $st = $this->pdo->prepare('SELECT id, name, email, created_at FROM users WHERE id = ? LIMIT 1');
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    // Link any session-based records to the user account on login/register.
    public function migrateSession(int $userId, string $sessionToken): void
    {
        foreach (['nutritional_goals', 'meal_plans', 'custom_foods'] as $table) {
            $this->pdo->prepare(
                "UPDATE {$table} SET user_id = ? WHERE session_token = ? AND user_id IS NULL"
            )->execute([$userId, $sessionToken]);
        }
    }
}
