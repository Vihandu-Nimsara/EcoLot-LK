USE `ecolot_lk`;

INSERT INTO `postal_code_areas` (`postal_code`, `area_name`, `area_status`)
VALUES
    ('11100', 'Wellawatte', 'ACTIVE'),
    ('10800', 'Rajagiriya', 'ACTIVE'),
    ('10600', 'Narahenpita', 'ACTIVE'),
    ('10500', 'Kollupitiya', 'ACTIVE'),
    ('00800', 'Borella', 'ACTIVE'),
    ('00700', 'Cinnamon Gardens', 'ACTIVE')
ON DUPLICATE KEY UPDATE
    `area_name` = VALUES(`area_name`),
    `area_status` = VALUES(`area_status`);
