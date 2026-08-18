-- EcoLot LK database schema
-- Target: MariaDB 10.4+ / MySQL-compatible InnoDB

CREATE DATABASE IF NOT EXISTS `ecolot_lk`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `ecolot_lk`;

SET NAMES utf8mb4;
SET time_zone = '+05:30';

-- =========================================================
-- Identity and access
-- =========================================================

CREATE TABLE IF NOT EXISTS `users` (
    `user_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `full_name` VARCHAR(120) NOT NULL,
    `mobile_number` VARCHAR(20) NOT NULL,
    `email` VARCHAR(255) NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('ADMIN', 'PUBLIC_USER', 'MUNICIPAL_OFFICER', 'COLLECTOR', 'RECYCLER') NOT NULL,
    `account_status` ENUM('PENDING', 'ACTIVE', 'SUSPENDED', 'DISABLED') NOT NULL DEFAULT 'PENDING',
    `mobile_verified_at` DATETIME NULL,
    `must_change_password` BOOLEAN NOT NULL DEFAULT FALSE,
    `created_by_admin_user_id` BIGINT UNSIGNED NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`),
    UNIQUE KEY `uq_users_mobile_number` (`mobile_number`),
    UNIQUE KEY `uq_users_email` (`email`),
    KEY `idx_users_role_status` (`role`, `account_status`),
    CONSTRAINT `fk_users_created_by_user`
        FOREIGN KEY (`created_by_admin_user_id`) REFERENCES `users` (`user_id`)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT `chk_users_mobile_number`
        CHECK (`mobile_number` REGEXP '^[0-9+][0-9 -]{6,18}$')
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `administrators` (
    `user_id` BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (`user_id`),
    CONSTRAINT `fk_administrators_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `municipal_officers` (
    `user_id` BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (`user_id`),
    CONSTRAINT `fk_municipal_officers_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `collectors` (
    `user_id` BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (`user_id`),
    CONSTRAINT `fk_collectors_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `mobile_verification_otps` (
    `otp_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `purpose` ENUM('REGISTRATION', 'PASSWORD_RESET') NOT NULL DEFAULT 'REGISTRATION',
    `otp_hash` VARCHAR(255) NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `attempt_count` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `sent_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `verified_at` DATETIME NULL,
    `invalidated_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`otp_id`),
    KEY `idx_otp_user_purpose_state` (
        `user_id`,
        `purpose`,
        `verified_at`,
        `invalidated_at`
    ),
    KEY `idx_otp_user_purpose_sent` (`user_id`, `purpose`, `sent_at`),
    CONSTRAINT `fk_mobile_otps_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- Zones and public profiles
-- =========================================================

CREATE TABLE IF NOT EXISTS `postal_code_areas` (
    `postal_area_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `postal_code` VARCHAR(10) NOT NULL,
    `area_name` VARCHAR(120) NOT NULL,
    `area_status` ENUM('ACTIVE', 'INACTIVE') NOT NULL DEFAULT 'ACTIVE',
    PRIMARY KEY (`postal_area_id`),
    UNIQUE KEY `uq_postal_code_areas_postal_code` (`postal_code`),
    UNIQUE KEY `uq_postal_code_areas_name` (`area_name`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `public_profiles` (
    `user_id` BIGINT UNSIGNED NOT NULL,
    `postal_area_id` BIGINT UNSIGNED NOT NULL,
    `address` VARCHAR(500) NOT NULL,
    PRIMARY KEY (`user_id`),
    KEY `idx_public_profiles_postal_area` (`postal_area_id`),
    CONSTRAINT `fk_public_profiles_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_public_profiles_postal_area`
        FOREIGN KEY (`postal_area_id`) REFERENCES `postal_code_areas` (`postal_area_id`)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `authorized_recyclers` (
    `user_id` BIGINT UNSIGNED NOT NULL,
    `company_name` VARCHAR(180) NOT NULL,
    `business_address` VARCHAR(500) NOT NULL,
    `district` VARCHAR(100) NOT NULL,
    `verification_status` ENUM('PENDING', 'VERIFIED', 'REJECTED') NOT NULL DEFAULT 'PENDING',
    `verified_by_admin_user_id` BIGINT UNSIGNED NULL,
    `verified_at` DATETIME NULL,
    `submitted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`),
    UNIQUE KEY `uq_authorized_recyclers_company_name` (`company_name`),
    KEY `idx_recyclers_verification_status` (`verification_status`),
    CONSTRAINT `fk_authorized_recyclers_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_authorized_recyclers_verified_by_admin`
        FOREIGN KEY (`verified_by_admin_user_id`) REFERENCES `administrators` (`user_id`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `chk_recycler_verification_audit`
        CHECK (
            (`verification_status` = 'PENDING' AND `verified_by_admin_user_id` IS NULL AND `verified_at` IS NULL)
            OR
            (`verification_status` IN ('VERIFIED', 'REJECTED') AND `verified_by_admin_user_id` IS NOT NULL AND `verified_at` IS NOT NULL)
        )
) ENGINE=InnoDB;

-- =========================================================
-- Catalogue and risk rules
-- =========================================================

CREATE TABLE IF NOT EXISTS `waste_categories` (
    `category_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `category_name` VARCHAR(120) NOT NULL,
    `description` VARCHAR(500) NULL,
    `category_status` ENUM('ACTIVE', 'INACTIVE') NOT NULL DEFAULT 'ACTIVE',
    PRIMARY KEY (`category_id`),
    UNIQUE KEY `uq_waste_categories_name` (`category_name`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `e_waste_items` (
    `waste_item_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `category_id` BIGINT UNSIGNED NOT NULL,
    `item_name` VARCHAR(160) NOT NULL,
    `collection_status` ENUM('ACCEPTED', 'REVIEW_REQUIRED', 'DO_NOT_COLLECT') NOT NULL DEFAULT 'ACCEPTED',
    `default_risk_level` ENUM('LOW', 'MEDIUM', 'HIGH') NOT NULL DEFAULT 'LOW',
    `item_status` ENUM('ACTIVE', 'INACTIVE') NOT NULL DEFAULT 'ACTIVE',
    PRIMARY KEY (`waste_item_id`),
    UNIQUE KEY `uq_e_waste_items_category_name` (`category_id`, `item_name`),
    KEY `idx_e_waste_items_collection_policy` (`collection_status`, `default_risk_level`),
    CONSTRAINT `fk_e_waste_items_category`
        FOREIGN KEY (`category_id`) REFERENCES `waste_categories` (`category_id`)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `risk_rules` (
    `risk_rule_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `waste_item_id` BIGINT UNSIGNED NOT NULL,
    `condition_type` VARCHAR(80) NOT NULL,
    `risk_level` ENUM('LOW', 'MEDIUM', 'HIGH') NOT NULL,
    `action_note` VARCHAR(500) NOT NULL,
    `rule_status` ENUM('ACTIVE', 'INACTIVE') NOT NULL DEFAULT 'ACTIVE',
    PRIMARY KEY (`risk_rule_id`),
    UNIQUE KEY `uq_risk_rules_item_condition` (`waste_item_id`, `condition_type`),
    CONSTRAINT `fk_risk_rules_waste_item`
        FOREIGN KEY (`waste_item_id`) REFERENCES `e_waste_items` (`waste_item_id`)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- Campaigns, schedules, assignments and vehicles
-- =========================================================

CREATE TABLE IF NOT EXISTS `monthly_campaigns` (
    `campaign_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `created_by_officer_user_id` BIGINT UNSIGNED NOT NULL,
    `campaign_name` VARCHAR(180) NOT NULL,
    `campaign_month` DATE NOT NULL COMMENT 'Store the first day of the campaign month',
    `campaign_status` ENUM('OPEN', 'CLOSED') NOT NULL DEFAULT 'OPEN',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`campaign_id`),
    UNIQUE KEY `uq_monthly_campaigns_month` (`campaign_month`),
    CONSTRAINT `fk_monthly_campaigns_officer`
        FOREIGN KEY (`created_by_officer_user_id`) REFERENCES `municipal_officers` (`user_id`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `chk_monthly_campaigns_first_day`
        CHECK (DAYOFMONTH(`campaign_month`) = 1)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `area_collection_schedules` (
    `schedule_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `campaign_id` BIGINT UNSIGNED NOT NULL,
    `postal_area_id` BIGINT UNSIGNED NOT NULL,
    `created_by_officer_user_id` BIGINT UNSIGNED NOT NULL,
    `collection_date` DATE NOT NULL,
    `request_cutoff_at` DATETIME NOT NULL,
    `request_capacity` INT UNSIGNED NOT NULL,
    `schedule_status` ENUM(
        'PLANNED', 'OPEN', 'CLOSED', 'ASSIGNED', 'IN_PROGRESS',
        'COLLECTION_SUBMITTED', 'COMPLETED', 'CANCELLED'
    ) NOT NULL DEFAULT 'PLANNED',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`schedule_id`),
    UNIQUE KEY `uq_schedules_campaign_area` (`campaign_id`, `postal_area_id`),
    KEY `idx_schedules_date_status` (`collection_date`, `schedule_status`),
    CONSTRAINT `fk_schedules_campaign`
        FOREIGN KEY (`campaign_id`) REFERENCES `monthly_campaigns` (`campaign_id`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_schedules_postal_area`
        FOREIGN KEY (`postal_area_id`) REFERENCES `postal_code_areas` (`postal_area_id`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_schedules_created_by_officer`
        FOREIGN KEY (`created_by_officer_user_id`) REFERENCES `municipal_officers` (`user_id`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `chk_schedules_capacity`
        CHECK (`request_capacity` > 0),
    CONSTRAINT `chk_schedules_cutoff`
        CHECK (`request_cutoff_at` < `collection_date`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `vehicles` (
    `vehicle_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `vehicle_number` VARCHAR(30) NOT NULL,
    `vehicle_type` VARCHAR(100) NOT NULL,
    `availability_status` ENUM('AVAILABLE', 'MAINTENANCE', 'RETIRED') NOT NULL DEFAULT 'AVAILABLE',
    PRIMARY KEY (`vehicle_id`),
    UNIQUE KEY `uq_vehicles_number` (`vehicle_number`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `schedule_assignments` (
    `assignment_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `schedule_id` BIGINT UNSIGNED NOT NULL,
    `collector_user_id` BIGINT UNSIGNED NOT NULL,
    `vehicle_id` BIGINT UNSIGNED NULL,
    `assigned_by_officer_user_id` BIGINT UNSIGNED NOT NULL,
    `assigned_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `unassigned_at` DATETIME NULL,
    PRIMARY KEY (`assignment_id`),
    KEY `idx_schedule_assignments_schedule_active` (`schedule_id`, `unassigned_at`),
    KEY `idx_schedule_assignments_collector_active` (`collector_user_id`, `unassigned_at`),
    KEY `idx_schedule_assignments_vehicle_active` (`vehicle_id`, `unassigned_at`),
    CONSTRAINT `fk_schedule_assignments_schedule`
        FOREIGN KEY (`schedule_id`) REFERENCES `area_collection_schedules` (`schedule_id`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_schedule_assignments_collector`
        FOREIGN KEY (`collector_user_id`) REFERENCES `collectors` (`user_id`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_schedule_assignments_vehicle`
        FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`vehicle_id`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_schedule_assignments_officer`
        FOREIGN KEY (`assigned_by_officer_user_id`) REFERENCES `municipal_officers` (`user_id`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `chk_schedule_assignments_period`
        CHECK (`unassigned_at` IS NULL OR `unassigned_at` > `assigned_at`)
) ENGINE=InnoDB;

-- =========================================================
-- Requests and feedback
-- =========================================================

CREATE TABLE IF NOT EXISTS `e_waste_requests` (
    `request_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `public_user_id` BIGINT UNSIGNED NOT NULL,
    `schedule_id` BIGINT UNSIGNED NOT NULL,
    `pickup_address` VARCHAR(500) NOT NULL,
    `request_status` ENUM('SUBMITTED', 'PENDING_REVIEW', 'APPROVED', 'REJECTED', 'CANCELLED', 'COMPLETED') NOT NULL DEFAULT 'SUBMITTED',
    `risk_review_status` ENUM('NOT_REQUIRED', 'PENDING', 'APPROVED', 'REJECTED') NOT NULL DEFAULT 'NOT_REQUIRED',
    `reviewed_by_officer_user_id` BIGINT UNSIGNED NULL,
    `review_note` VARCHAR(500) NULL,
    `submitted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `reviewed_at` DATETIME NULL,
    PRIMARY KEY (`request_id`),
    KEY `idx_requests_user_status` (`public_user_id`, `request_status`),
    KEY `idx_requests_schedule_status` (`schedule_id`, `request_status`),
    KEY `idx_requests_risk_review` (`risk_review_status`),
    CONSTRAINT `fk_requests_public_profile`
        FOREIGN KEY (`public_user_id`) REFERENCES `public_profiles` (`user_id`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_requests_schedule`
        FOREIGN KEY (`schedule_id`) REFERENCES `area_collection_schedules` (`schedule_id`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_requests_reviewed_by_officer`
        FOREIGN KEY (`reviewed_by_officer_user_id`) REFERENCES `municipal_officers` (`user_id`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `chk_requests_review_audit`
        CHECK (
            (`risk_review_status` IN ('NOT_REQUIRED', 'PENDING') AND `reviewed_by_officer_user_id` IS NULL AND `reviewed_at` IS NULL)
            OR
            (`risk_review_status` IN ('APPROVED', 'REJECTED') AND `reviewed_by_officer_user_id` IS NOT NULL AND `reviewed_at` IS NOT NULL)
        )
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `request_items` (
    `request_item_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `request_id` BIGINT UNSIGNED NOT NULL,
    `waste_item_id` BIGINT UNSIGNED NOT NULL,
    `quantity` INT UNSIGNED NOT NULL,
    `estimated_weight_kg` DECIMAL(10,3) UNSIGNED NOT NULL,
    `item_condition` ENUM('WORKING', 'DAMAGED', 'UNKNOWN') NOT NULL,
    `condition_note` VARCHAR(500) NULL,
    `applied_risk_level` ENUM('LOW', 'MEDIUM', 'HIGH') NOT NULL,
    PRIMARY KEY (`request_item_id`),
    KEY `idx_request_items_request` (`request_id`),
    KEY `idx_request_items_waste_item` (`waste_item_id`),
    KEY `idx_request_items_risk` (`applied_risk_level`),
    CONSTRAINT `fk_request_items_request`
        FOREIGN KEY (`request_id`) REFERENCES `e_waste_requests` (`request_id`)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_request_items_waste_item`
        FOREIGN KEY (`waste_item_id`) REFERENCES `e_waste_items` (`waste_item_id`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `chk_request_items_quantity`
        CHECK (`quantity` > 0),
    CONSTRAINT `chk_request_items_weight`
        CHECK (`estimated_weight_kg` > 0)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `complaint_feedback` (
    `feedback_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `public_user_id` BIGINT UNSIGNED NOT NULL,
    `request_id` BIGINT UNSIGNED NULL,
    `feedback_type` ENUM('COMPLAINT', 'SUGGESTION', 'COMPLIMENT') NOT NULL,
    `message` TEXT NOT NULL,
    `feedback_status` ENUM('OPEN', 'IN_REVIEW', 'RESOLVED', 'CLOSED') NOT NULL DEFAULT 'OPEN',
    `officer_response` TEXT NULL,
    `reviewed_by_officer_user_id` BIGINT UNSIGNED NULL,
    `submitted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `reviewed_at` DATETIME NULL,
    PRIMARY KEY (`feedback_id`),
    KEY `idx_feedback_public_user` (`public_user_id`),
    KEY `idx_feedback_request` (`request_id`),
    KEY `idx_feedback_status` (`feedback_status`),
    CONSTRAINT `fk_feedback_public_profile`
        FOREIGN KEY (`public_user_id`) REFERENCES `public_profiles` (`user_id`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_feedback_request`
        FOREIGN KEY (`request_id`) REFERENCES `e_waste_requests` (`request_id`)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT `fk_feedback_reviewed_by_officer`
        FOREIGN KEY (`reviewed_by_officer_user_id`) REFERENCES `municipal_officers` (`user_id`)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- =========================================================
-- Recycler qualifications
-- =========================================================

CREATE TABLE IF NOT EXISTS `recycler_licenses` (
    `license_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `recycler_user_id` BIGINT UNSIGNED NOT NULL,
    `license_number` VARCHAR(100) NOT NULL,
    `expiry_date` DATE NOT NULL,
    `license_status` ENUM('PENDING', 'VALID', 'EXPIRED', 'REVOKED') NOT NULL DEFAULT 'PENDING',
    `document_path` VARCHAR(500) NULL,
    `submitted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`license_id`),
    UNIQUE KEY `uq_recycler_licenses_number` (`license_number`),
    KEY `idx_recycler_licenses_eligibility` (`recycler_user_id`, `license_status`, `expiry_date`),
    CONSTRAINT `fk_recycler_licenses_recycler`
        FOREIGN KEY (`recycler_user_id`) REFERENCES `authorized_recyclers` (`user_id`)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `recycler_capabilities` (
    `recycler_user_id` BIGINT UNSIGNED NOT NULL,
    `category_id` BIGINT UNSIGNED NOT NULL,
    `can_handle_high_risk` BOOLEAN NOT NULL DEFAULT FALSE,
    `capability_status` ENUM('PENDING', 'APPROVED', 'SUSPENDED') NOT NULL DEFAULT 'PENDING',
    PRIMARY KEY (`recycler_user_id`, `category_id`),
    KEY `idx_recycler_capabilities_category` (`category_id`, `capability_status`),
    CONSTRAINT `fk_recycler_capabilities_recycler`
        FOREIGN KEY (`recycler_user_id`) REFERENCES `authorized_recyclers` (`user_id`)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_recycler_capabilities_category`
        FOREIGN KEY (`category_id`) REFERENCES `waste_categories` (`category_id`)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `recycler_authorized_activities` (
    `activity_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `recycler_user_id` BIGINT UNSIGNED NOT NULL,
    `activity_type` ENUM(
        'COLLECTION',
        'TRANSPORTATION',
        'STORAGE',
        'RECOVERY',
        'RECYCLING',
        'DISPOSAL'
    ) NOT NULL,
    `activity_status` ENUM('PENDING', 'APPROVED', 'SUSPENDED') NOT NULL DEFAULT 'PENDING',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`activity_id`),
    UNIQUE KEY `uq_recycler_authorized_activity` (`recycler_user_id`, `activity_type`),
    KEY `idx_recycler_authorized_activities_status` (`activity_status`),
    CONSTRAINT `fk_recycler_authorized_activities_recycler`
        FOREIGN KEY (`recycler_user_id`) REFERENCES `authorized_recyclers` (`user_id`)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- Collection and whole-schedule verification
-- =========================================================

CREATE TABLE IF NOT EXISTS `schedule_collections` (
    `schedule_collection_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `schedule_id` BIGINT UNSIGNED NOT NULL,
    `submitted_by_collector_user_id` BIGINT UNSIGNED NOT NULL,
    `verification_status` ENUM('PENDING', 'VERIFIED', 'REJECTED') NOT NULL DEFAULT 'PENDING',
    `submitted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `verified_by_officer_user_id` BIGINT UNSIGNED NULL,
    `verified_at` DATETIME NULL,
    `verification_note` VARCHAR(500) NULL,
    PRIMARY KEY (`schedule_collection_id`),
    UNIQUE KEY `uq_schedule_collections_schedule` (`schedule_id`),
    KEY `idx_schedule_collections_verification` (`verification_status`),
    CONSTRAINT `fk_schedule_collections_schedule`
        FOREIGN KEY (`schedule_id`) REFERENCES `area_collection_schedules` (`schedule_id`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_schedule_collections_collector`
        FOREIGN KEY (`submitted_by_collector_user_id`) REFERENCES `collectors` (`user_id`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_schedule_collections_officer`
        FOREIGN KEY (`verified_by_officer_user_id`) REFERENCES `municipal_officers` (`user_id`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `chk_schedule_collections_verification_audit`
        CHECK (
            (`verification_status` = 'PENDING' AND `verified_by_officer_user_id` IS NULL AND `verified_at` IS NULL)
            OR
            (`verification_status` IN ('VERIFIED', 'REJECTED') AND `verified_by_officer_user_id` IS NOT NULL AND `verified_at` IS NOT NULL)
        )
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `collection_records` (
    `collection_record_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `schedule_collection_id` BIGINT UNSIGNED NOT NULL,
    `request_id` BIGINT UNSIGNED NOT NULL,
    `pickup_result` ENUM('COLLECTED', 'PARTIAL', 'NOT_COLLECTED') NOT NULL,
    `collected_at` DATETIME NOT NULL,
    `collector_note` VARCHAR(500) NULL,
    PRIMARY KEY (`collection_record_id`),
    UNIQUE KEY `uq_collection_records_request` (`request_id`),
    KEY `idx_collection_records_schedule_collection` (`schedule_collection_id`),
    KEY `idx_collection_records_result` (`pickup_result`),
    CONSTRAINT `fk_collection_records_schedule_collection`
        FOREIGN KEY (`schedule_collection_id`) REFERENCES `schedule_collections` (`schedule_collection_id`)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_collection_records_request`
        FOREIGN KEY (`request_id`) REFERENCES `e_waste_requests` (`request_id`)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `collection_record_items` (
    `record_item_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `collection_record_id` BIGINT UNSIGNED NOT NULL,
    `request_item_id` BIGINT UNSIGNED NOT NULL,
    `actual_quantity` INT UNSIGNED NOT NULL,
    `actual_weight_kg` DECIMAL(10,3) UNSIGNED NOT NULL,
    `actual_condition` ENUM('WORKING', 'DAMAGED', 'UNKNOWN') NOT NULL,
    `item_result` ENUM('COLLECTED', 'PARTIAL', 'NOT_COLLECTED') NOT NULL,
    `notes` VARCHAR(500) NULL,
    PRIMARY KEY (`record_item_id`),
    UNIQUE KEY `uq_collection_record_items_request_item` (`request_item_id`),
    KEY `idx_collection_record_items_record` (`collection_record_id`),
    KEY `idx_collection_record_items_result` (`item_result`),
    CONSTRAINT `fk_collection_record_items_record`
        FOREIGN KEY (`collection_record_id`) REFERENCES `collection_records` (`collection_record_id`)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_collection_record_items_request_item`
        FOREIGN KEY (`request_item_id`) REFERENCES `request_items` (`request_item_id`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `chk_collection_record_items_values`
        CHECK (
            (`item_result` = 'NOT_COLLECTED' AND `actual_quantity` = 0 AND `actual_weight_kg` = 0)
            OR
            (`item_result` IN ('COLLECTED', 'PARTIAL') AND `actual_quantity` > 0 AND `actual_weight_kg` > 0)
        )
) ENGINE=InnoDB;

-- =========================================================
-- E-Lots, bidding and handover
-- =========================================================

CREATE TABLE IF NOT EXISTS `e_lots` (
    `e_lot_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `lot_code` VARCHAR(30) NOT NULL,
    `created_by_collector_user_id` BIGINT UNSIGNED NOT NULL,
    `category_id` BIGINT UNSIGNED NOT NULL,
    `title` VARCHAR(180) NOT NULL,
    `lot_status` ENUM(
        'DRAFT', 'PENDING_VERIFICATION', 'REJECTED', 'OPEN_FOR_BIDDING',
        'AWARDED', 'COMPLETED', 'CANCELLED'
    ) NOT NULL DEFAULT 'DRAFT',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `verified_by_officer_user_id` BIGINT UNSIGNED NULL,
    `verification_note` VARCHAR(500) NULL,
    `verified_at` DATETIME NULL,
    `bidding_open_at` DATETIME NULL,
    `bidding_close_at` DATETIME NULL,
    PRIMARY KEY (`e_lot_id`),
    UNIQUE KEY `uq_e_lots_code` (`lot_code`),
    KEY `idx_e_lots_category_status` (`category_id`, `lot_status`),
    KEY `idx_e_lots_bidding_window` (`bidding_open_at`, `bidding_close_at`),
    CONSTRAINT `fk_e_lots_collector`
        FOREIGN KEY (`created_by_collector_user_id`) REFERENCES `collectors` (`user_id`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_e_lots_category`
        FOREIGN KEY (`category_id`) REFERENCES `waste_categories` (`category_id`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_e_lots_verified_by_officer`
        FOREIGN KEY (`verified_by_officer_user_id`) REFERENCES `municipal_officers` (`user_id`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `chk_e_lots_bidding_window`
        CHECK (
            `bidding_open_at` IS NULL
            OR `bidding_close_at` IS NULL
            OR `bidding_close_at` > `bidding_open_at`
        )
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `e_lot_items` (
    `record_item_id` BIGINT UNSIGNED NOT NULL,
    `e_lot_id` BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (`record_item_id`),
    KEY `idx_e_lot_items_lot` (`e_lot_id`),
    CONSTRAINT `fk_e_lot_items_record_item`
        FOREIGN KEY (`record_item_id`) REFERENCES `collection_record_items` (`record_item_id`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_e_lot_items_lot`
        FOREIGN KEY (`e_lot_id`) REFERENCES `e_lots` (`e_lot_id`)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `recycler_bids` (
    `bid_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `e_lot_id` BIGINT UNSIGNED NOT NULL,
    `recycler_user_id` BIGINT UNSIGNED NOT NULL,
    `bid_amount` DECIMAL(14,2) UNSIGNED NOT NULL,
    `bid_status` ENUM('SUBMITTED', 'WINNING', 'REJECTED', 'WITHDRAWN') NOT NULL DEFAULT 'SUBMITTED',
    `submitted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `reviewed_by_officer_user_id` BIGINT UNSIGNED NULL,
    `reviewed_at` DATETIME NULL,
    PRIMARY KEY (`bid_id`),
    UNIQUE KEY `uq_recycler_bids_lot_recycler` (`e_lot_id`, `recycler_user_id`),
    KEY `idx_recycler_bids_lot_status` (`e_lot_id`, `bid_status`),
    KEY `idx_recycler_bids_recycler` (`recycler_user_id`),
    CONSTRAINT `fk_recycler_bids_lot`
        FOREIGN KEY (`e_lot_id`) REFERENCES `e_lots` (`e_lot_id`)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_recycler_bids_recycler`
        FOREIGN KEY (`recycler_user_id`) REFERENCES `authorized_recyclers` (`user_id`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_recycler_bids_reviewed_by_officer`
        FOREIGN KEY (`reviewed_by_officer_user_id`) REFERENCES `municipal_officers` (`user_id`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `chk_recycler_bids_amount`
        CHECK (`bid_amount` > 0)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `handover_records` (
    `winning_bid_id` BIGINT UNSIGNED NOT NULL,
    `handover_status` ENUM('PENDING', 'SCHEDULED', 'COMPLETED', 'CANCELLED') NOT NULL DEFAULT 'PENDING',
    `handover_date` DATETIME NULL,
    `recorded_by_officer_user_id` BIGINT UNSIGNED NOT NULL,
    `remarks` VARCHAR(500) NULL,
    PRIMARY KEY (`winning_bid_id`),
    CONSTRAINT `fk_handover_records_winning_bid`
        FOREIGN KEY (`winning_bid_id`) REFERENCES `recycler_bids` (`bid_id`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_handover_records_officer`
        FOREIGN KEY (`recorded_by_officer_user_id`) REFERENCES `municipal_officers` (`user_id`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `chk_handover_completion_date`
        CHECK (`handover_status` <> 'COMPLETED' OR `handover_date` IS NOT NULL)
) ENGINE=InnoDB;

-- =========================================================
-- Cross-table business rules
-- =========================================================

DELIMITER $$

DROP TRIGGER IF EXISTS `trg_administrators_role_bi`$$
CREATE TRIGGER `trg_administrators_role_bi`
BEFORE INSERT ON `administrators`
FOR EACH ROW
BEGIN
    IF (SELECT `role` FROM `users` WHERE `user_id` = NEW.`user_id`) <> 'ADMIN' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Administrator profile requires an ADMIN user';
    END IF;
END$$

DROP TRIGGER IF EXISTS `trg_public_profiles_role_bi`$$
CREATE TRIGGER `trg_public_profiles_role_bi`
BEFORE INSERT ON `public_profiles`
FOR EACH ROW
BEGIN
    IF (SELECT `role` FROM `users` WHERE `user_id` = NEW.`user_id`) <> 'PUBLIC_USER' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Public profile requires a PUBLIC_USER account';
    END IF;
END$$

DROP TRIGGER IF EXISTS `trg_municipal_officers_role_bi`$$
CREATE TRIGGER `trg_municipal_officers_role_bi`
BEFORE INSERT ON `municipal_officers`
FOR EACH ROW
BEGIN
    IF (SELECT `role` FROM `users` WHERE `user_id` = NEW.`user_id`) <> 'MUNICIPAL_OFFICER' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Officer profile requires a MUNICIPAL_OFFICER user';
    END IF;
END$$

DROP TRIGGER IF EXISTS `trg_collectors_role_bi`$$
CREATE TRIGGER `trg_collectors_role_bi`
BEFORE INSERT ON `collectors`
FOR EACH ROW
BEGIN
    IF (SELECT `role` FROM `users` WHERE `user_id` = NEW.`user_id`) <> 'COLLECTOR' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Collector profile requires a COLLECTOR user';
    END IF;
END$$

DROP TRIGGER IF EXISTS `trg_recyclers_role_bi`$$
CREATE TRIGGER `trg_recyclers_role_bi`
BEFORE INSERT ON `authorized_recyclers`
FOR EACH ROW
BEGIN
    IF (SELECT `role` FROM `users` WHERE `user_id` = NEW.`user_id`) <> 'RECYCLER' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Recycler profile requires a RECYCLER user';
    END IF;
END$$

DROP TRIGGER IF EXISTS `trg_users_admin_creator_bi`$$
CREATE TRIGGER `trg_users_admin_creator_bi`
BEFORE INSERT ON `users`
FOR EACH ROW
BEGIN
    IF NEW.`created_by_admin_user_id` IS NOT NULL
       AND NOT EXISTS (
           SELECT 1 FROM `users` u
           WHERE u.`user_id` = NEW.`created_by_admin_user_id`
             AND u.`role` = 'ADMIN'
             AND u.`account_status` = 'ACTIVE'
       ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Internal accounts must be created by an active administrator';
    END IF;
END$$

DROP TRIGGER IF EXISTS `trg_schedule_assignments_one_active_bi`$$
CREATE TRIGGER `trg_schedule_assignments_one_active_bi`
BEFORE INSERT ON `schedule_assignments`
FOR EACH ROW
BEGIN
    IF NEW.`unassigned_at` IS NULL
       AND EXISTS (
           SELECT 1 FROM `schedule_assignments`
           WHERE `schedule_id` = NEW.`schedule_id`
             AND `unassigned_at` IS NULL
       ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'A schedule can have only one active collector assignment';
    END IF;

    IF NEW.`vehicle_id` IS NOT NULL
       AND EXISTS (
           SELECT 1 FROM `schedule_assignments`
           WHERE `vehicle_id` = NEW.`vehicle_id`
             AND `unassigned_at` IS NULL
       ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'The vehicle already has an active assignment';
    END IF;
END$$

DROP TRIGGER IF EXISTS `trg_schedule_assignments_one_active_bu`$$
CREATE TRIGGER `trg_schedule_assignments_one_active_bu`
BEFORE UPDATE ON `schedule_assignments`
FOR EACH ROW
BEGIN
    IF NEW.`unassigned_at` IS NULL
       AND EXISTS (
           SELECT 1 FROM `schedule_assignments`
           WHERE `schedule_id` = NEW.`schedule_id`
             AND `unassigned_at` IS NULL
             AND `assignment_id` <> OLD.`assignment_id`
       ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'A schedule can have only one active collector assignment';
    END IF;

    IF NEW.`vehicle_id` IS NOT NULL AND NEW.`unassigned_at` IS NULL
       AND EXISTS (
           SELECT 1 FROM `schedule_assignments`
           WHERE `vehicle_id` = NEW.`vehicle_id`
             AND `unassigned_at` IS NULL
             AND `assignment_id` <> OLD.`assignment_id`
       ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'The vehicle already has an active assignment';
    END IF;
END$$

DROP TRIGGER IF EXISTS `trg_requests_schedule_eligibility_bi`$$
CREATE TRIGGER `trg_requests_schedule_eligibility_bi`
BEFORE INSERT ON `e_waste_requests`
FOR EACH ROW
BEGIN
    DECLARE v_status VARCHAR(32);
    DECLARE v_cutoff DATETIME;
    DECLARE v_capacity INT;
    DECLARE v_schedule_area BIGINT UNSIGNED;
    DECLARE v_user_area BIGINT UNSIGNED;
    DECLARE v_request_count INT;

    SELECT `schedule_status`, `request_cutoff_at`, `request_capacity`, `postal_area_id`
      INTO v_status, v_cutoff, v_capacity, v_schedule_area
      FROM `area_collection_schedules`
     WHERE `schedule_id` = NEW.`schedule_id`;

    SELECT `postal_area_id`
      INTO v_user_area
      FROM `public_profiles`
     WHERE `user_id` = NEW.`public_user_id`;

    SELECT COUNT(*)
      INTO v_request_count
      FROM `e_waste_requests`
     WHERE `schedule_id` = NEW.`schedule_id`
       AND `request_status` NOT IN ('REJECTED', 'CANCELLED');

    IF v_status <> 'OPEN' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Pickup requests require an OPEN schedule';
    END IF;
    IF CURRENT_TIMESTAMP > v_cutoff THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'The schedule request cutoff has passed';
    END IF;
    IF v_user_area <> v_schedule_area THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'The public user and schedule must belong to the same postal area';
    END IF;
    IF v_request_count >= v_capacity THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'The schedule request capacity has been reached';
    END IF;
END$$

DROP TRIGGER IF EXISTS `trg_schedule_collections_assignment_bi`$$
CREATE TRIGGER `trg_schedule_collections_assignment_bi`
BEFORE INSERT ON `schedule_collections`
FOR EACH ROW
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM `schedule_assignments`
        WHERE `schedule_id` = NEW.`schedule_id`
          AND `collector_user_id` = NEW.`submitted_by_collector_user_id`
          AND `unassigned_at` IS NULL
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Only the active assigned collector can submit the schedule collection';
    END IF;
END$$

DROP TRIGGER IF EXISTS `trg_collection_records_schedule_bi`$$
CREATE TRIGGER `trg_collection_records_schedule_bi`
BEFORE INSERT ON `collection_records`
FOR EACH ROW
BEGIN
    DECLARE v_collection_schedule BIGINT UNSIGNED;
    DECLARE v_request_schedule BIGINT UNSIGNED;

    SELECT `schedule_id` INTO v_collection_schedule
      FROM `schedule_collections`
     WHERE `schedule_collection_id` = NEW.`schedule_collection_id`;

    SELECT `schedule_id` INTO v_request_schedule
      FROM `e_waste_requests`
     WHERE `request_id` = NEW.`request_id`;

    IF v_collection_schedule <> v_request_schedule THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'The collection record request belongs to a different schedule';
    END IF;
END$$

DROP TRIGGER IF EXISTS `trg_collection_record_items_request_bi`$$
CREATE TRIGGER `trg_collection_record_items_request_bi`
BEFORE INSERT ON `collection_record_items`
FOR EACH ROW
BEGIN
    DECLARE v_record_request BIGINT UNSIGNED;
    DECLARE v_item_request BIGINT UNSIGNED;

    SELECT `request_id` INTO v_record_request
      FROM `collection_records`
     WHERE `collection_record_id` = NEW.`collection_record_id`;

    SELECT `request_id` INTO v_item_request
      FROM `request_items`
     WHERE `request_item_id` = NEW.`request_item_id`;

    IF v_record_request <> v_item_request THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'The collected item belongs to a different pickup request';
    END IF;
END$$

DROP TRIGGER IF EXISTS `trg_e_lot_status_bi`$$
CREATE TRIGGER `trg_e_lot_status_bi`
BEFORE INSERT ON `e_lots`
FOR EACH ROW
BEGIN
    IF NEW.`lot_status` = 'REJECTED'
       AND (NEW.`verified_by_officer_user_id` IS NULL OR NEW.`verified_at` IS NULL) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'A rejected E-Lot requires officer verification details';
    END IF;

    IF NEW.`lot_status` IN ('OPEN_FOR_BIDDING', 'AWARDED', 'COMPLETED')
       AND (
           NEW.`verified_by_officer_user_id` IS NULL OR NEW.`verified_at` IS NULL
           OR NEW.`bidding_open_at` IS NULL OR NEW.`bidding_close_at` IS NULL
           OR NEW.`bidding_close_at` <= NEW.`bidding_open_at`
       ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'An approved E-Lot requires verification and a valid bidding window';
    END IF;
END$$

DROP TRIGGER IF EXISTS `trg_e_lot_status_bu`$$
CREATE TRIGGER `trg_e_lot_status_bu`
BEFORE UPDATE ON `e_lots`
FOR EACH ROW
BEGIN
    IF NEW.`lot_status` = 'REJECTED'
       AND (NEW.`verified_by_officer_user_id` IS NULL OR NEW.`verified_at` IS NULL) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'A rejected E-Lot requires officer verification details';
    END IF;

    IF NEW.`lot_status` IN ('OPEN_FOR_BIDDING', 'AWARDED', 'COMPLETED')
       AND (
           NEW.`verified_by_officer_user_id` IS NULL OR NEW.`verified_at` IS NULL
           OR NEW.`bidding_open_at` IS NULL OR NEW.`bidding_close_at` IS NULL
           OR NEW.`bidding_close_at` <= NEW.`bidding_open_at`
       ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'An approved E-Lot requires verification and a valid bidding window';
    END IF;

    IF NEW.`lot_status` IN ('AWARDED', 'COMPLETED')
       AND NOT EXISTS (
           SELECT 1 FROM `recycler_bids`
           WHERE `e_lot_id` = NEW.`e_lot_id`
             AND `bid_status` = 'WINNING'
       ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'An awarded E-Lot requires one winning bid';
    END IF;
END$$

DROP TRIGGER IF EXISTS `trg_e_lot_items_verified_source_bi`$$
CREATE TRIGGER `trg_e_lot_items_verified_source_bi`
BEFORE INSERT ON `e_lot_items`
FOR EACH ROW
BEGIN
    DECLARE v_verification_status VARCHAR(20);
    DECLARE v_item_result VARCHAR(20);
    DECLARE v_source_category BIGINT UNSIGNED;
    DECLARE v_source_collector BIGINT UNSIGNED;
    DECLARE v_lot_category BIGINT UNSIGNED;
    DECLARE v_lot_collector BIGINT UNSIGNED;

    SELECT sc.`verification_status`, cri.`item_result`, wi.`category_id`, sc.`submitted_by_collector_user_id`
      INTO v_verification_status, v_item_result, v_source_category, v_source_collector
      FROM `collection_record_items` cri
      JOIN `collection_records` cr ON cr.`collection_record_id` = cri.`collection_record_id`
      JOIN `schedule_collections` sc ON sc.`schedule_collection_id` = cr.`schedule_collection_id`
      JOIN `request_items` ri ON ri.`request_item_id` = cri.`request_item_id`
      JOIN `e_waste_items` wi ON wi.`waste_item_id` = ri.`waste_item_id`
     WHERE cri.`record_item_id` = NEW.`record_item_id`;

    SELECT `category_id`, `created_by_collector_user_id`
      INTO v_lot_category, v_lot_collector
      FROM `e_lots`
     WHERE `e_lot_id` = NEW.`e_lot_id`;

    IF v_verification_status <> 'VERIFIED' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Only officer-verified collection items can enter an E-Lot';
    END IF;
    IF v_item_result NOT IN ('COLLECTED', 'PARTIAL') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'A not-collected item cannot enter an E-Lot';
    END IF;
    IF v_source_category <> v_lot_category THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'All E-Lot items must match the E-Lot category';
    END IF;
    IF v_source_collector <> v_lot_collector THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'A collector can lot only items from their own verified schedules';
    END IF;
END$$

DROP TRIGGER IF EXISTS `trg_recycler_bids_eligibility_bi`$$
CREATE TRIGGER `trg_recycler_bids_eligibility_bi`
BEFORE INSERT ON `recycler_bids`
FOR EACH ROW
BEGIN
    DECLARE v_lot_status VARCHAR(30);
    DECLARE v_open_at DATETIME;
    DECLARE v_close_at DATETIME;
    DECLARE v_category_id BIGINT UNSIGNED;
    DECLARE v_recycler_status VARCHAR(20);
    DECLARE v_license_count INT;
    DECLARE v_capability_count INT;
    DECLARE v_can_high_risk BOOLEAN;
    DECLARE v_has_high_risk BOOLEAN;

    SELECT `lot_status`, `bidding_open_at`, `bidding_close_at`, `category_id`
      INTO v_lot_status, v_open_at, v_close_at, v_category_id
      FROM `e_lots`
     WHERE `e_lot_id` = NEW.`e_lot_id`;

    SELECT `verification_status` INTO v_recycler_status
      FROM `authorized_recyclers`
     WHERE `user_id` = NEW.`recycler_user_id`;

    SELECT COUNT(*) INTO v_license_count
      FROM `recycler_licenses`
     WHERE `recycler_user_id` = NEW.`recycler_user_id`
       AND `license_status` = 'VALID'
       AND `expiry_date` >= CURRENT_DATE;

    SELECT COUNT(*), COALESCE(MAX(`can_handle_high_risk`), FALSE)
      INTO v_capability_count, v_can_high_risk
      FROM `recycler_capabilities`
     WHERE `recycler_user_id` = NEW.`recycler_user_id`
       AND `category_id` = v_category_id
       AND `capability_status` = 'APPROVED';

    SELECT EXISTS (
        SELECT 1
          FROM `e_lot_items` eli
          JOIN `collection_record_items` cri ON cri.`record_item_id` = eli.`record_item_id`
          JOIN `request_items` ri ON ri.`request_item_id` = cri.`request_item_id`
         WHERE eli.`e_lot_id` = NEW.`e_lot_id`
           AND ri.`applied_risk_level` = 'HIGH'
    ) INTO v_has_high_risk;

    IF v_lot_status <> 'OPEN_FOR_BIDDING'
       OR CURRENT_TIMESTAMP < v_open_at
       OR CURRENT_TIMESTAMP > v_close_at THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'The E-Lot is not currently open for bidding';
    END IF;
    IF v_recycler_status <> 'VERIFIED' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Only verified recyclers can bid';
    END IF;
    IF v_license_count = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'A valid recycler licence is required to bid';
    END IF;
    IF v_capability_count = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Recycler capability does not match the E-Lot category';
    END IF;
    IF v_has_high_risk AND NOT v_can_high_risk THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'High-risk handling capability is required for this E-Lot';
    END IF;
END$$

DROP TRIGGER IF EXISTS `trg_recycler_bids_one_winner_bi`$$
CREATE TRIGGER `trg_recycler_bids_one_winner_bi`
BEFORE INSERT ON `recycler_bids`
FOR EACH ROW
BEGIN
    IF NEW.`bid_status` = 'WINNING'
       AND EXISTS (
           SELECT 1 FROM `recycler_bids`
           WHERE `e_lot_id` = NEW.`e_lot_id`
             AND `bid_status` = 'WINNING'
       ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'An E-Lot can have only one winning bid';
    END IF;
END$$

DROP TRIGGER IF EXISTS `trg_recycler_bids_one_winner_bu`$$
CREATE TRIGGER `trg_recycler_bids_one_winner_bu`
BEFORE UPDATE ON `recycler_bids`
FOR EACH ROW
BEGIN
    IF NEW.`bid_status` = 'WINNING'
       AND EXISTS (
           SELECT 1 FROM `recycler_bids`
           WHERE `e_lot_id` = NEW.`e_lot_id`
             AND `bid_status` = 'WINNING'
             AND `bid_id` <> OLD.`bid_id`
       ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'An E-Lot can have only one winning bid';
    END IF;
END$$

DROP TRIGGER IF EXISTS `trg_handover_winning_bid_bi`$$
CREATE TRIGGER `trg_handover_winning_bid_bi`
BEFORE INSERT ON `handover_records`
FOR EACH ROW
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM `recycler_bids`
        WHERE `bid_id` = NEW.`winning_bid_id`
          AND `bid_status` = 'WINNING'
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'A handover requires a winning recycler bid';
    END IF;
END$$

DELIMITER ;
