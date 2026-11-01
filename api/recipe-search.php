<?php

declare(strict_types=1);

$appRoot = is_dir(dirname(__DIR__) . '/food/config')
    ? dirname(__DIR__)                    // local: food/ inside the project
    : dirname($_SERVER['DOCUMENT_ROOT']); // server: food/ beside public_html
require_once $appRoot . '/food/config/planner_bootstrap.php';

use Toril\Food\Service\RecipeApiService;

header('Content-Type: application/json');

$q           = trim($_GET['q'] ?? '');
$offset      = max(0, (int) ($_GET['offset'] ?? 0));
$maxCalories = isset($_GET['max_calories']) ? (int) $_GET['max_calories'] : null;

if (strlen($q) < 2) {
    echo json_encode(['recipes' => [], 'total' => 0]);
    exit;
}

if (!defined('spoonacular_API') || spoonacular_API === '') {
    http_response_code(503);
    echo json_encode(['recipes' => [], 'total' => 0, 'error' => 'Recipe search is not configured.']);
    exit;
}

$service = new RecipeApiService(spoonacular_API);
$result  = $service->search($q, 10, $offset, $maxCalories);
if (isset($result['error'])) {
    http_response_code(502);
}
echo json_encode($result);
