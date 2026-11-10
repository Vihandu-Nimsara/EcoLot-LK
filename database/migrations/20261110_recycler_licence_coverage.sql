-- NEW migration only. Do not rerun the already-applied capability migrations.
-- Legacy coverage and lot requirements intentionally start empty (fail closed).
ALTER TABLE recycler_licenses
    ADD COLUMN IF NOT EXISTS document_version BIGINT UNSIGNED NOT NULL DEFAULT 1,
    ADD UNIQUE INDEX IF NOT EXISTS uq_license_owner_identity (license_id, recycler_user_id);

CREATE TABLE IF NOT EXISTS recycler_license_evidence (
    evidence_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    license_id BIGINT UNSIGNED NOT NULL,
    recycler_user_id BIGINT UNSIGNED NOT NULL,
    document_version BIGINT UNSIGNED NOT NULL,
    license_number VARCHAR(100) NOT NULL,
    expiry_date DATE NOT NULL,
    document_path VARCHAR(500) NOT NULL,
    document_sha256 CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    captured_by_admin_user_id BIGINT UNSIGNED NOT NULL,
    captured_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (evidence_id),
    UNIQUE KEY uq_license_evidence_version (license_id, document_version),
    KEY idx_license_evidence_owner (recycler_user_id, evidence_id),
    FOREIGN KEY (license_id, recycler_user_id) REFERENCES recycler_licenses (license_id, recycler_user_id) ON DELETE RESTRICT ON UPDATE RESTRICT,
    FOREIGN KEY (captured_by_admin_user_id) REFERENCES administrators (user_id) ON DELETE RESTRICT,
    CHECK (document_version > 0 AND document_sha256 REGEXP '^[0-9a-f]{64}$')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS recycler_license_coverage (
    coverage_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    evidence_id BIGINT UNSIGNED NOT NULL,
    category_id BIGINT UNSIGNED NOT NULL,
    activity_type ENUM('COLLECTION','TRANSPORTATION','STORAGE','RECOVERY','RECYCLING','DISPOSAL') NOT NULL,
    covers_high_risk BOOLEAN NOT NULL DEFAULT FALSE,
    authorization_basis ENUM('EPL','SWML','EPL_AND_SWML','OTHER') NOT NULL,
    waste_description VARCHAR(500) NOT NULL,
    document_waste_code VARCHAR(120) NULL,
    evidence_reference VARCHAR(500) NOT NULL,
    licence_conditions VARCHAR(1000) NOT NULL,
    verification_status ENUM('PENDING','VERIFIED','REJECTED','UNCERTAIN','REVOKED') NOT NULL DEFAULT 'PENDING',
    proposed_by_admin_user_id BIGINT UNSIGNED NOT NULL,
    proposed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_by_admin_user_id BIGINT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    review_note VARCHAR(500) NULL,
    superseded_at DATETIME NULL,
    pending_slot TINYINT GENERATED ALWAYS AS (CASE WHEN verification_status = 'PENDING' THEN 1 ELSE NULL END) PERSISTENT,
    verified_slot TINYINT GENERATED ALWAYS AS (CASE WHEN verification_status = 'VERIFIED' AND superseded_at IS NULL THEN 1 ELSE NULL END) PERSISTENT,
    PRIMARY KEY (coverage_id),
    UNIQUE KEY uq_pending_license_scope (evidence_id, category_id, activity_type, pending_slot),
    UNIQUE KEY uq_verified_license_scope (evidence_id, category_id, activity_type, verified_slot),
    KEY idx_current_license_scope (evidence_id, category_id, activity_type, verification_status),
    FOREIGN KEY (evidence_id) REFERENCES recycler_license_evidence (evidence_id) ON DELETE RESTRICT,
    FOREIGN KEY (category_id) REFERENCES waste_categories (category_id) ON DELETE RESTRICT,
    FOREIGN KEY (proposed_by_admin_user_id) REFERENCES administrators (user_id) ON DELETE RESTRICT,
    FOREIGN KEY (reviewed_by_admin_user_id) REFERENCES administrators (user_id) ON DELETE RESTRICT,
    CHECK (covers_high_risk IN (0,1)),
    CHECK (covers_high_risk = 0 OR authorization_basis <> 'EPL'),
    CHECK (CHAR_LENGTH(TRIM(waste_description)) > 0 AND CHAR_LENGTH(TRIM(evidence_reference)) > 0 AND CHAR_LENGTH(TRIM(licence_conditions)) > 0),
    CHECK ((verification_status = 'PENDING' AND reviewed_by_admin_user_id IS NULL AND reviewed_at IS NULL AND review_note IS NULL)
        OR (verification_status <> 'PENDING' AND reviewed_by_admin_user_id IS NOT NULL AND reviewed_at IS NOT NULL AND CHAR_LENGTH(TRIM(COALESCE(review_note,''))) > 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS recycler_license_coverage_reviews (
    review_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    coverage_id BIGINT UNSIGNED NOT NULL,
    decision ENUM('VERIFIED','REJECTED','UNCERTAIN','REVOKED','SUPERSEDED') NOT NULL,
    admin_user_id BIGINT UNSIGNED NOT NULL,
    review_note VARCHAR(500) NOT NULL,
    reviewed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (review_id),
    KEY idx_coverage_audit (coverage_id, review_id),
    FOREIGN KEY (coverage_id) REFERENCES recycler_license_coverage (coverage_id) ON DELETE RESTRICT,
    FOREIGN KEY (admin_user_id) REFERENCES administrators (user_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS e_lot_authorization_requirements (
    e_lot_id BIGINT UNSIGNED NOT NULL,
    requirement_version BIGINT UNSIGNED NOT NULL DEFAULT 1,
    reviewed_by_admin_user_id BIGINT UNSIGNED NOT NULL,
    reviewed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    review_note VARCHAR(500) NOT NULL,
    PRIMARY KEY (e_lot_id),
    FOREIGN KEY (e_lot_id) REFERENCES e_lots (e_lot_id) ON DELETE RESTRICT,
    FOREIGN KEY (reviewed_by_admin_user_id) REFERENCES administrators (user_id) ON DELETE RESTRICT,
    CHECK (CHAR_LENGTH(TRIM(review_note)) > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS e_lot_required_activities (
    e_lot_id BIGINT UNSIGNED NOT NULL,
    activity_type ENUM('COLLECTION','TRANSPORTATION','STORAGE','RECOVERY','RECYCLING','DISPOSAL') NOT NULL,
    PRIMARY KEY (e_lot_id, activity_type),
    FOREIGN KEY (e_lot_id) REFERENCES e_lot_authorization_requirements (e_lot_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS e_lot_authorization_reviews (
    review_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    e_lot_id BIGINT UNSIGNED NOT NULL,
    requirement_version BIGINT UNSIGNED NOT NULL,
    required_activities_json TEXT NOT NULL,
    admin_user_id BIGINT UNSIGNED NOT NULL,
    review_note VARCHAR(500) NOT NULL,
    reviewed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (review_id),
    UNIQUE KEY uq_lot_requirement_audit (e_lot_id, requirement_version),
    FOREIGN KEY (e_lot_id) REFERENCES e_lots (e_lot_id) ON DELETE RESTRICT,
    FOREIGN KEY (admin_user_id) REFERENCES administrators (user_id) ON DELETE RESTRICT,
    CHECK (JSON_VALID(required_activities_json))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELIMITER $$
CREATE OR REPLACE TRIGGER trg_recycler_license_document_version_bu BEFORE UPDATE ON recycler_licenses FOR EACH ROW
BEGIN
    IF NOT (NEW.document_path <=> OLD.document_path) OR NEW.license_number <> OLD.license_number
        OR NEW.expiry_date <> OLD.expiry_date OR NEW.license_status <> OLD.license_status THEN
        SET NEW.document_version = OLD.document_version + 1;
    ELSE
        SET NEW.document_version = OLD.document_version;
    END IF;
END$$

CREATE OR REPLACE TRIGGER trg_recycler_bid_coverage_bi BEFORE INSERT ON recycler_bids FOR EACH ROW
BEGIN
    -- Application transactions serialize scope review and bids on the Recycler owner row.
    IF NOT EXISTS (SELECT 1 FROM e_lots auth_lot WHERE auth_lot.e_lot_id=NEW.e_lot_id AND (EXISTS (SELECT 1 FROM users auth_user JOIN authorized_recyclers auth_recycler ON auth_recycler.user_id=auth_user.user_id
                WHERE auth_user.user_id=NEW.recycler_user_id AND auth_user.role='RECYCLER' AND auth_user.account_status='ACTIVE' AND auth_recycler.verification_status='VERIFIED')
            AND EXISTS (SELECT 1 FROM recycler_capabilities auth_cap WHERE auth_cap.recycler_user_id=NEW.recycler_user_id AND auth_cap.category_id=auth_lot.category_id
                AND auth_cap.capability_status='APPROVED' AND (NOT (EXISTS (SELECT 1 FROM e_lot_items auth_item
            JOIN collection_record_items auth_record ON auth_record.record_item_id=auth_item.record_item_id
            JOIN request_items auth_request ON auth_request.request_item_id=auth_record.request_item_id
            WHERE auth_item.e_lot_id=auth_lot.e_lot_id AND (auth_request.applied_risk_level='HIGH' OR auth_record.actual_risk_level='HIGH'))) OR auth_cap.can_handle_high_risk=1))
            AND EXISTS (SELECT 1 FROM e_lot_authorization_requirements WHERE e_lot_id=auth_lot.e_lot_id)
            AND EXISTS (SELECT 1 FROM e_lot_required_activities WHERE e_lot_id=auth_lot.e_lot_id)
            AND NOT EXISTS (SELECT 1 FROM e_lot_required_activities required_activity WHERE required_activity.e_lot_id=auth_lot.e_lot_id AND (
                NOT EXISTS (SELECT 1 FROM recycler_authorized_activities approved_activity WHERE approved_activity.recycler_user_id=NEW.recycler_user_id
                    AND approved_activity.activity_type=required_activity.activity_type AND approved_activity.activity_status='APPROVED')
                OR NOT (EXISTS (SELECT 1 FROM recycler_license_coverage scope
            JOIN recycler_license_evidence evidence ON evidence.evidence_id=scope.evidence_id
            JOIN recycler_licenses source ON source.license_id=evidence.license_id AND source.recycler_user_id=evidence.recycler_user_id
            JOIN waste_categories scope_category ON scope_category.category_id=scope.category_id
            WHERE evidence.recycler_user_id=NEW.recycler_user_id AND scope.category_id=auth_lot.category_id AND scope.activity_type=required_activity.activity_type
                AND scope_category.category_status='ACTIVE' AND scope.verification_status='VERIFIED' AND scope.superseded_at IS NULL
                AND ((EXISTS (SELECT 1 FROM e_lot_items auth_item
            JOIN collection_record_items auth_record ON auth_record.record_item_id=auth_item.record_item_id
            JOIN request_items auth_request ON auth_request.request_item_id=auth_record.request_item_id
            WHERE auth_item.e_lot_id=auth_lot.e_lot_id AND (auth_request.applied_risk_level='HIGH' OR auth_record.actual_risk_level='HIGH')))=0 OR scope.covers_high_risk=1)
                AND source.license_status='VALID' AND source.expiry_date>=CURRENT_DATE
                AND source.document_version=evidence.document_version AND source.document_path=evidence.document_path
                AND source.license_number=evidence.license_number AND source.expiry_date=evidence.expiry_date
                AND source.license_id=(SELECT current_source.license_id FROM recycler_licenses current_source
                    WHERE current_source.recycler_user_id=NEW.recycler_user_id AND current_source.license_status='VALID'
                    ORDER BY current_source.submitted_at DESC,current_source.license_id DESC LIMIT 1))))))) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Current verified licence scope and required activities are required';
    END IF;
END$$

CREATE OR REPLACE TRIGGER trg_recycler_bid_coverage_bu BEFORE UPDATE ON recycler_bids FOR EACH ROW
BEGIN
    IF NEW.bid_amount <> OLD.bid_amount THEN
        IF NOT EXISTS (SELECT 1 FROM e_lots auth_lot WHERE auth_lot.e_lot_id=NEW.e_lot_id AND (EXISTS (SELECT 1 FROM users auth_user JOIN authorized_recyclers auth_recycler ON auth_recycler.user_id=auth_user.user_id
                WHERE auth_user.user_id=NEW.recycler_user_id AND auth_user.role='RECYCLER' AND auth_user.account_status='ACTIVE' AND auth_recycler.verification_status='VERIFIED')
            AND EXISTS (SELECT 1 FROM recycler_capabilities auth_cap WHERE auth_cap.recycler_user_id=NEW.recycler_user_id AND auth_cap.category_id=auth_lot.category_id
                AND auth_cap.capability_status='APPROVED' AND (NOT (EXISTS (SELECT 1 FROM e_lot_items auth_item
            JOIN collection_record_items auth_record ON auth_record.record_item_id=auth_item.record_item_id
            JOIN request_items auth_request ON auth_request.request_item_id=auth_record.request_item_id
            WHERE auth_item.e_lot_id=auth_lot.e_lot_id AND (auth_request.applied_risk_level='HIGH' OR auth_record.actual_risk_level='HIGH'))) OR auth_cap.can_handle_high_risk=1))
            AND EXISTS (SELECT 1 FROM e_lot_authorization_requirements WHERE e_lot_id=auth_lot.e_lot_id)
            AND EXISTS (SELECT 1 FROM e_lot_required_activities WHERE e_lot_id=auth_lot.e_lot_id)
            AND NOT EXISTS (SELECT 1 FROM e_lot_required_activities required_activity WHERE required_activity.e_lot_id=auth_lot.e_lot_id AND (
                NOT EXISTS (SELECT 1 FROM recycler_authorized_activities approved_activity WHERE approved_activity.recycler_user_id=NEW.recycler_user_id
                    AND approved_activity.activity_type=required_activity.activity_type AND approved_activity.activity_status='APPROVED')
                OR NOT (EXISTS (SELECT 1 FROM recycler_license_coverage scope
            JOIN recycler_license_evidence evidence ON evidence.evidence_id=scope.evidence_id
            JOIN recycler_licenses source ON source.license_id=evidence.license_id AND source.recycler_user_id=evidence.recycler_user_id
            JOIN waste_categories scope_category ON scope_category.category_id=scope.category_id
            WHERE evidence.recycler_user_id=NEW.recycler_user_id AND scope.category_id=auth_lot.category_id AND scope.activity_type=required_activity.activity_type
                AND scope_category.category_status='ACTIVE' AND scope.verification_status='VERIFIED' AND scope.superseded_at IS NULL
                AND ((EXISTS (SELECT 1 FROM e_lot_items auth_item
            JOIN collection_record_items auth_record ON auth_record.record_item_id=auth_item.record_item_id
            JOIN request_items auth_request ON auth_request.request_item_id=auth_record.request_item_id
            WHERE auth_item.e_lot_id=auth_lot.e_lot_id AND (auth_request.applied_risk_level='HIGH' OR auth_record.actual_risk_level='HIGH')))=0 OR scope.covers_high_risk=1)
                AND source.license_status='VALID' AND source.expiry_date>=CURRENT_DATE
                AND source.document_version=evidence.document_version AND source.document_path=evidence.document_path
                AND source.license_number=evidence.license_number AND source.expiry_date=evidence.expiry_date
                AND source.license_id=(SELECT current_source.license_id FROM recycler_licenses current_source
                    WHERE current_source.recycler_user_id=NEW.recycler_user_id AND current_source.license_status='VALID'
                    ORDER BY current_source.submitted_at DESC,current_source.license_id DESC LIMIT 1))))))) THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Current verified licence scope is required to revise this bid';
        END IF;
    END IF;
END$$
DELIMITER ;
