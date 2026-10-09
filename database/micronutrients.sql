-- Vitamins & minerals — run after nutrition_schema.sql
-- Per-entry totals (per-serving amounts × servings), keyed as in FoodApiService::MICRONUTRIENTS.
-- NULL means the food had no vitamin/mineral data (custom foods, or added before this column).

ALTER TABLE meal_entries
    ADD COLUMN micronutrients JSON NULL AFTER saturated_fat_g;

-- Same column on custom_foods: vitamins/minerals entered per serving by the
-- user, keyed as above. NULL means none were entered for that food.
ALTER TABLE custom_foods
    ADD COLUMN micronutrients JSON NULL AFTER saturated_fat_g;
