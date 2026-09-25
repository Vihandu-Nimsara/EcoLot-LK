-- Apply once to the selected EcoLot database (MariaDB 10.4+).
-- Existing submissions retain their status, timestamps and verification history.
ALTER TABLE schedule_collections
    DROP CONSTRAINT chk_schedule_collections_verification_audit,
    MODIFY verification_status ENUM('DRAFT','PENDING','VERIFIED','REJECTED') NOT NULL DEFAULT 'PENDING',
    MODIFY submitted_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
    ADD CONSTRAINT chk_schedule_collections_verification_audit CHECK (
        (verification_status = 'DRAFT' AND submitted_at IS NULL AND verified_by_officer_user_id IS NULL AND verified_at IS NULL)
        OR (verification_status = 'PENDING' AND submitted_at IS NOT NULL AND verified_by_officer_user_id IS NULL AND verified_at IS NULL)
        OR (verification_status IN ('VERIFIED','REJECTED') AND verified_by_officer_user_id IS NOT NULL AND verified_at IS NOT NULL)
    );
