-- Add Administrator review metadata to recycler licenses.
-- Apply once to an existing EcoLot-LK database.

ALTER TABLE recycler_licenses
    ADD COLUMN reviewed_by_admin_user_id
        BIGINT UNSIGNED NULL AFTER submitted_at,

    ADD COLUMN reviewed_at
        DATETIME NULL AFTER reviewed_by_admin_user_id,

    ADD COLUMN review_note
        VARCHAR(500) NULL AFTER reviewed_at,

    ADD INDEX idx_recycler_licenses_reviewer
        (reviewed_by_admin_user_id),

    ADD CONSTRAINT fk_recycler_licenses_reviewed_by_admin
        FOREIGN KEY (reviewed_by_admin_user_id)
        REFERENCES administrators(user_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT;