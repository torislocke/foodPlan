<?php

declare(strict_types=1);

namespace Toril\Food\Service;

/**
 * Daily vitamin & mineral targets.
 *
 * With a profile (gender + age) targets are the National Academies Dietary
 * Reference Intakes for adults: the RDA, or the AI where no RDA exists
 * (vitamin K, choline, potassium, manganese). Without one, the FDA Daily
 * Values used on US nutrition labels are the fallback.
 *
 * Upper limits (UL) are listed only where food intake counts toward them.
 * Vitamin A (RAE includes plant carotenoids), vitamin E, niacin, folate and
 * magnesium have ULs that apply to supplements/fortificants only, so food
 * totals are never flagged for those.
 */
class MicronutrientService
{
    // key => [label, unit, group]  (keys match FoodApiService::MICRONUTRIENTS)
    private const NUTRIENTS = [
        'vitamin_a_ug'   => ['Vitamin A',   'µg', 'vitamins'],
        'vitamin_c_mg'   => ['Vitamin C',   'mg', 'vitamins'],
        'vitamin_d_ug'   => ['Vitamin D',   'µg', 'vitamins'],
        'vitamin_e_mg'   => ['Vitamin E',   'mg', 'vitamins'],
        'vitamin_k_ug'   => ['Vitamin K',   'µg', 'vitamins'],
        'thiamin_mg'     => ['Thiamin (B1)',    'mg', 'vitamins'],
        'riboflavin_mg'  => ['Riboflavin (B2)', 'mg', 'vitamins'],
        'niacin_mg'      => ['Niacin (B3)',     'mg', 'vitamins'],
        'vitamin_b6_mg'  => ['Vitamin B6',  'mg', 'vitamins'],
        'folate_ug'      => ['Folate',      'µg', 'vitamins'],
        'vitamin_b12_ug' => ['Vitamin B12', 'µg', 'vitamins'],
        'choline_mg'     => ['Choline',     'mg', 'vitamins'],
        'calcium_mg'     => ['Calcium',     'mg', 'minerals'],
        'iron_mg'        => ['Iron',        'mg', 'minerals'],
        'magnesium_mg'   => ['Magnesium',   'mg', 'minerals'],
        'phosphorus_mg'  => ['Phosphorus',  'mg', 'minerals'],
        'potassium_mg'   => ['Potassium',   'mg', 'minerals'],
        'zinc_mg'        => ['Zinc',        'mg', 'minerals'],
        'copper_mg'      => ['Copper',      'mg', 'minerals'],
        'selenium_ug'    => ['Selenium',    'µg', 'minerals'],
        'manganese_mg'   => ['Manganese',   'mg', 'minerals'],
    ];

    // FDA Daily Values (adults & children 4+), 21 CFR 101.9
    private const DAILY_VALUES = [
        'vitamin_a_ug' => 900, 'vitamin_c_mg' => 90, 'vitamin_d_ug' => 20, 'vitamin_e_mg' => 15,
        'vitamin_k_ug' => 120, 'thiamin_mg' => 1.2, 'riboflavin_mg' => 1.3, 'niacin_mg' => 16,
        'vitamin_b6_mg' => 1.7, 'folate_ug' => 400, 'vitamin_b12_ug' => 2.4, 'choline_mg' => 550,
        'calcium_mg' => 1300, 'iron_mg' => 18, 'magnesium_mg' => 420, 'phosphorus_mg' => 1250,
        'potassium_mg' => 4700, 'zinc_mg' => 11, 'copper_mg' => 0.9, 'selenium_ug' => 55,
        'manganese_mg' => 2.3,
    ];

    public function targets(?string $gender, ?int $age): array
    {
        $personal = in_array($gender, ['male', 'female'], true) && $age !== null && $age >= 19;

        $list = [];
        foreach (self::NUTRIENTS as $key => [$label, $unit, $group]) {
            $list[] = [
                'key'    => $key,
                'label'  => $label,
                'unit'   => $unit,
                'group'  => $group,
                'target' => $personal ? $this->dri($key, $gender, $age) : self::DAILY_VALUES[$key],
                'upper'  => $this->upperLimit($key, $age),
            ];
        }

        return [
            'basis'     => $personal
                ? sprintf('Recommended for %s, %s', $gender === 'male' ? 'men' : 'women', $this->ageBand($age))
                : 'FDA Daily Values',
            'personal'  => $personal,
            'nutrients' => $list,
        ];
    }

    private function ageBand(int $age): string
    {
        return match (true) {
            $age <= 30 => 'ages 19–30',
            $age <= 50 => 'ages 31–50',
            $age <= 70 => 'ages 51–70',
            default    => 'ages 71+',
        };
    }

    // Dietary Reference Intakes for adults 19+ (RDA, or AI where marked)
    private function dri(string $key, string $gender, int $age): float
    {
        $m = $gender === 'male';

        return match ($key) {
            'vitamin_a_ug'   => $m ? 900 : 700,
            'vitamin_c_mg'   => $m ? 90 : 75,
            'vitamin_d_ug'   => $age > 70 ? 20 : 15,
            'vitamin_e_mg'   => 15,
            'vitamin_k_ug'   => $m ? 120 : 90,                         // AI
            'thiamin_mg'     => $m ? 1.2 : 1.1,
            'riboflavin_mg'  => $m ? 1.3 : 1.1,
            'niacin_mg'      => $m ? 16 : 14,
            'vitamin_b6_mg'  => $age <= 50 ? 1.3 : ($m ? 1.7 : 1.5),
            'folate_ug'      => 400,
            'vitamin_b12_ug' => 2.4,
            'choline_mg'     => $m ? 550 : 425,                        // AI
            'calcium_mg'     => ($m ? $age > 70 : $age > 50) ? 1200 : 1000,
            'iron_mg'        => (!$m && $age <= 50) ? 18 : 8,
            'magnesium_mg'   => $m ? ($age <= 30 ? 400 : 420) : ($age <= 30 ? 310 : 320),
            'phosphorus_mg'  => 700,
            'potassium_mg'   => $m ? 3400 : 2600,                      // AI (2019)
            'zinc_mg'        => $m ? 11 : 8,
            'copper_mg'      => 0.9,
            'selenium_ug'    => 55,
            'manganese_mg'   => $m ? 2.3 : 1.8,                        // AI
        };
    }

    private function upperLimit(string $key, ?int $age): ?float
    {
        $age ??= 30;

        return match ($key) {
            'vitamin_c_mg'  => 2000,
            'vitamin_d_ug'  => 100,
            'vitamin_b6_mg' => 100,
            'choline_mg'    => 3500,
            'calcium_mg'    => $age <= 50 ? 2500 : 2000,
            'iron_mg'       => 45,
            'phosphorus_mg' => $age <= 70 ? 4000 : 3000,
            'zinc_mg'       => 40,
            'copper_mg'     => 10,
            'selenium_ug'   => 400,
            'manganese_mg'  => 11,
            default         => null,
        };
    }
}
