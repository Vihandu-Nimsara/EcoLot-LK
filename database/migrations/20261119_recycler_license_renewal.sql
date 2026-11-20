-- Additive licence renewal storage. No legacy request, document or scope backfill.
CREATE TABLE IF NOT EXISTS `recycler_license_update_requests` (
  `request_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `recycler_user_id` bigint(20) unsigned NOT NULL,
  `current_license_id` bigint(20) unsigned DEFAULT NULL,
  `requested_license_number` varchar(100) NOT NULL,
  `requested_expiry_date` date NOT NULL,
  `document_path` varchar(500) NOT NULL,
  `request_status` enum('PENDING','APPROVED','REJECTED') NOT NULL DEFAULT 'PENDING',
  `submitted_at` datetime NOT NULL DEFAULT current_timestamp(),
  `reviewed_by_admin_user_id` bigint(20) unsigned DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `review_note` varchar(500) DEFAULT NULL,
  `pending_recycler_user_id` bigint(20) unsigned GENERATED ALWAYS AS (case when `request_status` = 'PENDING' then `recycler_user_id` else NULL end) STORED,
  PRIMARY KEY (`request_id`),
  UNIQUE KEY `uq_recycler_license_one_pending` (`pending_recycler_user_id`),
  KEY `idx_license_update_owner` (`recycler_user_id`,`request_status`,`submitted_at`),
  KEY `idx_license_update_current` (`current_license_id`),
  KEY `idx_license_update_reviewer` (`reviewed_by_admin_user_id`),
  CONSTRAINT `fk_license_update_admin_restrict` FOREIGN KEY (`reviewed_by_admin_user_id`) REFERENCES `administrators` (`user_id`) ON UPDATE RESTRICT,
  CONSTRAINT `fk_license_update_current` FOREIGN KEY (`current_license_id`) REFERENCES `recycler_licenses` (`license_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_license_update_recycler_guarded` FOREIGN KEY (`recycler_user_id`) REFERENCES `authorized_recyclers` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `chk_license_update_review` CHECK (`request_status` = 'PENDING' and `reviewed_by_admin_user_id` is null and `reviewed_at` is null and `review_note` is null or `request_status` = 'APPROVED' and `reviewed_by_admin_user_id` is not null and `reviewed_at` is not null or `request_status` = 'REJECTED' and `reviewed_by_admin_user_id` is not null and `reviewed_at` is not null and `review_note` is not null)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `recycler_license_revisions` (
  `revision_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `license_id` bigint(20) unsigned NOT NULL,
  `update_request_id` bigint(20) unsigned NOT NULL,
  `previous_license_number` varchar(100) NOT NULL,
  `previous_expiry_date` date NOT NULL,
  `previous_document_path` varchar(500) DEFAULT NULL,
  `previous_license_status` enum('PENDING','VALID','REJECTED','EXPIRED','REVOKED','SUPERSEDED') NOT NULL,
  `previous_submitted_at` datetime NOT NULL,
  `previous_reviewed_by_admin_user_id` bigint(20) unsigned DEFAULT NULL,
  `previous_reviewed_at` datetime DEFAULT NULL,
  `previous_review_note` varchar(500) DEFAULT NULL,
  `archived_at` datetime NOT NULL DEFAULT current_timestamp(),
  `archived_by_admin_user_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`revision_id`),
  UNIQUE KEY `uq_license_revision_request` (`update_request_id`),
  KEY `idx_license_revision_history` (`license_id`,`archived_at`),
  KEY `fk_license_revision_admin` (`archived_by_admin_user_id`),
  CONSTRAINT `fk_license_revision_admin` FOREIGN KEY (`archived_by_admin_user_id`) REFERENCES `administrators` (`user_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_license_revision_license` FOREIGN KEY (`license_id`) REFERENCES `recycler_licenses` (`license_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SET @profile_ddl = IF(EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='recycler_license_update_requests' AND COLUMN_NAME='document_sha256'), 'DO 0', 'ALTER TABLE recycler_license_update_requests ADD COLUMN document_sha256 CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL');
PREPARE profile_statement FROM @profile_ddl;
EXECUTE profile_statement;
DEALLOCATE PREPARE profile_statement;
SET @profile_ddl = IF(EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='recycler_license_update_requests' AND COLUMN_NAME='current_identity_sha256'), 'DO 0', 'ALTER TABLE recycler_license_update_requests ADD COLUMN current_identity_sha256 CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL');
PREPARE profile_statement FROM @profile_ddl;
EXECUTE profile_statement;
DEALLOCATE PREPARE profile_statement;
SET @profile_ddl = IF(EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='recycler_license_update_requests' AND COLUMN_NAME='current_document_sha256'), 'DO 0', 'ALTER TABLE recycler_license_update_requests ADD COLUMN current_document_sha256 CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL');
PREPARE profile_statement FROM @profile_ddl;
EXECUTE profile_statement;
DEALLOCATE PREPARE profile_statement;
SET @profile_ddl = IF(EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='recycler_license_update_requests' AND COLUMN_NAME='explanation'), 'DO 0', 'ALTER TABLE recycler_license_update_requests ADD COLUMN explanation VARCHAR(500) NULL');
PREPARE profile_statement FROM @profile_ddl;
EXECUTE profile_statement;
DEALLOCATE PREPARE profile_statement;
SET @profile_ddl = IF(EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='recycler_license_revisions' AND COLUMN_NAME='previous_document_sha256'), 'DO 0', 'ALTER TABLE recycler_license_revisions ADD COLUMN previous_document_sha256 CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL');
PREPARE profile_statement FROM @profile_ddl;
EXECUTE profile_statement;
DEALLOCATE PREPARE profile_statement;
SET @profile_ddl = IF(EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='recycler_license_revisions' AND COLUMN_NAME='previous_document_version'), 'DO 0', 'ALTER TABLE recycler_license_revisions ADD COLUMN previous_document_version BIGINT UNSIGNED NULL');
PREPARE profile_statement FROM @profile_ddl;
EXECUTE profile_statement;
DEALLOCATE PREPARE profile_statement;

-- Preserve identity/history: stored generated owner keys cannot cascade on MySQL 8.
SET @profile_owner_fk = (SELECT CONSTRAINT_NAME FROM information_schema.REFERENTIAL_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='recycler_license_update_requests'
      AND CONSTRAINT_NAME IN ('fk_license_update_recycler','fk_license_update_recycler_restrict')
      AND (UPDATE_RULE='CASCADE' OR DELETE_RULE='CASCADE') LIMIT 1);
SET @reservation_fk_ddl = IF(@profile_owner_fk IS NULL,'DO 0',CONCAT(
    'ALTER TABLE recycler_license_update_requests DROP FOREIGN KEY `',@profile_owner_fk,
    '`, ADD CONSTRAINT fk_license_update_recycler_guarded FOREIGN KEY (recycler_user_id) REFERENCES authorized_recyclers(user_id) ON DELETE RESTRICT ON UPDATE RESTRICT'));
PREPARE reservation_fk_statement FROM @reservation_fk_ddl;
EXECUTE reservation_fk_statement;
DEALLOCATE PREPARE reservation_fk_statement;

-- Reviewed identities also participate in CHECK constraints on MariaDB 10.11.
SET @reservation_fk_ddl = IF(
    EXISTS (SELECT 1 FROM information_schema.REFERENTIAL_CONSTRAINTS
        WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='recycler_license_update_requests'
          AND CONSTRAINT_NAME='fk_license_update_admin' AND UPDATE_RULE='CASCADE'),
    'ALTER TABLE `recycler_license_update_requests` DROP FOREIGN KEY `fk_license_update_admin`, ADD CONSTRAINT `fk_license_update_admin_restrict` FOREIGN KEY (`reviewed_by_admin_user_id`) REFERENCES `administrators` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT',
    'DO 0'
);
PREPARE reservation_fk_statement FROM @reservation_fk_ddl;
EXECUTE reservation_fk_statement;
DEALLOCATE PREPARE reservation_fk_statement;

DELIMITER $$
DROP TRIGGER IF EXISTS trg_recycler_renewal_submission_bu$$
CREATE TRIGGER trg_recycler_renewal_submission_bu
BEFORE UPDATE ON recycler_license_update_requests FOR EACH ROW
BEGIN
    IF OLD.request_status <> 'PENDING' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='A recorded licence renewal decision is immutable';
    END IF;
    IF OLD.document_sha256 IS NOT NULL AND (
        NOT (NEW.request_id <=> OLD.request_id) OR NOT (NEW.recycler_user_id <=> OLD.recycler_user_id)
        OR NOT (BINARY NEW.requested_license_number <=> BINARY OLD.requested_license_number)
        OR NOT (NEW.requested_expiry_date <=> OLD.requested_expiry_date)
        OR NOT (BINARY NEW.document_path <=> BINARY OLD.document_path)
        OR NOT (NEW.document_sha256 <=> OLD.document_sha256)
        OR NOT (NEW.current_identity_sha256 <=> OLD.current_identity_sha256)
        OR NOT (NEW.current_document_sha256 <=> OLD.current_document_sha256)
        OR NOT (NEW.submitted_at <=> OLD.submitted_at)
        OR NOT (BINARY NEW.explanation <=> BINARY OLD.explanation)
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='A submitted licence renewal identity is immutable';
    END IF;
END$$
DELIMITER ;
