<?php
declare(strict_types=1);
define('APP_ROOT', dirname(__DIR__));
spl_autoload_register(static function (string $class): void {
    foreach (['Core', 'Models'] as $directory) {
        $path = APP_ROOT . '/app/' . $directory . '/' . $class . '.php';
        if (is_file($path)) { require $path; return; }
    }
});
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
    $schema = file_get_contents(APP_ROOT . '/database/schema.sql');
    $schema = preg_replace('/CREATE DATABASE IF NOT EXISTS `ecolot_lk`.*?;/s', '', $schema);
    $schema = str_replace('USE `ecolot_lk`;', '', $schema);
    [$tables, $triggers] = explode('DELIMITER $$', $schema, 2);
    foreach (explode(';', $tables) as $sql) if (trim($sql) !== '') $db->exec($sql);
    foreach (explode('$$', str_replace('DELIMITER ;', '', $triggers)) as $sql) if (trim($sql) !== '') $db->exec($sql);
    $seed = preg_replace('/^--.*$/m', '', str_replace('USE `ecolot_lk`;', '', file_get_contents(APP_ROOT . '/database/seed.sql')));
    foreach (explode(';', $seed) as $sql) if (trim($sql) !== '') $db->exec($sql);
    $db->prepare('UPDATE users SET password_hash = ?, mobile_verified_at = NOW()')->execute([password_hash('BrowserTest123!', PASSWORD_DEFAULT)]);
    $month = (new DateTimeImmutable('first day of next month', new DateTimeZone('Asia/Colombo')))->format('Y-m');
    $officer = (int) $db->query('SELECT user_id FROM municipal_officers LIMIT 1')->fetchColumn();
    $db->prepare("INSERT INTO monthly_campaigns (created_by_officer_user_id, campaign_name, campaign_month, campaign_status) VALUES (?, 'Browser Test Campaign', ?, 'OPEN')")->execute([$officer, "$month-01"]);
    echo json_encode(['database' => $name, 'month' => $month]);
} catch (Throwable $error) {
    $db->exec("DROP DATABASE `$name`");
    throw $error;
}
