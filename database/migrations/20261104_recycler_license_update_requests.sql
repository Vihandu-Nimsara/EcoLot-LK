
CREATE TABLE IF NOT EXISTS `recycler_license_update_requests` (
    `request_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `recycler_user_id` BIGINT UNSIGNED NOT NULL,
    `current_license_id` BIGINT UNSIGNED NULL,

    `requested_license_number` VARCHAR(100) NOT NULL,
    `requested_expiry_date` DATE NOT NULL,
    `document_path` VARCHAR(500) NOT NULL,

    `request_status` ENUM(
        'PENDING',
        'APPROVED',
        'REJECTED'
    ) NOT NULL DEFAULT 'PENDING',

    `submitted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    `reviewed_by_admin_user_id` BIGINT UNSIGNED NULL,
    `reviewed_at` DATETIME NULL,
    `review_note` VARCHAR(500) NULL,

    -- Enforce only one pending request per recycler.
    `pending_recycler_user_id` BIGINT UNSIGNED
        GENERATED ALWAYS AS (
            CASE
                WHEN `request_status` = 'PENDING'
                THEN `recycler_user_id`
                ELSE NULL
            END
        ) PERSISTENT,

    PRIMARY KEY (`request_id`),

    UNIQUE KEY `uq_recycler_license_one_pending` (
        `pending_recycler_user_id`
    ),

    KEY `idx_license_update_owner` (
        `recycler_user_id`,
        `request_status`,
        `submitted_at`
    ),

    KEY `idx_license_update_current` (`current_license_id`),

    KEY `idx_license_update_reviewer` (
        `reviewed_by_admin_user_id`
    ),

    CONSTRAINT `fk_license_update_recycler`
        FOREIGN KEY (`recycler_user_id`)
        REFERENCES `authorized_recyclers` (`user_id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT `fk_license_update_current`
        FOREIGN KEY (`current_license_id`)
        REFERENCES `recycler_licenses` (`license_id`)
        ON UPDATE CASCADE
        ON DELETE SET NULL,

    CONSTRAINT `fk_license_update_admin`
        FOREIGN KEY (`reviewed_by_admin_user_id`)
        REFERENCES `administrators` (`user_id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT `chk_license_update_review`
        CHECK (
            (
                `request_status` = 'PENDING'
                AND `reviewed_by_admin_user_id` IS NULL
                AND `reviewed_at` IS NULL
                AND `review_note` IS NULL
            )
            OR
            (
                `request_status` = 'APPROVED'
                AND `reviewed_by_admin_user_id` IS NOT NULL
                AND `reviewed_at` IS NOT NULL
            )
            OR
            (
                `request_status` = 'REJECTED'
                AND `reviewed_by_admin_user_id` IS NOT NULL
                AND `reviewed_at` IS NOT NULL
                AND `review_note` IS NOT NULL
            )
        )
) ENGINE=InnoDB;
