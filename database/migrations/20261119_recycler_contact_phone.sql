-- Adopt the compatible legacy phone-change table. Registration OTPs remain separate.
-- Preflight existing definitions and duplicate active targets. No history is deleted or backfilled.
CREATE TABLE IF NOT EXISTS `recycler_phone_change_challenges` (
  `challenge_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `recycler_user_id` bigint(20) unsigned NOT NULL,
  `original_mobile_number` varchar(20) NOT NULL,
  `proposed_mobile_number` varchar(20) NOT NULL,
  `otp_hash` varchar(255) NOT NULL,
  `session_context_hash` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `password_state_fingerprint` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `attempt_count` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime NOT NULL,
  `delivery_status` enum('PENDING','SENT','FAILED') NOT NULL DEFAULT 'PENDING',
  `sent_at` datetime DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `invalidated_at` datetime DEFAULT NULL,
  `active_recycler_user_id` bigint(20) unsigned GENERATED ALWAYS AS (case when `verified_at` is null and `invalidated_at` is null then `recycler_user_id` else NULL end) STORED,
  PRIMARY KEY (`challenge_id`),
  UNIQUE KEY `uq_phone_change_one_active` (`active_recycler_user_id`),
  KEY `idx_phone_change_owner_created` (`recycler_user_id`,`created_at`),
  KEY `idx_phone_change_target_created` (`proposed_mobile_number`,`created_at`),
  CONSTRAINT `fk_phone_change_recycler_restrict` FOREIGN KEY (`recycler_user_id`) REFERENCES `authorized_recyclers` (`user_id`) ON UPDATE RESTRICT,
  CONSTRAINT `chk_phone_change_numbers` CHECK (`original_mobile_number` regexp '^947[0-9]{8}$' and `proposed_mobile_number` regexp '^947[0-9]{8}$' and `original_mobile_number` <> `proposed_mobile_number`),
  CONSTRAINT `chk_phone_change_bindings` CHECK (`session_context_hash` regexp '^[0-9a-f]{64}$' and `password_state_fingerprint` regexp '^[0-9a-f]{64}$'),
  CONSTRAINT `chk_phone_change_attempts` CHECK (`attempt_count` <= 5),
  CONSTRAINT `chk_phone_change_expiry` CHECK (`expires_at` > `created_at`),
  CONSTRAINT `chk_phone_change_delivery` CHECK (`delivery_status` = 'PENDING' and `sent_at` is null or `delivery_status` = 'SENT' and `sent_at` is not null and `sent_at` >= `created_at` or `delivery_status` = 'FAILED' and `sent_at` is null and `invalidated_at` is not null),
  CONSTRAINT `chk_phone_change_terminal_state` CHECK ((`verified_at` is null or `invalidated_at` is null and `delivery_status` = 'SENT' and `verified_at` >= `sent_at` and `verified_at` <= `expires_at` and `attempt_count` < 5) and (`invalidated_at` is null or `invalidated_at` >= `created_at`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SET @profile_ddl = IF(EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='recycler_phone_change_challenges' AND COLUMN_NAME='active_proposed_mobile_number'), 'DO 0', 'ALTER TABLE recycler_phone_change_challenges ADD COLUMN active_proposed_mobile_number VARCHAR(20)
        GENERATED ALWAYS AS (CASE WHEN verified_at IS NULL AND invalidated_at IS NULL THEN proposed_mobile_number ELSE NULL END) STORED');
PREPARE profile_statement FROM @profile_ddl;
EXECUTE profile_statement;
DEALLOCATE PREPARE profile_statement;
SET @profile_ddl = IF(EXISTS (SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='recycler_phone_change_challenges' AND INDEX_NAME='uq_phone_change_active_target'), 'DO 0', 'ALTER TABLE recycler_phone_change_challenges ADD UNIQUE INDEX uq_phone_change_active_target (active_proposed_mobile_number)');
PREPARE profile_statement FROM @profile_ddl;
EXECUTE profile_statement;
DEALLOCATE PREPARE profile_statement;

-- MariaDB 10.11 rejects cascading updates of stored generated-key inputs.
-- Tighten only the existing FK's update action, preserving rows and delete behavior.
SET @reservation_fk_ddl = IF(
    EXISTS (SELECT 1 FROM information_schema.REFERENTIAL_CONSTRAINTS
        WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='recycler_phone_change_challenges'
          AND CONSTRAINT_NAME='fk_phone_change_recycler' AND UPDATE_RULE='CASCADE'),
    'ALTER TABLE `recycler_phone_change_challenges` DROP FOREIGN KEY `fk_phone_change_recycler`, ADD CONSTRAINT `fk_phone_change_recycler_restrict` FOREIGN KEY (`recycler_user_id`) REFERENCES `authorized_recyclers` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT',
    'DO 0'
);
PREPARE reservation_fk_statement FROM @reservation_fk_ddl;
EXECUTE reservation_fk_statement;
DEALLOCATE PREPARE reservation_fk_statement;
