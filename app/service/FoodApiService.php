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
        return [
            'fdc_id'       => $f['fdcId'],
            'name'         => $f['description'],
            'brand'        => $f['brandName'] ?? $f['brandOwner'] ?? null,
            'data_type'    => $f['dataType'] ?? null,
            'serving_size' => $f['servingSize'] ?? 100,
            'serving_unit' => $f['servingSizeUnit'] ?? 'g',
            'nutrients'    => $this->extractNutrients($f['foodNutrients'] ?? []),
        ];
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

        return [
            'fdc_id'       => $f['fdcId'],
            'name'         => $f['description'],
            'brand'        => $f['brandName'] ?? $f['brandOwner'] ?? null,
            'data_type'    => $f['dataType'] ?? null,
            'serving_size' => $servingSize,
            'serving_unit' => $servingUnit,
            'nutrients'    => $this->extractNutrients($f['foodNutrients'] ?? []),
        ];
    }

    private function extractNutrients(array $items): array
    {
        $out = array_fill_keys(array_keys(self::NUTRIENTS), null);

        foreach ($items as $n) {
            // Search results: nutrientId + value  |  Detail: nutrient.id + amount
            $id  = $n['nutrientId']      ?? $n['nutrient']['id'] ?? null;
            $val = $n['value']           ?? $n['amount']         ?? null;

            if ($id === null || $val === null) {
                continue;
            }

            foreach (self::NUTRIENTS as $key => $targetId) {
                if ((int) $id === $targetId) {
                    $out[$key] = round((float) $val, 2);
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
