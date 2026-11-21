-- Adopt the established request contract without changing existing decisions.
ALTER TABLE recycler_capabilities MODIFY COLUMN capability_status ENUM('PENDING','APPROVED','REJECTED','SUSPENDED','INACTIVE') NOT NULL DEFAULT 'PENDING';
CREATE TABLE IF NOT EXISTS `recycler_capability_change_requests` (
  `request_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `recycler_user_id` bigint(20) unsigned NOT NULL,
  `category_id` bigint(20) unsigned NOT NULL,
  `request_type` enum('ADD','UPDATE','DEACTIVATE') NOT NULL,
  `previous_capability_status` enum('PENDING','APPROVED','REJECTED','SUSPENDED','INACTIVE') DEFAULT NULL,
  `previous_can_handle_high_risk` tinyint(1) DEFAULT NULL,
  `requested_can_handle_high_risk` tinyint(1) DEFAULT NULL,
  `request_reason` varchar(500) NOT NULL,
  `request_status` enum('PENDING','APPROVED','REJECTED') NOT NULL DEFAULT 'PENDING',
  `submitted_at` datetime NOT NULL DEFAULT current_timestamp(),
  `reviewed_by_admin_user_id` bigint(20) unsigned DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `review_note` varchar(500) DEFAULT NULL,
  `pending_recycler_user_id` bigint(20) unsigned GENERATED ALWAYS AS (case when `request_status` = 'PENDING' then `recycler_user_id` else NULL end) STORED,
  `pending_category_id` bigint(20) unsigned GENERATED ALWAYS AS (case when `request_status` = 'PENDING' then `category_id` else NULL end) STORED,
  PRIMARY KEY (`request_id`),
  UNIQUE KEY `uq_capability_one_pending` (`pending_recycler_user_id`,`pending_category_id`),
  KEY `idx_capability_request_owner` (`recycler_user_id`,`request_status`,`submitted_at`),
  KEY `idx_capability_request_category` (`category_id`),
  KEY `idx_capability_request_reviewer` (`reviewed_by_admin_user_id`),
  CONSTRAINT `fk_capability_request_admin_restrict` FOREIGN KEY (`reviewed_by_admin_user_id`) REFERENCES `administrators` (`user_id`) ON UPDATE RESTRICT,
  CONSTRAINT `fk_capability_request_category_restrict` FOREIGN KEY (`category_id`) REFERENCES `waste_categories` (`category_id`) ON UPDATE RESTRICT,
  CONSTRAINT `fk_capability_request_recycler_restrict` FOREIGN KEY (`recycler_user_id`) REFERENCES `authorized_recyclers` (`user_id`) ON UPDATE RESTRICT,
  CONSTRAINT `chk_capability_request_reason` CHECK (char_length(trim(`request_reason`)) > 0),
  CONSTRAINT `chk_capability_request_snapshot` CHECK (`request_type` = 'ADD' and `previous_capability_status` is null and `previous_can_handle_high_risk` is null or `request_type` = 'UPDATE' and `previous_capability_status` is not null and `previous_capability_status` in ('APPROVED','INACTIVE') and `previous_can_handle_high_risk` is not null and (`previous_capability_status` = 'INACTIVE' or `previous_can_handle_high_risk` <> `requested_can_handle_high_risk`) or `request_type` = 'DEACTIVATE' and `previous_capability_status` is not null and `previous_capability_status` = 'APPROVED' and `previous_can_handle_high_risk` is not null),
  CONSTRAINT `chk_capability_request_risk` CHECK ((`previous_can_handle_high_risk` is null or `previous_can_handle_high_risk` in (0,1)) and (`request_type` = 'DEACTIVATE' and `requested_can_handle_high_risk` is null or `request_type` in ('ADD','UPDATE') and `requested_can_handle_high_risk` is not null and `requested_can_handle_high_risk` in (0,1))),
  CONSTRAINT `chk_capability_request_review` CHECK (`request_status` = 'PENDING' and `reviewed_by_admin_user_id` is null and `reviewed_at` is null and `review_note` is null or `request_status` = 'APPROVED' and `reviewed_by_admin_user_id` is not null and `reviewed_at` is not null and `reviewed_at` >= `submitted_at` or `request_status` = 'REJECTED' and `reviewed_by_admin_user_id` is not null and `reviewed_at` is not null and `reviewed_at` >= `submitted_at` and `review_note` is not null and char_length(trim(`review_note`)) > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SET @profile_ddl = IF(EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='recycler_capability_change_requests' AND COLUMN_NAME='capability_snapshot_json'), 'DO 0', 'ALTER TABLE recycler_capability_change_requests ADD COLUMN capability_snapshot_json LONGTEXT NULL');
PREPARE profile_statement FROM @profile_ddl;
EXECUTE profile_statement;
DEALLOCATE PREPARE profile_statement;
SET @profile_ddl = IF(EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='recycler_capability_change_requests' AND COLUMN_NAME='license_identity_sha256'), 'DO 0', 'ALTER TABLE recycler_capability_change_requests ADD COLUMN license_identity_sha256 CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL');
PREPARE profile_statement FROM @profile_ddl;
EXECUTE profile_statement;
DEALLOCATE PREPARE profile_statement;
SET @profile_ddl = IF(EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='recycler_capability_change_requests' AND COLUMN_NAME='approved_activity_snapshot_json'), 'DO 0', 'ALTER TABLE recycler_capability_change_requests ADD COLUMN approved_activity_snapshot_json TEXT NULL');
PREPARE profile_statement FROM @profile_ddl;
EXECUTE profile_statement;
DEALLOCATE PREPARE profile_statement;

-- MariaDB 10.11 rejects cascading updates of stored generated-key inputs.
-- Tighten only the existing FK's update action, preserving rows and delete behavior.
SET @reservation_fk_ddl = IF(
    EXISTS (SELECT 1 FROM information_schema.REFERENTIAL_CONSTRAINTS
        WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='recycler_capability_change_requests'
          AND CONSTRAINT_NAME='fk_capability_request_category' AND UPDATE_RULE='CASCADE'),
    'ALTER TABLE `recycler_capability_change_requests` DROP FOREIGN KEY `fk_capability_request_category`, ADD CONSTRAINT `fk_capability_request_category_restrict` FOREIGN KEY (`category_id`) REFERENCES `waste_categories` (`category_id`) ON DELETE RESTRICT ON UPDATE RESTRICT',
    'DO 0'
);
PREPARE reservation_fk_statement FROM @reservation_fk_ddl;
EXECUTE reservation_fk_statement;
DEALLOCATE PREPARE reservation_fk_statement;

-- MariaDB 10.11 rejects cascading updates of stored generated-key inputs.
-- Tighten only the existing FK's update action, preserving rows and delete behavior.
SET @reservation_fk_ddl = IF(
    EXISTS (SELECT 1 FROM information_schema.REFERENTIAL_CONSTRAINTS
        WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='recycler_capability_change_requests'
          AND CONSTRAINT_NAME='fk_capability_request_recycler' AND UPDATE_RULE='CASCADE'),
    'ALTER TABLE `recycler_capability_change_requests` DROP FOREIGN KEY `fk_capability_request_recycler`, ADD CONSTRAINT `fk_capability_request_recycler_restrict` FOREIGN KEY (`recycler_user_id`) REFERENCES `authorized_recyclers` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT',
    'DO 0'
);
PREPARE reservation_fk_statement FROM @reservation_fk_ddl;
EXECUTE reservation_fk_statement;
DEALLOCATE PREPARE reservation_fk_statement;

-- Reviewed identities also participate in CHECK constraints on MariaDB 10.11.
SET @reservation_fk_ddl = IF(
    EXISTS (SELECT 1 FROM information_schema.REFERENTIAL_CONSTRAINTS
        WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='recycler_capability_change_requests'
          AND CONSTRAINT_NAME='fk_capability_request_admin' AND UPDATE_RULE='CASCADE'),
    'ALTER TABLE `recycler_capability_change_requests` DROP FOREIGN KEY `fk_capability_request_admin`, ADD CONSTRAINT `fk_capability_request_admin_restrict` FOREIGN KEY (`reviewed_by_admin_user_id`) REFERENCES `administrators` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT',
    'DO 0'
);
PREPARE reservation_fk_statement FROM @reservation_fk_ddl;
EXECUTE reservation_fk_statement;
DEALLOCATE PREPARE reservation_fk_statement;

DELIMITER $$
DROP TRIGGER IF EXISTS trg_recycler_capability_request_bu$$
CREATE TRIGGER trg_recycler_capability_request_bu
BEFORE UPDATE ON recycler_capability_change_requests FOR EACH ROW
BEGIN
 IF OLD.request_status <> 'PENDING' THEN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='A completed capability decision is immutable';
 END IF;
 IF OLD.capability_snapshot_json IS NOT NULL AND (
  NOT (NEW.recycler_user_id <=> OLD.recycler_user_id) OR NOT (NEW.category_id <=> OLD.category_id)
  OR NOT (NEW.request_id <=> OLD.request_id) OR NOT (NEW.request_type <=> OLD.request_type)
  OR NOT (NEW.previous_capability_status <=> OLD.previous_capability_status)
  OR NOT (NEW.previous_can_handle_high_risk <=> OLD.previous_can_handle_high_risk)
  OR NOT (NEW.requested_can_handle_high_risk <=> OLD.requested_can_handle_high_risk)
  OR NOT (BINARY NEW.request_reason <=> BINARY OLD.request_reason)
  OR NOT (BINARY NEW.capability_snapshot_json <=> BINARY OLD.capability_snapshot_json)
  OR NOT (NEW.license_identity_sha256 <=> OLD.license_identity_sha256)
  OR NOT (BINARY NEW.approved_activity_snapshot_json <=> BINARY OLD.approved_activity_snapshot_json)
  OR NOT (NEW.submitted_at <=> OLD.submitted_at)
 ) THEN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Submitted capability request metadata is immutable';
 END IF;
END$$
DELIMITER ;
