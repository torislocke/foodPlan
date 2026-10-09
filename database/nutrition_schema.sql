-- Nutrition Planner schema
-- Run once against the `food` database (local) or `u475464744_food` (production)
-- If tables already exist from an earlier run, execute only the new CREATE TABLE at the bottom.

CREATE TABLE IF NOT EXISTS nutritional_goals (
    id            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    session_token VARCHAR(64)     NOT NULL,
    user_id       INT UNSIGNED    NULL,
    calories      INT UNSIGNED    NOT NULL DEFAULT 2000,
    protein_g     DECIMAL(6,1)   NOT NULL DEFAULT 50.0,
    carbs_g       DECIMAL(6,1)   NOT NULL DEFAULT 250.0,
    fat_g         DECIMAL(6,1)   NOT NULL DEFAULT 65.0,
    fiber_g       DECIMAL(6,1)   NOT NULL DEFAULT 25.0,
    sodium_mg     DECIMAL(8,1)   NOT NULL DEFAULT 2300.0,
    height_cm     DECIMAL(5,1)   NULL,
    weight_kg     DECIMAL(5,1)   NULL,
    age           TINYINT UNSIGNED NULL,
    gender        ENUM('male','female') NULL,
    activity_level VARCHAR(20)   NULL,
    weight_goal   VARCHAR(20)    NULL COMMENT 'lose | mild_lose | maintain | mild_gain | gain',
    created_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_token (session_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS meal_plans (
    id            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    session_token VARCHAR(64)     NOT NULL,
    user_id       INT UNSIGNED    NULL,
    week_start    DATE            NOT NULL  COMMENT 'Always a Monday (YYYY-MM-DD)',
    created_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_plan (session_token, week_start)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS meal_entries (
    id               INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    meal_plan_id     INT UNSIGNED    NOT NULL,
    day_of_week      TINYINT UNSIGNED NOT NULL  COMMENT '0=Monday … 6=Sunday',
    meal_type        ENUM('breakfast','lunch','dinner','snack') NOT NULL,
    fdc_id           INT UNSIGNED    NOT NULL   COMMENT 'USDA FoodData Central ID',
    food_name        VARCHAR(255)    NOT NULL,
    brand_name       VARCHAR(255)    NULL,
    serving_size     DECIMAL(8,2)   NOT NULL DEFAULT 100.00,
    serving_unit     VARCHAR(50)    NOT NULL DEFAULT 'g',
    servings         DECIMAL(5,2)   NOT NULL DEFAULT 1.00,
    -- Stored values are totals (per-serving nutrients × servings)
    calories         DECIMAL(8,1)   NULL,
    protein_g        DECIMAL(7,2)   NULL,
    carbs_g          DECIMAL(7,2)   NULL,
    fat_g            DECIMAL(7,2)   NULL,
    fiber_g          DECIMAL(7,2)   NULL,
    sodium_mg        DECIMAL(8,2)   NULL,
    sugar_g          DECIMAL(7,2)   NULL,
    cholesterol_mg   DECIMAL(7,2)   NULL,
    saturated_fat_g  DECIMAL(7,2)   NULL,
    micronutrients   JSON           NULL COMMENT 'Vitamin & mineral totals; NULL = no data',
    created_at       TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_plan_day (meal_plan_id, day_of_week),
    CONSTRAINT fk_entry_plan FOREIGN KEY (meal_plan_id) REFERENCES meal_plans (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── User-defined foods (not in USDA database) ─────────────────────────────── //
CREATE TABLE IF NOT EXISTS custom_foods (
    id               INT UNSIGNED   NOT NULL AUTO_INCREMENT,
    session_token    VARCHAR(64)    NOT NULL,
    food_name        VARCHAR(255)   NOT NULL,
    brand_name       VARCHAR(255)   NULL,
    serving_size     DECIMAL(8,2)  NOT NULL DEFAULT 100.00,
    serving_unit     VARCHAR(50)   NOT NULL DEFAULT 'g',
    -- Nutrients are stored per serving as entered by the user
    calories         DECIMAL(8,1)  NOT NULL,
    protein_g        DECIMAL(7,2)  NULL,
    carbs_g          DECIMAL(7,2)  NULL,
    fat_g            DECIMAL(7,2)  NULL,
    fiber_g          DECIMAL(7,2)  NULL,
    sodium_mg        DECIMAL(8,2)  NULL,
    sugar_g          DECIMAL(7,2)  NULL,
    cholesterol_mg   DECIMAL(7,2)  NULL,
    saturated_fat_g  DECIMAL(7,2)  NULL,
    micronutrients   JSON          NULL COMMENT 'Vitamin & mineral amounts per serving; NULL = none entered',
    created_at       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_cf_token (session_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
