<?php

declare(strict_types=1);

$appRoot = is_dir(dirname(__DIR__) . '/food/config')
    ? dirname(__DIR__)                    // local: food/ inside the project
    : dirname($_SERVER['DOCUMENT_ROOT']); // server: food/ beside public_html
require_once $appRoot . '/food/config/planner_bootstrap.php';

use Toril\Food\Service\MealPlannerService;
use Toril\Food\Service\MicronutrientService;

header('Content-Type: application/json');

$planner = new MealPlannerService($pdo);
$token   = MealPlannerService::getToken();
$userId  = $currentUserId ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $goals = $planner->getGoals($token, $userId);
    // Vitamin & mineral targets follow the profile's gender and age
    $goals['micronutrient_targets'] = (new MicronutrientService())->targets(
        $goals['gender'] ?? null,
        isset($goals['age']) ? (int) $goals['age'] : null
    );
    echo json_encode($goals);
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
