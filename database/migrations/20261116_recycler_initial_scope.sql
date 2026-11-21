-- Initial Admin review only. No existing rows are changed or granted scope.
CREATE TABLE IF NOT EXISTS recycler_license_scopes (
    scope_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    license_id BIGINT UNSIGNED NOT NULL,
    category_id BIGINT UNSIGNED NOT NULL,
    activity_type ENUM('COLLECTION','TRANSPORTATION','STORAGE','RECOVERY','RECYCLING','DISPOSAL') NOT NULL,
    covers_high_risk BOOLEAN NOT NULL DEFAULT FALSE,
    authorization_basis ENUM('EPL','SWML','EPL_AND_SWML','OTHER') NOT NULL,
    waste_description VARCHAR(500) NOT NULL,
    waste_code VARCHAR(120) NULL,
    evidence_reference VARCHAR(500) NOT NULL,
    licence_conditions VARCHAR(1000) NOT NULL,
    license_number VARCHAR(100) NOT NULL,
    expiry_date DATE NOT NULL,
    document_path VARCHAR(500) NOT NULL,
    document_sha256 CHAR(64) NOT NULL,
    scope_status ENUM('PENDING','VERIFIED','REJECTED') NOT NULL DEFAULT 'PENDING',
    proposed_by_admin_user_id BIGINT UNSIGNED NOT NULL,
    proposed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_by_admin_user_id BIGINT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    review_note VARCHAR(500) NULL,
    active_scope TINYINT GENERATED ALWAYS AS (CASE WHEN scope_status IN ('PENDING','VERIFIED') THEN 1 ELSE NULL END) STORED,
    UNIQUE KEY uq_scope_document (license_id, category_id, activity_type, covers_high_risk, document_sha256, active_scope),
    KEY idx_scope_lookup (license_id, category_id, activity_type, scope_status),
    CONSTRAINT fk_scope_license FOREIGN KEY (license_id) REFERENCES recycler_licenses(license_id),
    CONSTRAINT fk_scope_category FOREIGN KEY (category_id) REFERENCES waste_categories(category_id),
    CONSTRAINT fk_scope_proposer FOREIGN KEY (proposed_by_admin_user_id) REFERENCES administrators(user_id),
    CONSTRAINT fk_scope_reviewer FOREIGN KEY (reviewed_by_admin_user_id) REFERENCES administrators(user_id),
    CONSTRAINT chk_scope_decision CHECK (
        (scope_status='PENDING' AND reviewed_by_admin_user_id IS NULL AND reviewed_at IS NULL AND review_note IS NULL)
        OR (scope_status IN ('VERIFIED','REJECTED') AND reviewed_by_admin_user_id IS NOT NULL AND reviewed_at IS NOT NULL AND review_note IS NOT NULL AND CHAR_LENGTH(TRIM(review_note))>0)
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Also support the earlier Phase 1 table definition without changing decision rows.
-- Existing duplicate active scopes must be reconciled explicitly, never delete history here.
SET @profile_ddl = IF(EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='recycler_license_scopes' AND COLUMN_NAME='active_scope'), 'DO 0', 'ALTER TABLE recycler_license_scopes ADD COLUMN active_scope TINYINT GENERATED ALWAYS AS (CASE WHEN scope_status IN (''PENDING'',''VERIFIED'') THEN 1 ELSE NULL END) STORED');
PREPARE profile_statement FROM @profile_ddl;
EXECUTE profile_statement;
DEALLOCATE PREPARE profile_statement;
SET @profile_ddl = IF(EXISTS (SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='recycler_license_scopes' AND INDEX_NAME='uq_scope_document'), 'DO 0', 'ALTER TABLE recycler_license_scopes ADD UNIQUE INDEX uq_scope_document (license_id, category_id, activity_type, covers_high_risk, document_sha256, active_scope)');
PREPARE profile_statement FROM @profile_ddl;
EXECUTE profile_statement;
DEALLOCATE PREPARE profile_statement;
