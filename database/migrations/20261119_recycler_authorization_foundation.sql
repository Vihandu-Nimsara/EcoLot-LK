-- Apply after 20261116_recycler_initial_scope.sql. No legacy rows are selected or granted scope.
-- Pause review writes and compare existing definitions before applying. DDL auto-commits on MySQL and MariaDB.
SET @profile_ddl = IF(EXISTS (SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='recycler_licenses' AND INDEX_NAME='uq_license_owner_identity'), 'DO 0', 'ALTER TABLE recycler_licenses ADD UNIQUE INDEX uq_license_owner_identity (license_id, recycler_user_id)');
PREPARE profile_statement FROM @profile_ddl;
EXECUTE profile_statement;
DEALLOCATE PREPARE profile_statement;
SET @profile_ddl = IF(EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='authorized_recyclers' AND COLUMN_NAME='current_license_id'), 'DO 0', 'ALTER TABLE authorized_recyclers ADD COLUMN current_license_id BIGINT UNSIGNED NULL');
PREPARE profile_statement FROM @profile_ddl;
EXECUTE profile_statement;
DEALLOCATE PREPARE profile_statement;
SET @profile_ddl = IF(EXISTS (SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='authorized_recyclers' AND INDEX_NAME='idx_recycler_current_license'), 'DO 0', 'ALTER TABLE authorized_recyclers ADD INDEX idx_recycler_current_license (current_license_id, user_id)');
PREPARE profile_statement FROM @profile_ddl;
EXECUTE profile_statement;
DEALLOCATE PREPARE profile_statement;
SET @foundation_ddl = IF(
    EXISTS (SELECT 1 FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='authorized_recyclers' AND CONSTRAINT_NAME='fk_recycler_current_license'),
    'DO 0',
    'ALTER TABLE authorized_recyclers ADD CONSTRAINT fk_recycler_current_license FOREIGN KEY (current_license_id, user_id) REFERENCES recycler_licenses (license_id, recycler_user_id) ON DELETE RESTRICT ON UPDATE RESTRICT'
);
PREPARE foundation_statement FROM @foundation_ddl;
EXECUTE foundation_statement;
DEALLOCATE PREPARE foundation_statement;
SET @foundation_ddl = IF(
    EXISTS (SELECT 1 FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='recycler_license_scopes' AND CONSTRAINT_NAME='chk_scope_evidence'),
    'DO 0',
    'ALTER TABLE recycler_license_scopes ADD CONSTRAINT chk_scope_evidence CHECK (covers_high_risk IN (0,1) AND (covers_high_risk=0 OR authorization_basis<>''EPL'') AND document_sha256 COLLATE utf8mb4_bin REGEXP ''^[0-9a-f]{64}$'' AND CHAR_LENGTH(TRIM(waste_description))>0 AND CHAR_LENGTH(TRIM(evidence_reference))>0 AND CHAR_LENGTH(TRIM(licence_conditions))>0)'
);
PREPARE foundation_statement FROM @foundation_ddl;
EXECUTE foundation_statement;
DEALLOCATE PREPARE foundation_statement;
