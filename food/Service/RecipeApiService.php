<?php

declare(strict_types=1);

namespace Toril\Food\Service;

class RecipeApiService
{
    private const BASE = 'https://api.spoonacular.com';

    // Spoonacular caching terms allow keeping responses for up to an hour
    private const CACHE_TTL = 3600;

    // Spoonacular nutrient names mapped to our internal keys
    private const NUTRIENTS = [
        'calories'        => 'Calories',
        'protein_g'       => 'Protein',
        'carbs_g'         => 'Carbohydrates',
        'fat_g'           => 'Fat',
        'fiber_g'         => 'Fiber',
        'sugar_g'         => 'Sugar',
        'sodium_mg'       => 'Sodium',
        'cholesterol_mg'  => 'Cholesterol',
        'saturated_fat_g' => 'Saturated Fat',
        'vitamin_a_ug'    => 'Vitamin A',
        'vitamin_c_mg'    => 'Vitamin C',
        'vitamin_d_ug'    => 'Vitamin D',
        'vitamin_e_mg'    => 'Vitamin E',
        'vitamin_k_ug'    => 'Vitamin K',
        'thiamin_mg'      => 'Vitamin B1',
        'riboflavin_mg'   => 'Vitamin B2',
        'niacin_mg'       => 'Vitamin B3',
        'vitamin_b6_mg'   => 'Vitamin B6',
        'folate_ug'       => 'Folate',
        'vitamin_b12_ug'  => 'Vitamin B12',
        'choline_mg'      => 'Choline',
        'calcium_mg'      => 'Calcium',
        'iron_mg'         => 'Iron',
        'magnesium_mg'    => 'Magnesium',
        'phosphorus_mg'   => 'Phosphorus',
        'potassium_mg'    => 'Potassium',
        'zinc_mg'         => 'Zinc',
        'copper_mg'       => 'Copper',
        'selenium_ug'     => 'Selenium',
        'manganese_mg'    => 'Manganese',
    ];

    // IU → µg for the vitamins Spoonacular may report in IU
    private const IU_TO_UG = [
        'vitamin_a_ug' => 0.3,   // retinol
        'vitamin_d_ug' => 0.025, // 40 IU = 1 µg
    ];

    private const TO_MG = ['g' => 1000, 'mg' => 1, 'µg' => 0.001, 'ug' => 0.001, 'mcg' => 0.001];

    public function __construct(private string $apiKey) {}

    /**
     * Recipes matching the query, with per-serving nutrition.
     *
     * @param int|null $maxCalories only recipes at or under this many kcal per serving
     * @return array{recipes: array, total: int, error?: string}
     */
    public function search(string $query, int $number = 10, int $offset = 0, ?int $maxCalories = null): array
    {
        $params = [
            'query'                => $query,
            'number'               => $number,
            'offset'               => $offset,
            'addRecipeNutrition'   => 'true',
            'addRecipeInformation' => 'true',
            'instructionsRequired' => 'true',
        ];
        if ($maxCalories !== null && $maxCalories > 0) {
            $params['maxCalories'] = $maxCalories;
        }

        $result = $this->request('/recipes/complexSearch?' . http_build_query($params));
        if (isset($result['error'])) {
            return ['recipes' => [], 'total' => 0, 'error' => $result['error']];
        }

        return [
            'recipes' => array_map([$this, 'normalizeRecipe'], $result['results'] ?? []),
            'total'   => $result['totalResults'] ?? 0,
        ];
    }

    /** Recipe in the same shape as a USDA food, nutrients per serving */
    private function normalizeRecipe(array $r): array
    {
        $nutrition = $r['nutrition'] ?? [];
        $weight    = $nutrition['weightPerServing'] ?? null;
        $grams     = ($weight && strtolower((string) ($weight['unit'] ?? '')) === 'g' && $weight['amount'] > 0)
            ? (float) $weight['amount'] : null;

        return [
            'fdc_id'        => $r['id'],
            'name'          => $r['title'],
            'brand'         => $r['sourceName'] ?? null,
            'data_type'     => 'Recipe',
            'serving_size'  => $grams ?? 1,
            'serving_unit'  => $grams ? 'g' : 'serving',
            'recipe_servings' => (int) ($r['servings'] ?? 1),
            'ready_minutes' => $r['readyInMinutes'] ?? null,
            'image'         => $r['image'] ?? null,
            'source_url'    => $r['sourceUrl'] ?? $r['spoonacularSourceUrl'] ?? null,
            'nutrients'     => $this->extractNutrients($nutrition['nutrients'] ?? []),
        ];
    }

    private function extractNutrients(array $items): array
    {
        $byName = [];
        foreach ($items as $n) {
            if (isset($n['name'], $n['amount']) && !isset($byName[$n['name']])) {
                $byName[$n['name']] = $n;
            }
        }

        $out = [];
        foreach (self::NUTRIENTS as $key => $name) {
            $n = $byName[$name] ?? null;
            $out[$key] = $n ? $this->convert($key, (float) $n['amount'], (string) ($n['unit'] ?? '')) : null;
        }
        return $out;
    }

    /** Convert an amount to the unit named by our key's suffix (_g, _mg, _ug) */
    private function convert(string $key, float $amount, string $unit): ?float
    {
        $unit = strtolower(trim($unit));
        if ($key === 'calories') {
            return round($amount, 2);
        }
        if ($unit === 'iu') {
            return isset(self::IU_TO_UG[$key]) ? round($amount * self::IU_TO_UG[$key], 3) : null;
        }
        if (!isset(self::TO_MG[$unit])) {
            return null;
        }

        $mg     = $amount * self::TO_MG[$unit];
        $target = substr($key, strrpos($key, '_') + 1);
        return round($mg / self::TO_MG[$target], 3);
    }

    /** @return array decoded JSON, or ['error' => message] */
    private function request(string $path): array
    {
        $cacheFile = sys_get_temp_dir() . '/spoonacular_' . md5($path) . '.json';
        if (is_file($cacheFile) && time() - filemtime($cacheFile) < self::CACHE_TTL) {
            $cached = json_decode((string) file_get_contents($cacheFile), true);
            if (is_array($cached)) {
                return $cached;
            }
        }

        $ctx = stream_context_create(['http' => [
            'timeout'       => 15,
            'ignore_errors' => true, // read the body of 4xx responses too
            'header'        => "Accept: application/json\r\nx-api-key: {$this->apiKey}\r\n",
        ]]);
        $body = @file_get_contents(self::BASE . $path, false, $ctx);

        if ($body === false) {
            error_log("RecipeApiService: request failed — $path");
            return ['error' => 'Recipe search is unavailable right now. Please try again.'];
        }

        // PHP 8.4+ exposes the response headers via a function; older versions fill $http_response_header
        $headers = function_exists('http_get_last_response_headers')
            ? (http_get_last_response_headers() ?? [])
            : ($http_response_header ?? []);
        $status = 0;
        if (isset($headers[0]) && preg_match('/\s(\d{3})\s/', $headers[0] . ' ', $m)) {
            $status = (int) $m[1];
        }

        $data = json_decode($body, true);
        if ($status === 402) {
            return ['error' => 'The daily recipe search limit has been reached. Please try again tomorrow.'];
        }
        if ($status !== 200 || !is_array($data)) {
            error_log("RecipeApiService: HTTP $status — $path — " . substr($body, 0, 300));
            return ['error' => 'Recipe search is unavailable right now. Please try again.'];
        }

        @file_put_contents($cacheFile, $body);
        return $data;
    }
}
