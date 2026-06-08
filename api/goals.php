<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/config/planner_bootstrap.php';

use Toril\Food\Service\MealPlannerService;

header('Content-Type: application/json');

$planner = new MealPlannerService($pdo);
$token   = MealPlannerService::getToken();
$userId  = $currentUserId ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode($planner->getGoals($token, $userId));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON.']);
        exit;
    }
    $planner->saveGoals($token, $data, $userId);
    echo json_encode(['success' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed.']);
