<?php

declare(strict_types=1);

$appRoot = is_dir(dirname(__DIR__) . '/food/config')
    ? dirname(__DIR__)                    // local: food/ inside the project
    : dirname($_SERVER['DOCUMENT_ROOT']); // server: food/ beside public_html
require_once $appRoot . '/food/config/planner_bootstrap.php';

use Toril\Food\Service\FitnessService;

header('Content-Type: application/json');

// POST { height_cm, weight_kg, age, gender, activity } → BMI + calorie targets
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON.']);
    exit;
}

$fitness = new FitnessService();
$errors  = $fitness->validate($data);
if ($errors) {
    http_response_code(422);
    echo json_encode(['error' => implode(' ', $errors)]);
    exit;
}

echo json_encode($fitness->calculate($data));
