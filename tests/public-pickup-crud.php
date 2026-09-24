<?php
declare(strict_types=1);

// Run against an isolated, disposable database; never writes the application database.
// php -d session.save_path=/tmp tests/public-pickup-crud.php
// The configured DB account needs CREATE/DROP DATABASE privileges.
require_once __DIR__ . '/support/bootstrap.php';
if (($argv[1] ?? '') === '--role-guard') {
    Session::start();
    if ($argv[2] !== 'guest') Session::put('auth_user', ['id' => 1, 'role' => 'MUNICIPAL_OFFICER']);
    register_shutdown_function(static function (): void { echo ' HTTP=' . http_response_code(); });
    $method = $argv[3];
    (new PublicUserController())->$method('1');
    echo 'UNPROTECTED';
    exit;
}
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

    $db->exec("INSERT INTO waste_categories (category_id, category_name) VALUES (1, 'Domestic E-Waste')");
    $db->exec("INSERT INTO e_waste_items (waste_item_id, category_id, item_name, collection_status) VALUES
        (1, 1, 'Lamp', 'ACCEPTED'), (2, 1, 'Battery', 'REVIEW_REQUIRED'), (3, 1, 'Forbidden', 'DO_NOT_COLLECT')");
    $db->exec("INSERT INTO risk_rules (waste_item_id, condition_type, risk_level, action_note) VALUES (1, 'DAMAGED', 'HIGH', 'Review damage')");
    $raw = [['category' => 'Domestic E-Waste', 'item' => 'Lamp', 'quantity' => '2', 'weight' => '1.250', 'condition' => 'WORKING', 'note' => '<script>note</script>']];
    $request = ['public_user_id' => 2, 'schedule_id' => $id];
    $requests = new EWasteRequest();
    $reject = static function (callable $operation): void {
        try { $operation(); } catch (DomainException $expected) { return; }
        throw new RuntimeException('Expected operation to be rejected');
    };
    foreach ([[], ['bad'], [array_replace($raw[0], ['item' => 'Invented'])], [array_replace($raw[0], ['item' => 'Forbidden'])],
        [array_replace($raw[0], ['weight' => '0'])], [array_replace($raw[0], ['weight' => '0.0001'])],
        [array_replace($raw[0], ['quantity' => '1.5'])], [array_replace($raw[0], ['note' => str_repeat('a', 501)])],
        [array_replace($raw[0], ['category' => ['bad']])]] as $bad) {
        $reject(fn () => $requests->createWithItems($request, $bad));
    }
    verify($requests->all() === [], 'Invalid input persisted request');
    verify((int) $db->query('SELECT COUNT(*) FROM e_waste_items')->fetchColumn() === 3, 'Validation mutated catalogue');
    $requestId = $requests->createWithItems($request, $raw);
    verify($requests->find($requestId)['request_status'] === 'SUBMITTED', 'Low risk status wrong');
    verify($requests->find($requestId)['pickup_address'] === 'Test address', 'Profile address missing');
    $reject(fn () => $requests->createWithItems($request, $raw));
    $reject(fn () => $requests->updateScheduleAndItems($requestId, $id, $raw, 3));
    $reject(fn () => $requests->cancelOwned($requestId, 3));
    $targetAttributes = $model->find($id);
    unset($targetAttributes['schedule_id']);
    $targetAttributes['collection_date'] = "$month-20";
    $target = $model->create($targetAttributes);
    $requests->updateScheduleAndItems($requestId, $target, $raw, 2);
    verify($model->isBookable($id, 1) && !$model->isBookable($target, 1), 'Schedule move did not transfer capacity');
    $requests->updateScheduleAndItems($requestId, $id, $raw, 2);
    $model->update($target, ['postal_area_id' => 2]);
    $reject(fn () => $requests->updateScheduleAndItems($requestId, $target, $raw, 2));
    verify((int) $requests->find($requestId)['schedule_id'] === $id, 'Wrong-area move changed request');
    $damaged = [array_replace($raw[0], ['condition' => 'DAMAGED'])];
    $requests->updateScheduleAndItems($requestId, $id, $damaged, 2);
    verify($requests->find($requestId)['request_status'] === 'PENDING_REVIEW', 'Condition risk not applied');
    verify($requests->find($requestId)['risk_review_status'] === 'PENDING', 'Review flag missing');
    verify($requests->forPublicUser(3) === [], 'Other user history leaked');
    $reject(fn () => $requests->updateScheduleAndItems($requestId, $id, [], 2));
    verify(count($requests->forPublicUser(2)[0]['items']) === 1, 'Failed update lost items');
    foreach (['newRequest', 'myRequests', 'dashboard'] as $method) {
        ob_start();
        try { (new PublicUserController())->$method(); $html = ob_get_contents(); }
        finally { ob_end_clean(); }
        verify(!str_contains($html, '<script>note</script>'), 'Unescaped note in page');
    }
    $requests->update($requestId, ['request_status' => 'APPROVED']);
    $reject(fn () => $requests->updateScheduleAndItems($requestId, $id, $raw, 2));
    $reject(fn () => $requests->cancelOwned($requestId, 2));
    $requests->update($requestId, ['request_status' => 'PENDING_REVIEW']);
    $model->update($id, ['schedule_status' => 'CLOSED']);
    $reject(fn () => $requests->cancelOwned($requestId, 2));
    $model->update($id, ['schedule_status' => 'OPEN']);
    $db->exec("INSERT INTO schedule_assignments (schedule_id, collector_user_id, assigned_by_officer_user_id) VALUES ($id, 3, 1)");
    $reject(fn () => $requests->cancelOwned($requestId, 2));
    $db->exec('DELETE FROM schedule_assignments');
    $requests->cancelOwned($requestId, 2);
    verify($requests->find($requestId)['request_status'] === 'CANCELLED', 'Cancellation missing');
    verify(count($requests->forPublicUser(2)[0]['items']) === 1, 'Cancellation deleted history');
    verify($model->isBookable($id, 1), 'Cancellation did not release capacity');
    $reviewId = $requests->createWithItems($request, [array_replace($raw[0], ['item' => 'Battery'])]);
    verify($requests->find($reviewId)['risk_review_status'] === 'PENDING', 'Review-required catalogue policy ignored');
    $requests->cancelOwned($reviewId, 2);

    $controller = new class extends PublicUserController {
        protected function redirect(string $path, int $status = 303): never { throw new LogicException($path); }
    };
    $post = static function (array $input) use ($controller): void {
        $_POST = $input;
        try { $controller->storeRequest(); } catch (LogicException $redirect) { return; }
        throw new RuntimeException('Controller did not redirect');
    };
    $post(['schedule_id' => (string) $id, 'items' => $raw, '_csrf_token' => 'bad']);
    verify(count($requests->all()) === 2, 'Bad CSRF persisted request');
    $token = Csrf::token();
    $post(['schedule_id' => (string) $id, 'items' => $raw, '_csrf_token' => $token]);
    verify(count($requests->all()) === 3, 'Controller did not save request');
    $post(['schedule_id' => (string) $id, 'items' => $raw, '_csrf_token' => $token]);
    verify(count($requests->all()) === 3, 'Repeated submission duplicated request');
    foreach (['updateRequest', 'deleteRequest'] as $method) {
        $_POST = ['_csrf_token' => 'bad', 'schedule_id' => (string) $id, 'items' => $raw];
        $before = $requests->all();
        try { $controller->$method((string) $requestId); } catch (LogicException $redirect) {}
        verify($before === $requests->all(), 'Bad CSRF changed request: ' . $method);
    }
    $router = new Router();
    (require APP_ROOT . '/routes/web.php')($router, []);
    foreach (['update', 'delete'] as $action) {
        ob_start(); $router->dispatch('GET', '/user/my-requests/1/' . $action); ob_end_clean();
        verify(http_response_code() === 405, 'GET mutation allowed');
    }
    foreach (['newRequest', 'myRequests', 'storeRequest', 'updateRequest', 'deleteRequest'] as $method) {
        foreach (['guest', 'officer'] as $role) {
            $process = proc_open([PHP_BINARY, '-d', 'session.save_path=' . sys_get_temp_dir(), __FILE__, '--role-guard', $role, $method], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
            $output = stream_get_contents($pipes[1]); $error = stream_get_contents($pipes[2]);
            fclose($pipes[1]); fclose($pipes[2]);
            verify(proc_close($process) === 0 && $error === '', 'Role guard failed: ' . $error);
            verify(!str_contains($output, 'UNPROTECTED') && str_contains($output, $role === 'guest' ? 'HTTP=302' : 'HTTP=403'), 'Public role protection missing: ' . $method);
        }
    }
    echo "PASS: pickup CRUD, catalogue restrictions, risk rules, ownership, capacity, locks, rollback, cancellation history, rendering and CSRF replay checks\n";
} finally {
    $db->exec('USE `' . str_replace('`', '``', $originalDatabase) . '`');
    $db->exec("DROP DATABASE `$database`");
}
