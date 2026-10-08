-- Auth schema — run after nutrition_schema.sql (new installs).
-- Existing databases with the old users table: run auth_login.sql instead.
-- If custom_foods already exists without user_id, run the ALTER TABLE at the bottom.

CREATE TABLE IF NOT EXISTS users (
    id            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    first_name    VARCHAR(100)  NOT NULL DEFAULT '',
    last_name     VARCHAR(100)  NOT NULL DEFAULT '',
    email         VARCHAR(255)  NOT NULL,
    password      VARCHAR(255)  NOT NULL,
    token         CHAR(64)      NULL COMMENT 'sha256 of the emailed verify/reset token',
    token_expires DATETIME      NULL,
    status        ENUM('Active','Inactive') NOT NULL DEFAULT 'Inactive',
    created_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- "Keep me signed in" tokens (sha256 hashes only)
CREATE TABLE IF NOT EXISTS remember_tokens (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id    INT UNSIGNED NOT NULL,
    token_hash CHAR(64)     NOT NULL,
    expires_at DATETIME     NOT NULL,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_token_hash (token_hash),
    KEY idx_user_id (user_id),
    CONSTRAINT fk_rt_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add user_id to custom_foods if the table was created before auth was added.
-- Safe to run even if the column already exists — just skip it if MySQL errors.
ALTER TABLE custom_foods
    ADD COLUMN user_id INT UNSIGNED NULL AFTER session_token;
