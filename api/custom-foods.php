<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/config/planner_bootstrap.php';

use Toril\Food\Service\MealPlannerService;

header('Content-Type: application/json');

$token  = MealPlannerService::getToken();
$userId = $currentUserId ?? null;
$method = $_SERVER['REQUEST_METHOD'];

// ── GET: list all custom foods for this user/session ─────────────────────── //
if ($method === 'GET') {
    if ($userId) {
        $st = $pdo->prepare('SELECT * FROM custom_foods WHERE user_id = ? ORDER BY food_name');
        $st->execute([$userId]);
    } else {
        $st = $pdo->prepare('SELECT * FROM custom_foods WHERE session_token = ? ORDER BY food_name');
        $st->execute([$token]);
    }
    echo json_encode($st->fetchAll());
    exit;
}

// ── POST: create a new custom food ────────────────────────────────────────── //
if ($method === 'POST') {
    $d = json_decode(file_get_contents('php://input'), true);

    if (!$d || empty($d['food_name']) || !isset($d['calories'])) {
        http_response_code(400);
        echo json_encode(['error' => 'food_name and calories are required.']);
        exit;
    }

    $num = fn($key) => isset($d[$key]) && $d[$key] !== '' && $d[$key] !== null
        ? (float) $d[$key] : null;

    $st = $pdo->prepare('
        INSERT INTO custom_foods
            (session_token, user_id, food_name, brand_name, serving_size, serving_unit,
             calories, protein_g, carbs_g, fat_g, fiber_g,
             sodium_mg, sugar_g, cholesterol_mg, saturated_fat_g)
        VALUES
            (:tok, :uid, :name, :brand, :sz, :su,
             :cal, :pro, :car, :fat, :fib,
             :sod, :sug, :cho, :sat)
    ');

    $st->execute([
        ':tok'  => $token,
        ':uid'  => $userId,
        ':name' => trim($d['food_name']),
        ':brand'=> isset($d['brand_name']) && $d['brand_name'] !== '' ? trim($d['brand_name']) : null,
        ':sz'   => (float) ($d['serving_size'] ?? 100),
        ':su'   => $d['serving_unit'] ?? 'g',
        ':cal'  => (float) $d['calories'],
        ':pro'  => $num('protein_g'),
        ':car'  => $num('carbs_g'),
        ':fat'  => $num('fat_g'),
        ':fib'  => $num('fiber_g'),
        ':sod'  => $num('sodium_mg'),
        ':sug'  => $num('sugar_g'),
        ':cho'  => $num('cholesterol_mg'),
        ':sat'  => $num('saturated_fat_g'),
    ]);

    $id = (int) $pdo->lastInsertId();

    // Return the new food in the same shape the JS food-object expects
    echo json_encode([
        'success' => true,
        'food'    => [
            'fdc_id'       => $id,
            'name'         => trim($d['food_name']),
            'brand'        => isset($d['brand_name']) && $d['brand_name'] !== '' ? trim($d['brand_name']) : null,
            'data_type'    => 'Custom',
            'serving_size' => (float) ($d['serving_size'] ?? 100),
            'serving_unit' => $d['serving_unit'] ?? 'g',
            'nutrients'    => [
                'calories'        => (float) $d['calories'],
                'protein_g'       => $num('protein_g'),
                'carbs_g'         => $num('carbs_g'),
                'fat_g'           => $num('fat_g'),
                'fiber_g'         => $num('fiber_g'),
                'sodium_mg'       => $num('sodium_mg'),
                'sugar_g'         => $num('sugar_g'),
                'cholesterol_mg'  => $num('cholesterol_mg'),
                'saturated_fat_g' => $num('saturated_fat_g'),
            ],
            '_isCustom' => true,
        ],
        // Also return the raw DB row for state.customFoods
        'row' => [
            'id'             => $id,
            'food_name'      => trim($d['food_name']),
            'brand_name'     => isset($d['brand_name']) && $d['brand_name'] !== '' ? trim($d['brand_name']) : null,
            'serving_size'   => (float) ($d['serving_size'] ?? 100),
            'serving_unit'   => $d['serving_unit'] ?? 'g',
            'calories'       => (float) $d['calories'],
            'protein_g'      => $num('protein_g'),
            'carbs_g'        => $num('carbs_g'),
            'fat_g'          => $num('fat_g'),
            'fiber_g'        => $num('fiber_g'),
            'sodium_mg'      => $num('sodium_mg'),
            'sugar_g'        => $num('sugar_g'),
            'cholesterol_mg' => $num('cholesterol_mg'),
            'saturated_fat_g'=> $num('saturated_fat_g'),
        ],
    ]);
    exit;
}

// ── DELETE: remove a custom food ──────────────────────────────────────────── //
if ($method === 'DELETE') {
    $d  = json_decode(file_get_contents('php://input'), true);
    $id = (int) ($d['id'] ?? 0);

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid id.']);
        exit;
    }

    if ($userId) {
        $st = $pdo->prepare('DELETE FROM custom_foods WHERE id = ? AND user_id = ?');
        $st->execute([$id, $userId]);
    } else {
        $st = $pdo->prepare('DELETE FROM custom_foods WHERE id = ? AND session_token = ?');
        $st->execute([$id, $token]);
    }
    echo json_encode(['success' => $st->rowCount() > 0]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed.']);
