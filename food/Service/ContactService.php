<?php

declare(strict_types=1);

namespace App\Service;

use PDO;
use Throwable;

class ContactService
{
    public function __construct(private PDO $pdo) {}

    private function jsonResponse(array $data): void
    {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    public function handle(array $post): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $log_id = uniqid('form_', true);

        $data = array_map(fn($v) => is_string($v) ? trim($v) : $v, $post);

        // CSRF
        if (
            empty($data['csrf_token']) ||
            empty($_SESSION['csrf_token']) ||
            !hash_equals($_SESSION['csrf_token'], $data['csrf_token'])
        ) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Invalid form submission.'
            ]);
        }

        // reCAPTCHA
        if (
            empty($data['g-recaptcha-response']) ||
            !verifyRecaptcha($data['g-recaptcha-response'], $log_id)
        ) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Please confirm you are not a robot.',
                'field'   => 'recaptcha'
            ]);
        }

        // Spam
        if (is_spam($data)) {
            log_spam_attempt($data, $log_id);

            $this->jsonResponse([
                'success' => false,
                'message' => 'Your message could not be submitted.'
            ]);
        }

        // Validation
        $required = ['first_name', 'last_name', 'email', 'phone', 'message'];
        $errors = [];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' may not be empty';
            }
        }

        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        }

        $phone = preg_replace('/\D+/', '', $data['phone'] ?? '');
        if (!preg_match('/^\d{10}$/', $phone)) {
            $errors['phone'] = 'Invalid phone';
        }

        if ($errors) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_data']   = $data;

            $this->jsonResponse([
                'success' => false,
                'errors'  => $errors
            ]);
        }

        try {
            store_message(
                $this->pdo,
                $log_id,
                $data['first_name'],
                $data['last_name'],
                $data['email'],
                $phone,
                $data['message'],
                !empty($data['newsletter']) ? 1 : 0
            );

            sendAdminNotification($log_id, $data, 0);
            sendUserConfirmation($log_id, $data, 0);

            $_SESSION['success_message'] = 'Message sent';

            $this->jsonResponse([
                'success'  => true,
                'redirect' => BASE_URL . 'thank_you.php'
            ]);
        } catch (Throwable $e) {
            error_log("[$log_id] " . $e->getMessage());

            $this->jsonResponse([
                'success' => false,
                'message' => 'Server error'
            ]);
        }
    }
}
