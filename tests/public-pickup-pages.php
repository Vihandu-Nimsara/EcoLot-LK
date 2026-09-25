<?php
declare(strict_types=1);

// Run against an isolated, disposable database; never writes the application database.
// php -d session.save_path=/tmp tests/public-pickup-pages.php
// The configured DB account needs CREATE/DROP DATABASE privileges.
require_once __DIR__ . '/support/bootstrap.php';
Session::start();
Session::put('auth_user', ['id' => 2, 'role' => 'PUBLIC_USER']);
set_error_handler(static function ($severity, $message, $file, $line): never {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
$db = Database::connection();
$originalDatabase = (string) $db->query('SELECT DATABASE()')->fetchColumn();
$database = 'ecolot_schedule_test_' . bin2hex(random_bytes(6));
$db->exec("CREATE DATABASE `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
try {
    $db->exec("USE `$database`");
    TestDatabase::loadSchema($db);
    $month = TestDatabase::seedScheduleActors($db);

    $model = new AreaCollectionSchedule();
    $id = $model->create(['campaign_id' => 1, 'postal_area_id' => 1,
        'created_by_officer_user_id' => 1, 'collection_date' => "$month-10",
        'request_cutoff_at' => "$month-08 23:59:59", 'request_capacity' => 1,
        'schedule_status' => 'OPEN']);
    verify(count($model->openForPostalArea(1)) === 1, 'Open schedule missing');
    verify($model->openForPostalArea(2) === [], 'Wrong area offered');
    verify($model->isBookable($id, 1), 'Open schedule not bookable');
    verify(!$model->isBookable($id, 2), 'Wrong area bookable');
    verify(!$model->isBookable(999999, 1), 'Missing schedule bookable');
    $render = static function (): void {
        foreach (['newRequest' => 'New Pickup Request', 'myRequests' => 'My Requests'] as $method => $title) {
            ob_start();
            try { (new PublicUserController())->$method(); $html = ob_get_contents(); }
            finally { ob_end_clean(); }
            verify(str_contains($html, $title), $method . ' did not render');
        }
    };
    $render();
    $requestId = (new EWasteRequest())->create(['public_user_id' => 2, 'schedule_id' => $id, 'pickup_address' => 'Test']);
    verify($model->openForPostalArea(1) === [], 'Full schedule offered');
    verify(!$model->isBookable($id, 1), 'Full schedule bookable for new request');
    verify($model->isBookable($id, 1, $requestId), 'Existing request cannot retain its slot');
    $render();
    foreach (['PLANNED', 'CLOSED', 'CANCELLED', 'COMPLETED'] as $status) {
        $model->update($id, ['schedule_status' => $status]);
        verify($model->openForPostalArea(1) === [] && !$model->isBookable($id, 1, $requestId), 'Unavailable status offered: ' . $status);
    }
    $model->update($id, ['schedule_status' => 'OPEN', 'request_cutoff_at' => '2000-01-01 23:59:59']);
    verify($model->openForPostalArea(1) === [] && !$model->isBookable($id, 1, $requestId), 'Expired schedule offered');
    $render();
    echo "PASS: public pickup pages render with open/full/expired schedules; area, status, capacity and edit-slot eligibility checked\n";
} finally {
    $db->exec('USE `' . str_replace('`', '``', $originalDatabase) . '`');
    $db->exec("DROP DATABASE `$database`");
}
