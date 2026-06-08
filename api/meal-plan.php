<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/config/planner_bootstrap.php';

use Toril\Food\Service\MealPlannerService;

header('Content-Type: application/json');

$planner = new MealPlannerService($pdo);
$token   = MealPlannerService::getToken();
$userId  = $currentUserId ?? null;
$method  = $_SERVER['REQUEST_METHOD'];

// ── GET: fetch week entries + daily summaries ─────────────────────────────── //
if ($method === 'GET') {
    $weekStart = $_GET['week'] ?? MealPlannerService::weekStart();
    $entries   = $planner->getWeekEntries($token, $weekStart, $userId);
    $summaries = $planner->getDailySummaries($entries);
    echo json_encode(['entries' => $entries, 'summaries' => $summaries]);
    exit;
}

// ── POST: add a meal entry ────────────────────────────────────────────────── //
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $required = ['week_start', 'day_of_week', 'meal_type', 'fdc_id', 'food_name'];
    foreach ($required as $field) {
        if (!isset($data[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Missing field: $field"]);
            exit;
        }
    }

    $validMeals = ['breakfast', 'lunch', 'dinner', 'snack'];
    if (!in_array($data['meal_type'], $validMeals, true)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid meal_type.']);
        exit;
    }

    $planId  = $planner->getOrCreatePlan($token, $data['week_start'], $userId);
    $entryId = $planner->addEntry($planId, $data);

    echo json_encode(['success' => true, 'id' => $entryId]);
    exit;
}

// ── DELETE: remove a meal entry ───────────────────────────────────────────── //
if ($method === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id   = (int) ($data['id'] ?? 0);

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid entry id.']);
        exit;
    }

    echo json_encode(['success' => $planner->deleteEntry($id, $token, $userId)]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed.']);
