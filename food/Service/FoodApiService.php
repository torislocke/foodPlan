<?php

declare(strict_types=1);

namespace Toril\Food\Service;

class FoodApiService
{
    private const BASE = 'https://api.nal.usda.gov/fdc/v1';

    // USDA nutrient IDs mapped to our internal keys
    private const NUTRIENTS = [
        'calories'        => 1008,
        'protein_g'       => 1003,
        'fat_g'           => 1004,
        'carbs_g'         => 1005,
        'fiber_g'         => 1079,
        'sugar_g'         => 2000,
        'sodium_mg'       => 1093,
        'cholesterol_mg'  => 1253,
        'saturated_fat_g' => 1258,
    ];

    // Vitamins & minerals: key => [[USDA nutrient ID, multiplier to our unit], …]
    // in order of preference. Branded foods often report A and D in IU only.
    private const MICRONUTRIENTS = [
        'vitamin_a_ug'   => [[1106, 1.0], [1104, 0.3]],   // µg RAE; IU → µg (retinol)
        'vitamin_c_mg'   => [[1162, 1.0]],
        'vitamin_d_ug'   => [[1114, 1.0], [1110, 0.025]], // µg; 40 IU = 1 µg
        'vitamin_e_mg'   => [[1109, 1.0]],
        'vitamin_k_ug'   => [[1185, 1.0]],
        'thiamin_mg'     => [[1165, 1.0]],
        'riboflavin_mg'  => [[1166, 1.0]],
        'niacin_mg'      => [[1167, 1.0]],
        'vitamin_b6_mg'  => [[1175, 1.0]],
        'folate_ug'      => [[1190, 1.0], [1177, 1.0]],   // µg DFE, else total folate
        'vitamin_b12_ug' => [[1178, 1.0]],
        'choline_mg'     => [[1180, 1.0]],
        'calcium_mg'     => [[1087, 1.0]],
        'iron_mg'        => [[1089, 1.0]],
        'magnesium_mg'   => [[1090, 1.0]],
        'phosphorus_mg'  => [[1091, 1.0]],
        'potassium_mg'   => [[1092, 1.0]],
        'zinc_mg'        => [[1095, 1.0]],
        'copper_mg'      => [[1098, 1.0]],
        'selenium_ug'    => [[1103, 1.0]],
        'manganese_mg'   => [[1101, 1.0]],
    ];

    /** @return string[] internal keys of the vitamins & minerals */
    public static function micronutrientKeys(): array
    {
        return array_keys(self::MICRONUTRIENTS);
    }

    public function __construct(private string $apiKey) {}

    public function search(string $query, int $pageSize = 15, int $page = 1): array
    {
        $url = self::BASE . '/foods/search?' . http_build_query([
            'query'      => $query,
            'pageSize'   => $pageSize,
            'pageNumber' => $page,
            'api_key'    => $this->apiKey,
        ]);

        $data = $this->request($url);
        if ($data === null) {
            return ['foods' => [], 'total' => 0];
        }

        return [
            'foods' => array_map([$this, 'normalizeSearchFood'], $data['foods'] ?? []),
            'total' => $data['totalHits'] ?? 0,
        ];
    }

    public function getFood(int $fdcId): ?array
    {
        $url  = self::BASE . '/food/' . $fdcId . '?' . http_build_query(['api_key' => $this->apiKey]);
        $data = $this->request($url);
        return $data ? $this->normalizeFoodDetail($data) : null;
    }

    private function normalizeSearchFood(array $f): array
    {
        [$size, $unit] = $this->metricServing($f['servingSize'] ?? null, $f['servingSizeUnit'] ?? null);

        return [
            'fdc_id'       => $f['fdcId'],
            'name'         => $f['description'],
            'brand'        => $f['brandName'] ?? $f['brandOwner'] ?? null,
            'data_type'    => $f['dataType'] ?? null,
            'serving_size' => $size,
            'serving_unit' => $unit,
            'nutrients'    => $this->extractNutrients($f['foodNutrients'] ?? [], $size / 100),
        ];
    }

    /**
     * USDA nutrient values are per 100 g (or 100 ml). Branded foods give a label
     * serving in g/ml; anything else falls back to a 100 g serving.
     *
     * @return array{0: float, 1: string}
     */
    private function metricServing(mixed $size, ?string $unit): array
    {
        $unit = strtolower((string) $unit);
        if (is_numeric($size) && $size > 0) {
            if (in_array($unit, ['g', 'grm'], true)) {
                return [(float) $size, 'g'];
            }
            if (in_array($unit, ['ml', 'mlt'], true)) {
                return [(float) $size, 'ml'];
            }
        }
        return [100.0, 'g'];
    }

    private function normalizeFoodDetail(array $f): array
    {
        $servingSize = $f['servingSize'] ?? 100;
        $servingUnit = $f['servingSizeUnit'] ?? 'g';

        if (!isset($f['servingSize']) && !empty($f['foodPortions'])) {
            $p           = $f['foodPortions'][0];
            $servingSize = $p['gramWeight'] ?? 100;
            $servingUnit = $p['portionDescription'] ?? 'g';
        }

        // Nutrients are per 100 g; scale when the serving is a gram weight
        $grams = isset($f['servingSize'])
            ? $this->metricServing($f['servingSize'], $f['servingSizeUnit'] ?? null)[0]
            : (float) ($f['foodPortions'][0]['gramWeight'] ?? 100);

        return [
            'fdc_id'       => $f['fdcId'],
            'name'         => $f['description'],
            'brand'        => $f['brandName'] ?? $f['brandOwner'] ?? null,
            'data_type'    => $f['dataType'] ?? null,
            'serving_size' => $servingSize,
            'serving_unit' => $servingUnit,
            'nutrients'    => $this->extractNutrients($f['foodNutrients'] ?? [], $grams / 100),
        ];
    }

    /**
     * @param float $factor multiplier from per-100 g values to one serving
     */
    private function extractNutrients(array $items, float $factor = 1.0): array
    {
        // First value reported for each USDA nutrient ID (some foods list duplicates)
        $byId = [];
        foreach ($items as $n) {
            // Search results: nutrientId + value  |  Detail: nutrient.id + amount
            $id  = $n['nutrientId'] ?? $n['nutrient']['id'] ?? null;
            $val = $n['value']      ?? $n['amount']         ?? null;

            if ($id !== null && $val !== null && !isset($byId[(int) $id])) {
                $byId[(int) $id] = (float) $val;
            }
        }

        $out = [];
        foreach (self::NUTRIENTS as $key => $id) {
            $out[$key] = isset($byId[$id]) ? round($byId[$id] * $factor, 2) : null;
        }
        foreach (self::MICRONUTRIENTS as $key => $sources) {
            $out[$key] = null;
            foreach ($sources as [$id, $mult]) {
                if (isset($byId[$id])) {
                    $out[$key] = round($byId[$id] * $mult * $factor, 3);
                    break;
                }
            }
        }

        return $out;
    }

    private function request(string $url): ?array
    {
        $ctx  = stream_context_create(['http' => [
            'timeout' => 10,
            'header'  => "Accept: application/json\r\n",
        ]]);
        $body = @file_get_contents($url, false, $ctx);

        if ($body === false) {
            error_log("FoodApiService: request failed — $url");
            return null;
        }

        $data = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("FoodApiService: JSON parse error — $url");
            return null;
        }

        return $data;
    }
}
