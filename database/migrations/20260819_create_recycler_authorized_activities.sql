USE `ecolot_lk`;

CREATE TABLE IF NOT EXISTS `recycler_authorized_activities` (
    `activity_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `recycler_user_id` BIGINT UNSIGNED NOT NULL,
    `activity_type` ENUM(
        'COLLECTION',
        'TRANSPORTATION',
        'STORAGE',
        'RECOVERY',
        'RECYCLING',
        'DISPOSAL'
    ) NOT NULL,
    `activity_status` ENUM('PENDING', 'APPROVED', 'SUSPENDED') NOT NULL DEFAULT 'PENDING',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`activity_id`),
    UNIQUE KEY `uq_recycler_authorized_activity` (`recycler_user_id`, `activity_type`),
    KEY `idx_recycler_authorized_activities_status` (`activity_status`),
    CONSTRAINT `fk_recycler_authorized_activities_recycler`
        FOREIGN KEY (`recycler_user_id`) REFERENCES `authorized_recyclers` (`user_id`)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;
