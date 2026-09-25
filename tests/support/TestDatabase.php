<?php
declare(strict_types=1);

final class TestDatabase
{
    /** Load the application schema into the already-selected disposable database. */
    public static function loadSchema(PDO $db): void
    {
        $schema = file_get_contents(APP_ROOT . '/database/schema.sql');
        $schema = preg_replace('/CREATE DATABASE IF NOT EXISTS `ecolot_lk`.*?;/s', '', $schema);
        $schema = str_replace('USE `ecolot_lk`;', '', $schema);
        [$tables, $triggers] = explode('DELIMITER $$', $schema, 2);
        self::executeStatements($db, $tables, ';');
        self::executeStatements($db, str_replace('DELIMITER ;', '', $triggers), '$$');
    }

    /** Common isolated officer/public fixtures. Returns the campaign month. */
    public static function seedScheduleActors(PDO $db): string
    {
        $db->exec("INSERT INTO users (user_id, full_name, mobile_number, password_hash, role) VALUES
            (1, '<script>Officer</script>', '0771234567', 'unused', 'MUNICIPAL_OFFICER'),
            (2, 'Public', '0771234568', 'unused', 'PUBLIC_USER'),
            (3, 'Collector', '0771234569', 'unused', 'COLLECTOR')");
        $db->exec('INSERT INTO municipal_officers VALUES (1)');
        $db->exec('INSERT INTO collectors VALUES (3)');
        $db->exec("INSERT INTO postal_code_areas VALUES
            (1, '00100', 'Area One', 'ACTIVE'),
            (2, '00200', 'Area Two', 'ACTIVE'),
            (3, '00300', 'Inactive', 'INACTIVE')");
        $db->exec("INSERT INTO public_profiles VALUES (2, 1, 'Test address')");
        $month = (new DateTimeImmutable('first day of next month', new DateTimeZone('Asia/Colombo')))->format('Y-m');
        $statement = $db->prepare("INSERT INTO monthly_campaigns
            (campaign_id, created_by_officer_user_id, campaign_name, campaign_month, campaign_status)
            VALUES (1, 1, '<b>Campaign</b>', ?, 'OPEN'), (2, 1, 'Closed', '2000-01-01', 'CLOSED')");
        $statement->execute([$month . '-01']);
        return $month;
    }

    private static function executeStatements(PDO $db, string $sql, string $delimiter): void
    {
        foreach (explode($delimiter, $sql) as $statement) {
            if (trim($statement) !== '') {
                $db->exec($statement);
            }
        }
    }
}
