-- Dedicated challenges. Registration/password-reset OTPs remain unchanged.
-- Each row reserves one SMS attempt. Count created_at for rate limits,
-- including failed, cancelled, expired and replaced challenges. Never delete
-- these rows to cancel/resend. Set invalidated_at and insert a new challenge.
CREATE TABLE IF NOT EXISTS `recycler_phone_change_challenges` (
    `challenge_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `recycler_user_id` BIGINT UNSIGNED NOT NULL,
    `original_mobile_number` VARCHAR(20) NOT NULL,
    `proposed_mobile_number` VARCHAR(20) NOT NULL,
    `otp_hash` VARCHAR(255) NOT NULL,
    `session_context_hash` CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    `password_state_fingerprint` CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    `attempt_count` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `expires_at` DATETIME NOT NULL,
    `delivery_status` ENUM('PENDING', 'SENT', 'FAILED') NOT NULL DEFAULT 'PENDING',
    -- SENT means the SMS gateway accepted delivery, not handset receipt.
    `sent_at` DATETIME NULL,
    `verified_at` DATETIME NULL,
    `invalidated_at` DATETIME NULL,

    -- Expiry alone does not release this slot. Invalidate an expired challenge
    -- in the same transaction before inserting its replacement.
    `active_recycler_user_id` BIGINT UNSIGNED
        GENERATED ALWAYS AS (
            CASE
                WHEN `verified_at` IS NULL AND `invalidated_at` IS NULL
                THEN `recycler_user_id`
                ELSE NULL
            END
        ) PERSISTENT,

    PRIMARY KEY (`challenge_id`),
    UNIQUE KEY `uq_phone_change_one_active` (`active_recycler_user_id`),
    KEY `idx_phone_change_owner_created` (`recycler_user_id`, `created_at`),
    KEY `idx_phone_change_target_created` (`proposed_mobile_number`, `created_at`),

    CONSTRAINT `fk_phone_change_recycler`
        FOREIGN KEY (`recycler_user_id`)
        REFERENCES `authorized_recyclers` (`user_id`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `chk_phone_change_numbers`
        CHECK (
            `original_mobile_number` REGEXP '^947[0-9]{8}$'
            AND `proposed_mobile_number` REGEXP '^947[0-9]{8}$'
            AND `original_mobile_number` <> `proposed_mobile_number`
        ),
    CONSTRAINT `chk_phone_change_bindings`
        CHECK (
            `session_context_hash` REGEXP '^[0-9a-f]{64}$'
            AND `password_state_fingerprint` REGEXP '^[0-9a-f]{64}$'
        ),
    CONSTRAINT `chk_phone_change_attempts`
        CHECK (`attempt_count` <= 5),
    CONSTRAINT `chk_phone_change_expiry`
        CHECK (`expires_at` > `created_at`),
    CONSTRAINT `chk_phone_change_delivery`
        CHECK (
            (`delivery_status` = 'PENDING' AND `sent_at` IS NULL)
            OR (`delivery_status` = 'SENT' AND `sent_at` IS NOT NULL
                AND `sent_at` >= `created_at`)
            OR (`delivery_status` = 'FAILED' AND `sent_at` IS NULL
                AND `invalidated_at` IS NOT NULL)
        ),
    CONSTRAINT `chk_phone_change_terminal_state`
        CHECK (
            (`verified_at` IS NULL OR (
                `invalidated_at` IS NULL AND `delivery_status` = 'SENT'
                AND `verified_at` >= `sent_at` AND `verified_at` <= `expires_at`
                AND `attempt_count` < 5
            ))
            AND (`invalidated_at` IS NULL OR `invalidated_at` >= `created_at`)
        )
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
