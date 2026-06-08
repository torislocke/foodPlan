<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/config/planner_bootstrap.php';

use Toril\Food\Service\AuthService;
use Toril\Food\Service\MealPlannerService;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

// CSRF check
if (($data['csrf'] ?? '') !== ($_SESSION['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid request token.']);
    exit;
}

$action = $data['action'] ?? '';
$auth   = new AuthService($pdo);

// ── Register ──────────────────────────────────────────────────────────────── //
if ($action === 'register') {
    $name     = trim($data['name']     ?? '');
    $email    = trim($data['email']    ?? '');
    $password =      $data['password'] ?? '';
    $confirm  =      $data['confirm']  ?? '';

    if (!$name || !$email || !$password) {
        echo json_encode(['error' => 'All fields are required.']);
        exit;
    }
    if ($password !== $confirm) {
        echo json_encode(['error' => 'Passwords do not match.']);
        exit;
    }

    $result = $auth->register($name, $email, $password);
    if (isset($result['error'])) {
        echo json_encode($result);
        exit;
    }

    $token = MealPlannerService::getToken();
    $auth->migrateSession($result['user_id'], $token);
    $_SESSION['user_id'] = $result['user_id'];

    echo json_encode(['success' => true, 'name' => $result['name']]);
    exit;
}

// ── Login ─────────────────────────────────────────────────────────────────── //
if ($action === 'login') {
    $email    = trim($data['email']    ?? '');
    $password =      $data['password'] ?? '';

    if (!$email || !$password) {
        echo json_encode(['error' => 'Email and password are required.']);
        exit;
    }

    $result = $auth->login($email, $password);
    if (isset($result['error'])) {
        echo json_encode($result);
        exit;
    }

    $user  = $result['user'];
    $token = MealPlannerService::getToken();
    $auth->migrateSession((int) $user['id'], $token);
    $_SESSION['user_id'] = (int) $user['id'];

    echo json_encode(['success' => true, 'name' => $user['name']]);
    exit;
}

// ── Logout ────────────────────────────────────────────────────────────────── //
if ($action === 'logout') {
    unset($_SESSION['user_id']);
    echo json_encode(['success' => true]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Unknown action.']);
