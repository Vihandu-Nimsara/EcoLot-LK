<?php
declare(strict_types=1);
require_once __DIR__ . '/support/bootstrap.php';
$db = Database::connection();
if (($argv[1] ?? '') === 'drop') {
    $name = $argv[2] ?? '';
    if (!preg_match('/^ecolot_browser_test_[a-f0-9]{12}$/D', $name)) throw new RuntimeException('Invalid test database');
    $db->exec("DROP DATABASE `$name`");
    exit;
}
$name = 'ecolot_browser_test_' . bin2hex(random_bytes(6));
$db->exec("CREATE DATABASE `$name`");
try {
    $db->exec("USE `$name`");
    TestDatabase::loadSchema($db);
    if (($argv[1] ?? '') === 'officer') {
        $month = TestDatabase::seedScheduleActors($db);
        $db->exec("UPDATE users SET mobile_number = CONCAT('94', SUBSTRING(mobile_number, 2))");
        $db->prepare("UPDATE users SET account_status = 'ACTIVE', mobile_verified_at = NOW(), password_hash = ?")->execute([password_hash('BrowserTest123!', PASSWORD_DEFAULT)]);
        $db->exec("INSERT INTO waste_categories (category_name) VALUES ('Demo electronics')");
        $db->exec("INSERT INTO e_waste_items (category_id,item_name,collection_status,default_risk_level) VALUES (1,'Demo damaged monitor','REVIEW_REQUIRED','HIGH')");
        $db->exec("INSERT INTO area_collection_schedules (campaign_id,postal_area_id,created_by_officer_user_id,collection_date,request_cutoff_at,request_capacity,schedule_status) VALUES (1,1,1,'$month-15','$month-10 23:59:59',10,'OPEN')");
        $db->exec("INSERT INTO e_waste_requests (public_user_id,schedule_id,pickup_address,request_status,risk_review_status) VALUES (2,1,'Test address','PENDING_REVIEW','PENDING')");
        $db->exec("INSERT INTO request_items (request_id,waste_item_id,quantity,item_condition,applied_risk_level,condition_note) VALUES (1,1,1,'DAMAGED','HIGH','Cracked housing')");
        $db->exec("INSERT INTO vehicles (vehicle_number,vehicle_type) VALUES ('DEMO-01','Collection truck')");
        echo json_encode(['database' => $name, 'month' => $month]);
        exit;
    }
    $seed = preg_replace('/^--.*$/m', '', str_replace('USE `ecolot_lk`;', '', file_get_contents(APP_ROOT . '/database/seed.sql')));
    $db->exec($seed);
    $db->prepare('UPDATE users SET password_hash = ?, mobile_verified_at = NOW()')->execute([password_hash('BrowserTest123!', PASSWORD_DEFAULT)]);
    $month = (new DateTimeImmutable('first day of next month', new DateTimeZone('Asia/Colombo')))->format('Y-m');
    $officer = (int) $db->query('SELECT user_id FROM municipal_officers LIMIT 1')->fetchColumn();
    $db->prepare("INSERT INTO monthly_campaigns (created_by_officer_user_id, campaign_name, campaign_month, campaign_status) VALUES (?, 'Browser Test Campaign', ?, 'OPEN') ON DUPLICATE KEY UPDATE campaign_name = VALUES(campaign_name), campaign_status = 'OPEN'")->execute([$officer, "$month-01"]);
    echo json_encode(['database' => $name, 'month' => $month]);
} catch (Throwable $error) {
    $db->exec("DROP DATABASE `$name`");
    throw $error;
}
