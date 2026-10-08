<?php

declare(strict_types=1);

namespace Toril\Food\Service;

/**
 * BMI and daily calorie needs.
 *
 * BMR uses the Mifflin-St Jeor equation; total daily energy expenditure (TDEE)
 * applies a standard activity multiplier. Weight-change targets assume
 * ~3,500 kcal per pound of body weight (500 kcal/day ≈ 1 lb/week).
 */
class FitnessService
{
    public const ACTIVITY = [
        'sedentary'   => ['label' => 'Sedentary (little or no exercise)',      'factor' => 1.2],
        'light'       => ['label' => 'Lightly active (1–3 days/week)',        'factor' => 1.375],
        'moderate'    => ['label' => 'Moderately active (3–5 days/week)',     'factor' => 1.55],
        'active'      => ['label' => 'Very active (6–7 days/week)',           'factor' => 1.725],
        'very_active' => ['label' => 'Extra active (physical job or 2x/day)', 'factor' => 1.9],
    ];

    // kcal/day offset from maintenance, and approximate weekly change in lb
    private const PLANS = [
        'lose'      => ['label' => 'Lose weight',      'offset' => -500, 'weekly_lb' => -1.0],
        'mild_lose' => ['label' => 'Mild weight loss', 'offset' => -250, 'weekly_lb' => -0.5],
        'maintain'  => ['label' => 'Maintain weight',  'offset' => 0,    'weekly_lb' => 0.0],
        'mild_gain' => ['label' => 'Mild weight gain', 'offset' => 250,  'weekly_lb' => 0.5],
        'gain'      => ['label' => 'Gain weight',      'offset' => 500,  'weekly_lb' => 1.0],
    ];

    // Commonly cited minimum daily intake without medical supervision
    private const MIN_CALORIES = ['male' => 1500, 'female' => 1200];

    private const LIMITS = [
        'height_cm' => [100, 250],
        'weight_kg' => [30, 300],
        'age'       => [18, 100],
    ];

    /**
     * Validate input; returns a list of error messages (empty when valid).
     */
    public function validate(array $in): array
    {
        $errors = [];
        foreach (self::LIMITS as $field => [$min, $max]) {
            $v = $in[$field] ?? null;
            if (!is_numeric($v) || $v < $min || $v > $max) {
                $errors[] = match ($field) {
                    'height_cm' => 'Height must be between 100 and 250 cm (3\'4" – 8\'2").',
                    'weight_kg' => 'Weight must be between 30 and 300 kg (66 – 660 lb).',
                    'age'       => 'Age must be between 18 and 100. These formulas are for adults.',
                };
            }
        }
        if (!in_array($in['gender'] ?? '', ['male', 'female'], true)) {
            $errors[] = 'Please choose male or female.';
        }
        if (!isset(self::ACTIVITY[$in['activity'] ?? ''])) {
            $errors[] = 'Please choose an activity level.';
        }
        return $errors;
    }

    /**
     * Expects validated input: height_cm, weight_kg, age, gender, activity.
     */
    public function calculate(array $in): array
    {
        $height = (float) $in['height_cm'];
        $weight = (float) $in['weight_kg'];
        $age    = (int)   $in['age'];
        $gender = $in['gender'];

        $bmi = $weight / (($height / 100) ** 2);

        $bmr  = 10 * $weight + 6.25 * $height - 5 * $age + ($gender === 'male' ? 5 : -161);
        $tdee = $bmr * self::ACTIVITY[$in['activity']]['factor'];
        $min  = self::MIN_CALORIES[$gender];

        $plans = [];
        foreach (self::PLANS as $key => $p) {
            $raw = $tdee + $p['offset'];
            // Only weight-loss targets are raised to the safe minimum
            $floored = $p['offset'] < 0 && $raw < $min;
            $plans[] = [
                'key'       => $key,
                'label'     => $p['label'],
                'calories'  => (int) (round(($floored ? $min : $raw) / 10) * 10),
                'weekly_lb' => $p['weekly_lb'],
                'floored'   => $floored,
            ];
        }

        return [
            'bmi'          => round($bmi, 1),
            'bmi_category' => $this->bmiCategory($bmi),
            'bmi_status'   => $this->bmiStatus($bmi),
            'bmr'          => (int) round($bmr),
            'tdee'         => (int) round($tdee),
            'min_calories' => $min,
            'plans'        => $plans,
        ];
    }

    private function bmiCategory(float $bmi): string
    {
        return match (true) {
            $bmi < 18.5 => 'Underweight',
            $bmi < 25   => 'Healthy weight',
            $bmi < 30   => 'Overweight',
            default     => 'Obese',
        };
    }

    // Matches the site's color key: good (green) / caution (yellow) / warning (red)
    private function bmiStatus(float $bmi): string
    {
        return match (true) {
            $bmi < 17   => 'warning',
            $bmi < 18.5 => 'caution',
            $bmi < 25   => 'good',
            $bmi < 30   => 'caution',
            default     => 'warning',
        };
    }
}
