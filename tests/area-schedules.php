<?php
declare(strict_types=1);

// Run against an isolated, disposable database; never writes the application database.
// php -d session.save_path=/tmp tests/area-schedules.php
// The configured DB account needs CREATE/DROP DATABASE privileges.
define('APP_ROOT', dirname(__DIR__));
spl_autoload_register(static function (string $class): void {
    foreach (['Core', 'Controllers', 'Models'] as $directory) {
        $file = APP_ROOT . '/app/' . $directory . '/' . $class . '.php';
        if (is_file($file)) { require_once $file; return; }
    }
});
final class ScheduleTestRedirect extends RuntimeException {}
final class ScheduleTestController extends MunicipalOfficerController
{
    public array $data = [];
    public function view(string $view, array $data = [], ?string $layout = null): void { $this->data = $data; }
    protected function redirect(string $path, int $status = 303): never { throw new ScheduleTestRedirect($path); }
}
function verify(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}
if (($argv[1] ?? '') === '--role-guard') {
    Session::start();
    if (($argv[2] ?? '') !== 'guest') Session::put('auth_user', ['id' => 2, 'role' => 'PUBLIC_USER']);
    register_shutdown_function(static function (): void { echo ' HTTP=' . http_response_code(); });
    $method = $argv[3];
    (new MunicipalOfficerController())->$method('1');
    echo 'UNPROTECTED';
    exit;
}
Session::start();
Session::put('auth_user', ['id' => 1, 'role' => 'MUNICIPAL_OFFICER']);
$token = Csrf::token();
$db = Database::connection();
$originalDatabase = (string) $db->query('SELECT DATABASE()')->fetchColumn();
$database = 'ecolot_schedule_test_' . bin2hex(random_bytes(6));
$db->exec("CREATE DATABASE `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
try {
    $db->exec("USE `$database`");
    $schema = file_get_contents(APP_ROOT . '/database/schema.sql');
    $schema = preg_replace('/CREATE DATABASE IF NOT EXISTS `ecolot_lk`.*?;/s', '', $schema);
    $schema = str_replace('USE `ecolot_lk`;', '', $schema);
    [$tables, $triggers] = explode('DELIMITER $$', $schema, 2);
    foreach (explode(';', $tables) as $sql) {
        if (trim($sql) !== '') $db->exec($sql);
    }
    $triggers = str_replace('DELIMITER ;', '', $triggers);
    foreach (explode('$$', $triggers) as $sql) {
        if (trim($sql) !== '') $db->exec($sql);
    }
    $db->exec("INSERT INTO users (user_id, full_name, mobile_number, password_hash, role) VALUES
        (1, '<script>Officer</script>', '0771234567', 'unused', 'MUNICIPAL_OFFICER'),
        (2, 'Public', '0771234568', 'unused', 'PUBLIC_USER'),
        (3, 'Collector', '0771234569', 'unused', 'COLLECTOR')");
    $db->exec('INSERT INTO municipal_officers VALUES (1)');
    $db->exec('INSERT INTO collectors VALUES (3)');
    $db->exec("INSERT INTO postal_code_areas VALUES (1, '00100', 'Area One', 'ACTIVE'), (2, '00200', 'Area Two', 'ACTIVE'), (3, '00300', 'Inactive', 'INACTIVE')");
    $db->exec("INSERT INTO public_profiles VALUES (2, 1, 'Test address')");
    $month = (new DateTimeImmutable('first day of next month', new DateTimeZone('Asia/Colombo')))->format('Y-m');
    $statement = $db->prepare("INSERT INTO monthly_campaigns (campaign_id, created_by_officer_user_id, campaign_name, campaign_month, campaign_status) VALUES (1, 1, '<b>Campaign</b>', ?, 'OPEN'), (2, 1, 'Closed', '2000-01-01', 'CLOSED')");
    $statement->execute([$month . '-01']);
    $controller = new ScheduleTestController();
    $model = new AreaCollectionSchedule();
    $valid = ['campaign_id' => '1', 'postal_area_id' => '1', 'request_capacity' => '5', 'request_cutoff_date' => "$month-08", 'collection_date' => "$month-10"];
    $invoke = static function (string $method, array $input, ?int $id = null) use ($controller, $token): bool {
        $_POST = $input + ['_csrf_token' => $token];
        $controller->data = [];
        http_response_code(200);
        try {
            if ($id === null) $controller->$method(); else $controller->$method((string) $id);
        } catch (ScheduleTestRedirect $redirect) { return true; }
        return false;
    };
    foreach ([
        ['campaign_id' => '999'], ['campaign_id' => '2'], ['campaign_id' => ['1']],
        ['postal_area_id' => '999'], ['postal_area_id' => '3'],
        ['request_capacity' => '0'], ['request_capacity' => '-1'], ['request_capacity' => '1.5'],
        ['request_capacity' => '4294967296'], ['request_capacity' => str_repeat('9', 40)],
        ['collection_date' => "2030-02-10\0"], ['collection_date' => '2030-02-30'], ['collection_date' => '2000-01-10'],
        ['collection_date' => "$month-10 trailing"], ['collection_date' => '2099-01-10'],
        ['request_cutoff_date' => "$month-10"], ['request_cutoff_date' => "$month-11"],
    ] as $change) {
        verify(!$invoke('storeAreaSchedule', array_replace($valid, $change)), 'Invalid create accepted: ' . json_encode($change));
        verify(!empty($controller->data['errors']), 'Create must return validation errors');
    }
    verify($model->all() === [], 'Invalid creates wrote rows');
    ob_start();
    verify(!$invoke('storeAreaSchedule', $valid + ['_csrf_token' => 'bad']), 'Bad CSRF accepted');
    ob_end_clean();
    verify(http_response_code() === 403 && $model->all() === [], 'CSRF status/write');
    verify($invoke('storeAreaSchedule', $valid + ['schedule_status' => 'COMPLETED', 'created_by_officer_user_id' => '3']), 'Valid create failed');
    $id = (int) $model->all()[0]['schedule_id'];
    $created = $model->find($id);
    verify($created['schedule_status'] === 'PLANNED' && (int) $created['created_by_officer_user_id'] === 1, 'Creation trusted forged fields');
    verify($created['request_cutoff_at'] === "$month-08 23:59:59", 'Cutoff not end of day');
    verify(!$invoke('storeAreaSchedule', $valid), 'Duplicate accepted');
    verify($invoke('storeAreaSchedule', array_replace($valid, ['collection_date' => "$month-20"])), 'Second schedule rejected');
    verify(!$invoke('storeAreaSchedule', array_replace($valid, ['collection_date' => "$month-25"])), 'Third schedule accepted');
    verify($invoke('storeAreaSchedule', array_replace($valid, ['postal_area_id' => '2'])), 'Limit incorrectly shared by areas');
    verify($model->availableForPublicUser(2) === [], 'PLANNED schedule offered publicly');
    verify($invoke('updateAreaSchedule', ['request_capacity' => '5', 'schedule_status' => 'OPEN', 'collection_date' => '2099-01-01'], $id), 'PLANNED -> OPEN failed');
    verify($model->find($id)['collection_date'] === $created['collection_date'], 'Immutable date changed');
    verify(count($model->availableForPublicUser(2)) === 1, 'Open schedule not offered');
    $requests = new EWasteRequest();
    $requestId = $requests->create(['public_user_id' => 2, 'schedule_id' => $id, 'pickup_address' => 'Test']);
    $requests->create(['public_user_id' => 2, 'schedule_id' => $id, 'pickup_address' => 'Test', 'request_status' => 'CANCELLED']);
    $requests->create(['public_user_id' => 2, 'schedule_id' => $id, 'pickup_address' => 'Test', 'request_status' => 'REJECTED']);
    $requests->create(['public_user_id' => 2, 'schedule_id' => $id, 'pickup_address' => 'Test']);
    verify($model->countActiveRequests($id) === 2, 'Active count includes cancelled/rejected');
    verify(!$invoke('updateAreaSchedule', ['request_capacity' => '1', 'schedule_status' => 'OPEN'], $id), 'Capacity below requests accepted');
    verify(str_contains(implode(' ', $controller->data['errors']), '2 existing requests'), 'Capacity message missing count');
    verify($invoke('updateAreaSchedule', ['request_capacity' => '2', 'schedule_status' => 'OPEN'], $id), 'Capacity equal to count rejected');
    verify($model->availableForPublicUser(2) === [], 'Full schedule offered');
    try { $requests->create(['public_user_id' => 2, 'schedule_id' => $id, 'pickup_address' => 'Test']); throw new RuntimeException('Full schedule accepted request'); } catch (DomainException $expected) {}
    verify(!$invoke('deleteAreaSchedule', [], $id), 'Used schedule deleted');
    foreach (['ASSIGNED', 'IN_PROGRESS', 'COLLECTION_SUBMITTED', 'COMPLETED', 'PLANNED'] as $state) {
        verify(!$invoke('updateAreaSchedule', ['request_capacity' => '5', 'schedule_status' => $state], $id), 'Illegal status accepted');
    }
    verify($invoke('updateAreaSchedule', ['request_capacity' => '5', 'schedule_status' => 'CLOSED'], $id), 'OPEN -> CLOSED failed');
    verify($invoke('updateAreaSchedule', ['request_capacity' => '5', 'schedule_status' => 'OPEN'], $id), 'CLOSED -> OPEN before cutoff failed');
    verify($invoke('updateAreaSchedule', ['request_capacity' => '5', 'schedule_status' => 'CLOSED'], $id), 'Close before cancellation failed');
    verify($invoke('updateAreaSchedule', ['request_capacity' => '5', 'schedule_status' => 'CANCELLED'], $id), 'CLOSED -> CANCELLED failed');
    verify(!$invoke('updateAreaSchedule', ['request_capacity' => '5', 'schedule_status' => 'OPEN'], $id), 'CANCELLED reopened');
    verify(!$invoke('updateAreaSchedule', ['request_capacity' => '6', 'schedule_status' => 'CANCELLED'], $id), 'Cancelled capacity edited');
    verify((int) $db->query("SELECT COUNT(*) FROM e_waste_requests WHERE schedule_id = $id AND request_status IN ('SUBMITTED', 'PENDING_REVIEW', 'APPROVED')")->fetchColumn() === 0, 'Cancellation left pending requests');
    verify($model->countActiveRequests($id) === 0, 'Cancelled requests still counted');
    verify($invoke('storeAreaSchedule', array_replace($valid, ['collection_date' => "$month-25"])), 'Cancelled schedule consumed limit');
    verify(!$invoke('storeAreaSchedule', $valid), 'Cancelled duplicate date accepted');
    $details = $model->getScheduleDetails($id);
    verify($details['created_by_name'] === '<script>Officer</script>' && (int) $details['active_request_count'] === 0 && $details['area_name'] === 'Area One', 'JOIN details wrong');
    $otherId = (int) $db->query('SELECT schedule_id FROM area_collection_schedules WHERE postal_area_id = 2')->fetchColumn();
    $model->update($otherId, ['schedule_status' => 'OPEN']);
    try { $requests->create(['public_user_id' => 2, 'schedule_id' => $otherId, 'pickup_address' => 'Test']); throw new RuntimeException('Wrong area accepted'); } catch (DomainException $expected) {}
    verify($invoke('deleteAreaSchedule', [], $otherId) && $model->find($otherId) === null, 'Unused schedule delete failed');
    $unusedId = (int) $db->query("SELECT schedule_id FROM area_collection_schedules WHERE collection_date = '$month-20'")->fetchColumn();
    $db->exec("INSERT INTO schedule_assignments (schedule_id, collector_user_id, assigned_by_officer_user_id) VALUES ($unusedId, 3, 1)");
    verify(!$invoke('deleteAreaSchedule', [], $unusedId), 'Assigned schedule deleted');
    verify(!$invoke('updateAreaSchedule', ['request_capacity' => '5', 'schedule_status' => 'CANCELLED'], $unusedId), 'Assigned work cancelled');
    verify($model->find($unusedId)['schedule_status'] === 'PLANNED', 'Failed cancellation changed status');
    $db->exec("INSERT INTO schedule_collections (schedule_id, submitted_by_collector_user_id) VALUES ($unusedId, 3)");
    verify($model->hasCollectionSubmission($unusedId), 'Collection check missing');
    $db->exec("DELETE FROM schedule_assignments WHERE schedule_id = $unusedId");
    verify(!$invoke('deleteAreaSchedule', [], $unusedId), 'Collection-only schedule deleted');
    $model->update($unusedId, ['request_cutoff_at' => '2000-01-01 23:59:59', 'schedule_status' => 'CLOSED']);
    verify(!$invoke('updateAreaSchedule', ['request_capacity' => '5', 'schedule_status' => 'OPEN'], $unusedId), 'Expired CLOSED schedule reopened');
    $sample = ['schedule_status' => 'CLOSED', 'request_cutoff_at' => "$month-08 23:59:59"];
    $boundary = new DateTimeImmutable($sample['request_cutoff_at'], new DateTimeZone('Asia/Colombo'));
    verify(in_array('OPEN', AreaCollectionSchedule::manualStatuses($sample, $boundary), true), 'Exact cutoff rejected');
    verify(!in_array('OPEN', AreaCollectionSchedule::manualStatuses($sample, $boundary->modify('+1 second')), true), 'Past cutoff accepted');
    $expiredPlanned = ['schedule_status' => 'PLANNED', 'request_cutoff_at' => '2000-01-01 23:59:59'];
    verify(!in_array('OPEN', AreaCollectionSchedule::manualStatuses($expiredPlanned), true), 'Expired planned schedule opens');
    verify(in_array('CANCELLED', AreaCollectionSchedule::manualStatuses($sample, $boundary->modify('+1 second')), true), 'Closed schedule cannot cancel');
    verify(AreaCollectionSchedule::intakeLabel(['schedule_status' => 'OPEN', 'request_cutoff_at' => '2000-01-01 23:59:59', 'active_request_count' => 0, 'request_capacity' => 5]) === 'Deadline passed', 'Expired intake label missing');
    $model->update($unusedId, ['schedule_status' => 'COMPLETED']);
    verify(!$invoke('updateAreaSchedule', ['request_capacity' => '6', 'schedule_status' => 'COMPLETED'], $unusedId), 'Completed capacity edited');
    foreach (['ASSIGNED', 'IN_PROGRESS', 'COLLECTION_SUBMITTED', 'COMPLETED', 'CANCELLED'] as $state) {
        verify(AreaCollectionSchedule::manualStatuses(['schedule_status' => $state] + $sample) === [$state], 'Workflow state editable');
    }
    foreach (['areaSchedules', 'storeAreaSchedule', 'showAreaSchedule', 'updateAreaSchedule', 'deleteAreaSchedule'] as $method) {
        foreach (['guest', 'public'] as $role) {
            $process = proc_open([PHP_BINARY, '-d', 'session.save_path=' . sys_get_temp_dir(), __FILE__, '--role-guard', $role, $method], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
            $output = stream_get_contents($pipes[1]);
            $error = stream_get_contents($pipes[2]);
            fclose($pipes[1]); fclose($pipes[2]);
            verify(proc_close($process) === 0 && $error === '', 'Role guard subprocess failed: ' . $error);
            verify(!str_contains($output, 'UNPROTECTED') && str_contains($output, $role === 'guest' ? 'HTTP=302' : 'HTTP=403'), 'Role protection missing: ' . $method);
        }
    }
    $router = new Router();
    (require APP_ROOT . '/routes/web.php')($router, []);
    foreach (['update', 'delete'] as $action) {
        ob_start(); $router->dispatch('GET', "/officer/area-schedules/$id/$action"); ob_end_clean();
        verify(http_response_code() === 405, 'GET mutation allowed');
        ob_start(); $invoke($action . 'AreaSchedule', ['_csrf_token' => 'bad'], $id); ob_end_clean();
        verify(http_response_code() === 403, 'Mutation CSRF missing');
    }
    ob_start();
    (new MunicipalOfficerController())->showAreaSchedule((string) $id);
    $html = ob_get_clean();
    verify(str_contains($html, '&lt;script&gt;Officer&lt;/script&gt;') && !str_contains($html, '<script>Officer</script>'), 'Details output not escaped');
    ob_start(); (new MunicipalOfficerController())->areaSchedules(); $html = ob_get_clean();
    verify(str_contains($html, '&lt;b&gt;Campaign&lt;/b&gt;'), 'List output not escaped');
    verify(!str_contains(file_get_contents(APP_ROOT . '/public/assets/js/municipal_officer/area-schedules.js'), 'localStorage'), 'Prototype persistence remains');
    echo "PASS: schedule CRUD, validation, counts, status transitions, availability, CSRF, routes, foreign keys, and HTML escaping\n";
} finally {
    $db->exec('USE `' . str_replace('`', '``', $originalDatabase) . '`');
    $db->exec("DROP DATABASE `$database`");
}
