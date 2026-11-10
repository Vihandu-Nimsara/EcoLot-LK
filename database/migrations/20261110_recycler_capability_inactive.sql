-- Apply BEFORE 20261110_recycler_capability_change_requests.sql.
-- Append the value to preserve the numeric ordering of existing ENUM values.
ALTER TABLE `recycler_capabilities`
    MODIFY COLUMN `capability_status`
        ENUM('PENDING', 'APPROVED', 'REJECTED', 'SUSPENDED', 'INACTIVE')
        NOT NULL DEFAULT 'PENDING';

-- Also support installations where the manually authored request table exists.
SET @ecolot_capability_history_ddl = (
    SELECT IF(COUNT(*) > 0,
        'ALTER TABLE `recycler_capability_change_requests` MODIFY COLUMN `previous_capability_status` ENUM(''PENDING'', ''APPROVED'', ''REJECTED'', ''SUSPENDED'', ''INACTIVE'') NULL',
        'DO 0')
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'recycler_capability_change_requests'
);
PREPARE ecolot_capability_history_statement FROM @ecolot_capability_history_ddl;
EXECUTE ecolot_capability_history_statement;
DEALLOCATE PREPARE ecolot_capability_history_statement;

-- Retrofit the snapshot guard if the original manually authored table exists.
SET @ecolot_capability_snapshot_ddl = IF(
    EXISTS (SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'recycler_capability_change_requests')
    AND NOT EXISTS (SELECT 1 FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE()
        AND TABLE_NAME = 'recycler_capability_change_requests' AND CONSTRAINT_NAME = 'chk_capability_request_snapshot'),
    'ALTER TABLE `recycler_capability_change_requests` ADD CONSTRAINT `chk_capability_request_snapshot` CHECK (
        (`request_type` = ''ADD'' AND `previous_capability_status` IS NULL AND `previous_can_handle_high_risk` IS NULL)
        OR (`request_type` = ''UPDATE'' AND `previous_capability_status` IS NOT NULL
            AND `previous_capability_status` IN (''APPROVED'', ''INACTIVE'') AND `previous_can_handle_high_risk` IS NOT NULL
            AND (`previous_capability_status` = ''INACTIVE'' OR `previous_can_handle_high_risk` <> `requested_can_handle_high_risk`))
        OR (`request_type` = ''DEACTIVATE'' AND `previous_capability_status` IS NOT NULL
            AND `previous_capability_status` = ''APPROVED'' AND `previous_can_handle_high_risk` IS NOT NULL))',
    'DO 0'
);
PREPARE ecolot_capability_snapshot_statement FROM @ecolot_capability_snapshot_ddl;
EXECUTE ecolot_capability_snapshot_statement;
DEALLOCATE PREPARE ecolot_capability_snapshot_statement;
