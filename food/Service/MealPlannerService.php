<?php

declare(strict_types=1);

namespace Toril\Food\Service;

class MealPlannerService
{
    public function __construct(private \PDO $pdo) {}

    // ── Session token ─────────────────────────────────────────────────────── //

    /**
     * Value stored in session_token (unique per plan week / goals row). Account
     * rows use a per-user key so a browser token already tied to another
     * account (e.g. after switching users) can never collide.
     */
    private static function ownerKey(string $token, ?int $userId): string
    {
        return $userId ? 'user-' . $userId : $token;
    }

    public static function getToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['planner_token'])) {
            $_SESSION['planner_token'] = bin2hex(random_bytes(16));
        }
        return $_SESSION['planner_token'];
    }

    // ── Nutritional goals ─────────────────────────────────────────────────── //

    public function getGoals(string $token, ?int $userId = null): array
    {
        if ($userId) {
            $st = $this->pdo->prepare('SELECT * FROM nutritional_goals WHERE user_id = ? LIMIT 1');
            $st->execute([$userId]);
        } else {
            $st = $this->pdo->prepare('SELECT * FROM nutritional_goals WHERE session_token = ? LIMIT 1');
            $st->execute([$token]);
        }
        $goals = $st->fetch();
        if (!$goals) {
            return $this->defaultGoals();
        }
        // Internal columns stay server-side
        unset($goals['id'], $goals['session_token'], $goals['user_id'], $goals['created_at'], $goals['updated_at']);
        return $goals;
    }

    public function saveGoals(string $token, array $g, ?int $userId = null): void
    {
        // Locate existing record
        if ($userId) {
            $st = $this->pdo->prepare('SELECT id FROM nutritional_goals WHERE user_id = ? LIMIT 1');
            $st->execute([$userId]);
        } else {
            $st = $this->pdo->prepare('SELECT id FROM nutritional_goals WHERE session_token = ? LIMIT 1');
            $st->execute([$token]);
        }
        $existing = $st->fetch();

        $vals = [
            (int)   ($g['calories']  ?? 2000),
            (float) ($g['protein_g'] ?? 50),
            (float) ($g['carbs_g']   ?? 250),
            (float) ($g['fat_g']     ?? 65),
            (float) ($g['fiber_g']   ?? 25),
            (float) ($g['sodium_mg'] ?? 2300),
            // Optional fitness profile (null when not provided)
            is_numeric($g['height_cm'] ?? null) ? (float) $g['height_cm'] : null,
            is_numeric($g['weight_kg'] ?? null) ? (float) $g['weight_kg'] : null,
            is_numeric($g['age'] ?? null)       ? (int)   $g['age']       : null,
            in_array($g['gender'] ?? null, ['male', 'female'], true) ? $g['gender'] : null,
            isset(FitnessService::ACTIVITY[$g['activity_level'] ?? '']) ? $g['activity_level'] : null,
            in_array($g['weight_goal'] ?? null, ['lose', 'mild_lose', 'maintain', 'mild_gain', 'gain'], true)
                ? $g['weight_goal'] : null,
        ];

        if ($existing) {
            $this->pdo->prepare('
                UPDATE nutritional_goals
                SET calories=?, protein_g=?, carbs_g=?, fat_g=?, fiber_g=?, sodium_mg=?,
                    height_cm=?, weight_kg=?, age=?, gender=?, activity_level=?, weight_goal=?,
                    updated_at=NOW()
                WHERE id=?
            ')->execute([...$vals, $existing['id']]);
        } else {
            $this->pdo->prepare('
                INSERT INTO nutritional_goals
                    (session_token, user_id, calories, protein_g, carbs_g, fat_g, fiber_g, sodium_mg,
                     height_cm, weight_kg, age, gender, activity_level, weight_goal)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ')->execute([self::ownerKey($token, $userId), $userId, ...$vals]);
        }
    }

    // ── Meal plan ─────────────────────────────────────────────────────────── //

    public function getOrCreatePlan(string $token, string $weekStart, ?int $userId = null): int
    {
        if ($userId) {
            $st = $this->pdo->prepare('SELECT id FROM meal_plans WHERE user_id = ? AND week_start = ?');
            $st->execute([$userId, $weekStart]);
        } else {
            $st = $this->pdo->prepare('SELECT id FROM meal_plans WHERE session_token = ? AND week_start = ?');
            $st->execute([$token, $weekStart]);
        }
        $row = $st->fetch();

        if ($row) {
            return (int) $row['id'];
        }

        $this->pdo->prepare(
            'INSERT INTO meal_plans (session_token, user_id, week_start) VALUES (?, ?, ?)'
        )->execute([self::ownerKey($token, $userId), $userId, $weekStart]);

        return (int) $this->pdo->lastInsertId();
    }

    public function getWeekEntries(string $token, string $weekStart, ?int $userId = null): array
    {
        if ($userId) {
            $st = $this->pdo->prepare('
                SELECT me.*
                FROM meal_entries me
                JOIN meal_plans mp ON me.meal_plan_id = mp.id
                WHERE mp.user_id = ? AND mp.week_start = ?
                ORDER BY me.day_of_week,
                         FIELD(me.meal_type, "breakfast","lunch","dinner","snack"),
                         me.id
            ');
            $st->execute([$userId, $weekStart]);
        } else {
            $st = $this->pdo->prepare('
                SELECT me.*
                FROM meal_entries me
                JOIN meal_plans mp ON me.meal_plan_id = mp.id
                WHERE mp.session_token = ? AND mp.week_start = ?
                ORDER BY me.day_of_week,
                         FIELD(me.meal_type, "breakfast","lunch","dinner","snack"),
                         me.id
            ');
            $st->execute([$token, $weekStart]);
        }
        return $st->fetchAll();
    }

    public function addEntry(int $planId, array $d): int
    {
        $n = $d['nutrients'] ?? [];
        $s = (float) ($d['servings'] ?? 1);

        $scale = fn($v) => $v !== null ? round((float) $v * $s, 2) : null;

        // Vitamins & minerals as JSON; NULL when the food reported none
        $micros = [];
        foreach (FoodApiService::micronutrientKeys() as $k) {
            if (isset($n[$k]) && is_numeric($n[$k])) {
                $micros[$k] = round((float) $n[$k] * $s, 3);
            }
        }

        $this->pdo->prepare('
            INSERT INTO meal_entries
                (meal_plan_id, day_of_week, meal_type, fdc_id, food_name, brand_name,
                 serving_size, serving_unit, servings,
                 calories, protein_g, carbs_g, fat_g, fiber_g,
                 sodium_mg, sugar_g, cholesterol_mg, saturated_fat_g, micronutrients)
            VALUES
                (:pid, :day, :meal, :fdc, :name, :brand,
                 :sz, :su, :sv,
                 :cal, :pro, :car, :fat, :fib,
                 :sod, :sug, :cho, :sat, :mic)
        ')->execute([
            ':pid'  => $planId,
            ':day'  => (int)    $d['day_of_week'],
            ':meal' => $d['meal_type'],
            ':fdc'  => (int)    $d['fdc_id'],
            ':name' => $d['food_name'],
            ':brand'=> $d['brand_name'] ?? null,
            ':sz'   => (float) ($d['serving_size'] ?? 100),
            ':su'   => $d['serving_unit'] ?? 'g',
            ':sv'   => $s,
            ':cal'  => $scale($n['calories']        ?? null),
            ':pro'  => $scale($n['protein_g']       ?? null),
            ':car'  => $scale($n['carbs_g']         ?? null),
            ':fat'  => $scale($n['fat_g']           ?? null),
            ':fib'  => $scale($n['fiber_g']         ?? null),
            ':sod'  => $scale($n['sodium_mg']       ?? null),
            ':sug'  => $scale($n['sugar_g']         ?? null),
            ':cho'  => $scale($n['cholesterol_mg']  ?? null),
            ':sat'  => $scale($n['saturated_fat_g'] ?? null),
            ':mic'  => $micros ? json_encode($micros) : null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function deleteEntry(int $entryId, string $token, ?int $userId = null): bool
    {
        if ($userId) {
            $st = $this->pdo->prepare('
                DELETE me FROM meal_entries me
                JOIN meal_plans mp ON me.meal_plan_id = mp.id
                WHERE me.id = ? AND mp.user_id = ?
            ');
            $st->execute([$entryId, $userId]);
        } else {
            $st = $this->pdo->prepare('
                DELETE me FROM meal_entries me
                JOIN meal_plans mp ON me.meal_plan_id = mp.id
                WHERE me.id = ? AND mp.session_token = ?
            ');
            $st->execute([$entryId, $token]);
        }
        return $st->rowCount() > 0;
    }

    // ── Nutrition summaries ───────────────────────────────────────────────── //

    public function getDailySummaries(array $entries): array
    {
        $keys       = ['calories', 'protein_g', 'carbs_g', 'fat_g', 'fiber_g', 'sodium_mg', 'sugar_g'];
        $microKeys  = FoodApiService::micronutrientKeys();
        $days       = [];

        foreach ($entries as $e) {
            $d = (int) $e['day_of_week'];
            if (!isset($days[$d])) {
                $days[$d] = array_fill_keys($keys, 0.0) + [
                    'food_count'       => 0,
                    'micro_food_count' => 0, // foods with any vitamin/mineral data
                    'micros'         => array_fill_keys($microKeys, 0.0),
                    'micro_reported' => array_fill_keys($microKeys, 0), // foods reporting each nutrient
                ];
            }
            foreach ($keys as $k) {
                $days[$d][$k] += (float) ($e[$k] ?? 0);
            }

            $days[$d]['food_count']++;
            $micros = json_decode((string) ($e['micronutrients'] ?? ''), true) ?: [];
            if ($micros) {
                $days[$d]['micro_food_count']++;
            }
            foreach ($micros as $k => $v) {
                if (isset($days[$d]['micros'][$k])) {
                    $days[$d]['micros'][$k] += (float) $v;
                    $days[$d]['micro_reported'][$k]++;
                }
            }
        }

        foreach ($days as &$day) {
            foreach ($keys as $k) {
                $day[$k] = round($day[$k], 1);
            }
            foreach ($day['micros'] as $k => $v) {
                $day['micros'][$k] = round($v, 2);
            }
        }

        return $days;
    }

    // ── Helpers ───────────────────────────────────────────────────────────── //

    public static function weekStart(?\DateTimeImmutable $d = null): string
    {
        $d   = $d ?? new \DateTimeImmutable();
        $dow = (int) $d->format('N');
        if ($dow !== 1) {
            $d = $d->modify('-' . ($dow - 1) . ' days');
        }
        return $d->format('Y-m-d');
    }

    private function defaultGoals(): array
    {
        return [
            'calories'  => 2000,
            'protein_g' => 50,
            'carbs_g'   => 250,
            'fat_g'     => 65,
            'fiber_g'   => 25,
            'sodium_mg' => 2300,
        ];
    }
}
