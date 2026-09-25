-- Select the EcoLot database before importing. Safe to rerun.
-- Expand first: this index also supports the campaign foreign key.
SET @schedule_add_date_index = IF(
    EXISTS(SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'area_collection_schedules'
          AND INDEX_NAME = 'uq_schedules_campaign_area_date'),
    'DO 0',
    'ALTER TABLE area_collection_schedules ADD UNIQUE KEY uq_schedules_campaign_area_date (campaign_id, postal_area_id, collection_date)'
);
PREPARE schedule_index_statement FROM @schedule_add_date_index;
EXECUTE schedule_index_statement;
DEALLOCATE PREPARE schedule_index_statement;

-- Remove the obsolete one-schedule-per-area restriction. No rows are changed.
SET @schedule_drop_area_index = IF(
    EXISTS(SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'area_collection_schedules'
          AND INDEX_NAME = 'uq_schedules_campaign_area'),
    'ALTER TABLE area_collection_schedules DROP INDEX uq_schedules_campaign_area',
    'DO 0'
);
PREPARE schedule_index_statement FROM @schedule_drop_area_index;
EXECUTE schedule_index_statement;
DEALLOCATE PREPARE schedule_index_statement;
