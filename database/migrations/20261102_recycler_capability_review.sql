-- Add Administrator review metadata to recycler capabilities.
-- Apply once to an existing EcoLot-LK database.

ALTER TABLE `recycler_capabilities`

    ADD COLUMN `reviewed_by_admin_user_id`
        BIGINT UNSIGNED NULL
        AFTER `capability_status`,

    ADD COLUMN `reviewed_at`
        DATETIME NULL
        AFTER `reviewed_by_admin_user_id`,

    ADD COLUMN `review_note`
        VARCHAR(500) NULL
        AFTER `reviewed_at`,

    ADD INDEX `idx_recycler_capabilities_reviewer`
        (`reviewed_by_admin_user_id`),

    ADD CONSTRAINT `fk_recycler_capabilities_reviewer`
        FOREIGN KEY (`reviewed_by_admin_user_id`)
        REFERENCES `administrators` (`user_id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT;