-- Auth schema — run after nutrition_schema.sql
-- If custom_foods already exists without user_id, run the ALTER TABLE at the bottom.

CREATE TABLE IF NOT EXISTS users (
    id            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    name          VARCHAR(100)  NOT NULL,
    email         VARCHAR(255)  NOT NULL,
    password_hash VARCHAR(255)  NOT NULL,
    created_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add user_id to custom_foods if the table was created before auth was added.
-- Safe to run even if the column already exists — just skip it if MySQL errors.
ALTER TABLE custom_foods
    ADD COLUMN user_id INT UNSIGNED NULL AFTER session_token;
