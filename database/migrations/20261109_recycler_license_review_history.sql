
-- Licence replacement status
-- Existing ENUM values are preserved in their original order.

ALTER TABLE recycler_licenses
MODIFY COLUMN license_status ENUM(
    'PENDING',
    'VALID',
    'REJECTED',
    'EXPIRED',
    'REVOKED',
    'SUPERSEDED'
) NOT NULL DEFAULT 'PENDING';


-- Historical snapshots for same-number licence renewals.
-- Previous PDF files must remain in private storage.

CREATE TABLE recycler_license_revisions (
    revision_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    license_id BIGINT UNSIGNED NOT NULL,
    update_request_id BIGINT UNSIGNED NOT NULL,

    previous_license_number VARCHAR(100) NOT NULL,
    previous_expiry_date DATE NOT NULL,
    previous_document_path VARCHAR(500) DEFAULT NULL,

    previous_license_status ENUM(
        'PENDING',
        'VALID',
        'REJECTED',
        'EXPIRED',
        'REVOKED',
        'SUPERSEDED'
    ) NOT NULL,

    previous_submitted_at DATETIME NOT NULL,
    previous_reviewed_by_admin_user_id BIGINT UNSIGNED DEFAULT NULL,
    previous_reviewed_at DATETIME DEFAULT NULL,
    previous_review_note VARCHAR(500) DEFAULT NULL,

    archived_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    archived_by_admin_user_id BIGINT UNSIGNED NOT NULL,

    PRIMARY KEY (revision_id),

    UNIQUE KEY uq_license_revision_request (update_request_id),

    KEY idx_license_revision_history (
        license_id,
        archived_at
    ),

    CONSTRAINT fk_license_revision_license
        FOREIGN KEY (license_id)
        REFERENCES recycler_licenses (license_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_license_revision_admin
        FOREIGN KEY (archived_by_admin_user_id)
        REFERENCES administrators (user_id)
        ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
