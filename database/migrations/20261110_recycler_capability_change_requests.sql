-- =========================================================
-- Recycler Capability Change Requests
-- =========================================================

-- Existing approved capabilities remain unchanged
-- until an administrator approves the request.

CREATE TABLE IF NOT EXISTS `recycler_capability_change_requests` (

    -- Request identification
    `request_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `recycler_user_id` BIGINT UNSIGNED NOT NULL,
    `category_id` BIGINT UNSIGNED NOT NULL,

    -- Type of capability change
    `request_type` ENUM(
        'ADD',
        'UPDATE',
        'DEACTIVATE'
    ) NOT NULL,

    -- Previous capability information
    `previous_capability_status` ENUM(
        'PENDING',
        'APPROVED',
        'REJECTED',
        'SUSPENDED',
        'INACTIVE'
    ) NULL,

    `previous_can_handle_high_risk` BOOLEAN NULL,

    -- Requested capability information
    `requested_can_handle_high_risk` BOOLEAN NULL,

    -- Request details
    `request_reason` VARCHAR(500) NOT NULL,

    `request_status` ENUM(
        'PENDING',
        'APPROVED',
        'REJECTED'
    ) NOT NULL DEFAULT 'PENDING',

    `submitted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    -- Administrator review details
    `reviewed_by_admin_user_id` BIGINT UNSIGNED NULL,
    `reviewed_at` DATETIME NULL,
    `review_note` VARCHAR(500) NULL,

    -- =====================================================
    -- Generated Columns
    -- Only PENDING requests occupy the unique slot
    -- =====================================================

    `pending_recycler_user_id` BIGINT UNSIGNED
        GENERATED ALWAYS AS (
            CASE
                WHEN `request_status` = 'PENDING'
                THEN `recycler_user_id`
                ELSE NULL
            END
        ) PERSISTENT,

    `pending_category_id` BIGINT UNSIGNED
        GENERATED ALWAYS AS (
            CASE
                WHEN `request_status` = 'PENDING'
                THEN `category_id`
                ELSE NULL
            END
        ) PERSISTENT,

    -- =====================================================
    -- Primary Key
    -- =====================================================

    PRIMARY KEY (`request_id`),

    -- Prevent multiple pending requests for the same
    -- recycler and waste category
    UNIQUE KEY `uq_capability_one_pending` (
        `pending_recycler_user_id`,
        `pending_category_id`
    ),

    -- =====================================================
    -- Indexes
    -- =====================================================

    KEY `idx_capability_request_owner` (
        `recycler_user_id`,
        `request_status`,
        `submitted_at`
    ),

    KEY `idx_capability_request_category` (
        `category_id`
    ),

    KEY `idx_capability_request_reviewer` (
        `reviewed_by_admin_user_id`
    ),

    -- =====================================================
    -- Foreign Key Constraints
    -- =====================================================

    CONSTRAINT `fk_capability_request_recycler`
        FOREIGN KEY (`recycler_user_id`)
        REFERENCES `authorized_recyclers` (`user_id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT `fk_capability_request_category`
        FOREIGN KEY (`category_id`)
        REFERENCES `waste_categories` (`category_id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT `fk_capability_request_admin`
        FOREIGN KEY (`reviewed_by_admin_user_id`)
        REFERENCES `administrators` (`user_id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    -- =====================================================
    -- Request Reason Validation
    -- =====================================================

    CONSTRAINT `chk_capability_request_reason`
        CHECK (
            CHAR_LENGTH(TRIM(`request_reason`)) > 0
        ),

    CONSTRAINT `chk_capability_request_snapshot`
        CHECK (
            (`request_type` = 'ADD'
                AND `previous_capability_status` IS NULL
                AND `previous_can_handle_high_risk` IS NULL)
            OR
            (`request_type` = 'UPDATE'
                AND `previous_capability_status` IS NOT NULL
                AND `previous_capability_status` IN ('APPROVED', 'INACTIVE')
                AND `previous_can_handle_high_risk` IS NOT NULL
                AND (`previous_capability_status` = 'INACTIVE'
                    OR `previous_can_handle_high_risk` <> `requested_can_handle_high_risk`))
            OR
            (`request_type` = 'DEACTIVATE'
                AND `previous_capability_status` IS NOT NULL
                AND `previous_capability_status` = 'APPROVED'
                AND `previous_can_handle_high_risk` IS NOT NULL)
        ),

    -- =====================================================
    -- High-Risk Permission Validation
    -- =====================================================

    CONSTRAINT `chk_capability_request_risk`
        CHECK (
            (
                `previous_can_handle_high_risk` IS NULL
                OR `previous_can_handle_high_risk` IN (0, 1)
            )
            AND
            (
                (
                    `request_type` = 'DEACTIVATE'
                    AND `requested_can_handle_high_risk` IS NULL
                )
                OR
                (
                    `request_type` IN ('ADD', 'UPDATE')
                    AND `requested_can_handle_high_risk` IS NOT NULL
                    AND `requested_can_handle_high_risk` IN (0, 1)
                )
            )
        ),

    -- =====================================================
    -- Administrator Review Validation
    -- =====================================================

    CONSTRAINT `chk_capability_request_review`
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
                AND `reviewed_at` >= `submitted_at`
            )
            OR
            (
                `request_status` = 'REJECTED'
                AND `reviewed_by_admin_user_id` IS NOT NULL
                AND `reviewed_at` IS NOT NULL
                AND `reviewed_at` >= `submitted_at`
                AND `review_note` IS NOT NULL
                AND CHAR_LENGTH(TRIM(`review_note`)) > 0
            )
        )

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
