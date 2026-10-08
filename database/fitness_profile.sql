-- Fitness profile — run after nutrition_schema.sql
-- Stores the body stats used to calculate BMI and calorie targets alongside the goals.

ALTER TABLE nutritional_goals
    ADD COLUMN height_cm      DECIMAL(5,1)  NULL AFTER sodium_mg,
    ADD COLUMN weight_kg      DECIMAL(5,1)  NULL AFTER height_cm,
    ADD COLUMN age            TINYINT UNSIGNED NULL AFTER weight_kg,
    ADD COLUMN gender         ENUM('male','female') NULL AFTER age,
    ADD COLUMN activity_level VARCHAR(20)   NULL AFTER gender,
    ADD COLUMN weight_goal    VARCHAR(20)   NULL COMMENT 'lose | mild_lose | maintain | mild_gain | gain' AFTER activity_level;
