<?php
declare(strict_types=1);

// Must never be run against the real ecolot_lk database.
if (PHP_SAPI !== 'cli'
    || getenv('ECOLOT_ADMIN_DASHBOARD_TEST') !== '1'
    || !preg_match('/^ecolot_test_[a-z0-9_]+$/i', (string) getenv('DB_DATABASE'))) {
    fwrite(STDERR, "REFUSED: requires CLI, ECOLOT_ADMIN_DASHBOARD_TEST=1 and DB_DATABASE=ecolot_test_*\n");
    exit(2);
}
require dirname(__DIR__) . '/app/autoload.php';

$db = Database::connection();
$database = (string) $db->query('SELECT DATABASE()')->fetchColumn();
if (!preg_match('/^ecolot_test_[a-z0-9_]+$/i', $database)) {
    throw new RuntimeException('Connected database is not an approved disposable test database.');
}

$service = new AdminDashboardService($db);
$baseline = $service->statistics();
$check = static function (bool $condition, string $label): void {
    if (!$condition) throw new RuntimeException('Admin dashboard regression failed: ' . $label);
};
$insert = static function (string $sql, array $data) use ($db): int {
    $statement = $db->prepare($sql);
    $statement->execute($data);
    return (int) $db->lastInsertId();
};
$tag = 'DASHQA-' . bin2hex(random_bytes(5));
$mobile = static fn (): string => '947' . (string) random_int(10000000, 99999999);
$db->beginTransaction();
try {
    $adminId = $insert(
        "INSERT INTO users(full_name, mobile_number, password_hash, role, account_status)
         VALUES (?, ?, ?, 'ADMIN', 'ACTIVE')",
        [$tag . ' Admin', $mobile(), 'synthetic-no-login']
    );
    $insert('INSERT INTO administrators(user_id) VALUES (?)', [$adminId]);
    foreach (['MUNICIPAL_OFFICER', 'COLLECTOR', 'RECYCLER', 'RECYCLER', 'PUBLIC_USER'] as $index => $role) {
        $id = $insert(
            "INSERT INTO users(full_name, mobile_number, password_hash, role, account_status, created_by_admin_user_id)
             VALUES (?, ?, ?, ?, 'ACTIVE', ?)",
            [$tag . ' ' . $role . ' ' . $index, $mobile(), 'synthetic-no-login', $role, $adminId]
        );
        if ($role === 'RECYCLER') {
            $verified = $index === 3;
            $insert(
                "INSERT INTO authorized_recyclers(user_id, company_name, business_address, district,
                      verification_status, reviewed_by_admin_user_id, reviewed_at)
                 VALUES (?, ?, 'QA only', 'Colombo', ?, ?, ?)",
                [$id, $tag . ' Recycler ' . $index, $verified ? 'VERIFIED' : 'PENDING',
                 $verified ? $adminId : null, $verified ? date('Y-m-d H:i:s') : null]
            );
        }
    }
    $categoryId = $insert('INSERT INTO waste_categories(category_name) VALUES (?)', [$tag . ' Category']);
    $itemId = $insert('INSERT INTO e_waste_items(category_id,item_name) VALUES (?,?)', [$categoryId, $tag . ' Item']);
    $insert("INSERT INTO risk_rules(waste_item_id,condition_type,risk_level,action_note) VALUES (?,'WORKING','LOW','QA test')", [$itemId]);
    $insert('INSERT INTO audit_logs(actor_user_id,action_type,entity_type,entity_id) VALUES (?, ?, ?, ?)',
        [$adminId, 'RECYCLER_CONTACT_UPDATED', 'users', $adminId]);

    $current = $service->statistics();
    foreach (['total_users' => 6, 'admin_users' => 1, 'municipal_officers' => 1, 'collectors' => 1,
              'recycler_users' => 2, 'public_users' => 1,
              'pending_recyclers' => 1, 'verified_recyclers' => 1,
              'e_waste_categories' => 1, 'e_waste_items' => 1, 'risk_rules' => 1] as $key => $increment) {
        $check($current[$key] === $baseline[$key] + $increment, $key . ' dynamically counts database rows');
    }
    $check(
        $current['admin_users'] + $current['municipal_officers'] + $current['collectors']
        + $current['recycler_users'] + $current['public_users'] === $current['total_users'],
        'role overview covers every user exactly once'
    );
    $events = $service->recentActivity(25);
    $check(count($events) <= 25, 'activity is bounded');
    $check(count(array_filter($events, static fn ($e): bool => str_contains($e['title'], 'Recycler contact person updated'))) > 0,
        'actual audit record appears in Recent Activity');
    $check(count(array_filter($events, static fn ($e): bool => str_contains($e['title'], $tag . ' Recycler 3'))) > 0,
        'actual Recycler review appears in Recent Activity');
    $check(count(array_filter($events, static fn ($e): bool => str_contains($e['title'], 'User account created:'))) > 0,
        'actual admin-created user appears in Recent Activity');
    $check(count(array_filter($events, static fn ($e): bool => ($e['source'] === 'review') &&
        preg_match('~^/admin/recycler-verification/[1-9][0-9]*$~', (string) $e['href']) === 1)) > 0,
        'review event has a valid Recycler details route');
    $check(count(array_filter($events, static fn ($e): bool => ($e['source'] === 'account') &&
        $e['href'] === '/admin/users')) > 0, 'account event links to Admin Users');
    $db->rollBack();
    echo "PASS: Admin Dashboard dynamic counts, real recent events, bounded activity and rollback\n";
} catch (Throwable $error) {
    if ($db->inTransaction()) $db->rollBack();
    throw $error;
}
