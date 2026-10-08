<?php

declare(strict_types=1);

$appRoot = is_dir(dirname(__DIR__) . '/food/config')
    ? dirname(__DIR__)                    // local: food/ inside the project
    : dirname($_SERVER['DOCUMENT_ROOT']); // server: food/ beside public_html
require_once $appRoot . '/food/config/planner_bootstrap.php';

use Toril\Food\Service\FoodApiService;

header('Content-Type: application/json');

$q    = trim($_GET['q'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));

if (strlen($q) < 2) {
    echo json_encode(['foods' => [], 'total' => 0]);
    exit;
}

$service = new FoodApiService(USDA_API_KEY);
echo json_encode($service->search($q, 15, $page));
