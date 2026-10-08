-- Login / registration upgrade — run after auth_schema.sql on existing databases.
-- Matches the erik/cypress users layout: first/last name, status + emailed tokens
-- for account verification and password reset, and persistent "remember me" logins.
-- Existing accounts are split into first/last name and kept Active.

ALTER TABLE users
    ADD COLUMN first_name    VARCHAR(100) NOT NULL DEFAULT '' AFTER id,
    ADD COLUMN last_name     VARCHAR(100) NOT NULL DEFAULT '' AFTER first_name,
    CHANGE COLUMN password_hash password VARCHAR(255) NOT NULL,
    ADD COLUMN token         CHAR(64)     NULL COMMENT 'sha256 of the emailed verify/reset token' AFTER password,
    ADD COLUMN token_expires DATETIME     NULL AFTER token,
    ADD COLUMN status        ENUM('Active','Inactive') NOT NULL DEFAULT 'Inactive' AFTER token_expires;

UPDATE users
SET first_name = SUBSTRING_INDEX(TRIM(name), ' ', 1),
    last_name  = IF(LOCATE(' ', TRIM(name)) > 0, TRIM(SUBSTRING(TRIM(name), LOCATE(' ', TRIM(name)) + 1)), ''),
    status     = 'Active';

ALTER TABLE users DROP COLUMN name;

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
