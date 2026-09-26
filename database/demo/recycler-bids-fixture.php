<?php
declare(strict_types=1);

// Development only. The caller owns the transaction. No schema/seed changes.
function recyclerBidFixture(PDO $db, string $password): array
{
    $tag = 'BID-' . bin2hex(random_bytes(5));
    $insert = static function (string $sql, array $parameters = []) use ($db): int {
        $statement = $db->prepare($sql);
        $statement->execute($parameters);
        return (int) $db->lastInsertId();
    };
    $users = [];
    $mobiles = [];
    foreach (['admin' => 'ADMIN', 'officer' => 'MUNICIPAL_OFFICER', 'collector' => 'COLLECTOR', 'public' => 'PUBLIC_USER', 'recycler' => 'RECYCLER', 'competitor' => 'RECYCLER'] as $key => $role) {
        $mobile = '947' . (string) random_int(10000000, 99999999);
        $users[$key] = $insert("INSERT INTO users (full_name, mobile_number, password_hash, role, account_status, mobile_verified_at) VALUES (?, ?, ?, ?, 'ACTIVE', CURRENT_TIMESTAMP)", [$tag . ' ' . $key, $mobile, password_hash($password, PASSWORD_DEFAULT), $role]);
        $mobiles[$key] = $mobile;
    }
    $insert('INSERT INTO administrators (user_id) VALUES (?)', [$users['admin']]);
    $insert('INSERT INTO municipal_officers (user_id) VALUES (?)', [$users['officer']]);
    $insert('INSERT INTO collectors (user_id) VALUES (?)', [$users['collector']]);
    $category = $insert('INSERT INTO waste_categories (category_name) VALUES (?)', [$tag . ' Electronics']);
    $item = $insert('INSERT INTO e_waste_items (category_id, item_name) VALUES (?, ?)', [$category, $tag . ' Laptop']);
    foreach (['recycler', 'competitor'] as $key) {
        $insert("INSERT INTO authorized_recyclers (user_id, company_name, business_address, district, verification_status, verified_by_admin_user_id, verified_at) VALUES (?, ?, 'Development fixture address', 'Colombo', 'VERIFIED', ?, CURRENT_TIMESTAMP)", [$users[$key], $tag . ' ' . $key, $users['admin']]);
        $insert("INSERT INTO recycler_licenses (recycler_user_id, license_number, expiry_date, license_status) VALUES (?, ?, CURRENT_DATE + INTERVAL 1 YEAR, 'VALID')", [$users[$key], $tag . '-' . $key]);
        $insert("INSERT INTO recycler_capabilities (recycler_user_id, category_id, can_handle_high_risk, capability_status) VALUES (?, ?, 1, 'APPROVED')", [$users[$key], $category]);
    }
    $area = $insert('INSERT INTO postal_code_areas (postal_code, area_name) VALUES (?, ?)', [substr($tag, 4), $tag . ' Area']);
    $insert('INSERT INTO public_profiles (user_id, postal_area_id, address) VALUES (?, ?, ?)', [$users['public'], $area, 'Development pickup address']);
    // One campaign per calendar month: reuse the current campaign if present.
    $campaign = $db->query("SELECT campaign_id FROM monthly_campaigns WHERE campaign_month = DATE_FORMAT(CURRENT_DATE, '%Y-%m-01')")->fetchColumn();
    if (!$campaign) $campaign = $insert("INSERT INTO monthly_campaigns (created_by_officer_user_id, campaign_name, campaign_month) VALUES (?, ?, DATE_FORMAT(CURRENT_DATE, '%Y-%m-01'))", [$users['officer'], $tag]);
    $schedule = $insert("INSERT INTO area_collection_schedules (campaign_id, postal_area_id, created_by_officer_user_id, collection_date, request_cutoff_at, request_capacity, schedule_status) VALUES (?, ?, ?, CURRENT_DATE + INTERVAL 3 DAY, CURRENT_TIMESTAMP + INTERVAL 1 DAY, 10, 'OPEN')", [$campaign, $area, $users['officer']]);
    $insert('INSERT INTO schedule_assignments (schedule_id, collector_user_id, assigned_by_officer_user_id) VALUES (?, ?, ?)', [$schedule, $users['collector'], $users['officer']]);
    $request = $insert("INSERT INTO e_waste_requests (public_user_id, schedule_id, pickup_address) VALUES (?, ?, 'Development pickup address')", [$users['public'], $schedule]);
    $requestItem = $insert("INSERT INTO request_items (request_id, waste_item_id, quantity, estimated_weight_kg, item_condition, applied_risk_level) VALUES (?, ?, 5, 12.5, 'DAMAGED', 'HIGH')", [$request, $item]);
    $collection = $insert("INSERT INTO schedule_collections (schedule_id, submitted_by_collector_user_id, verification_status, verified_by_officer_user_id, verified_at) VALUES (?, ?, 'VERIFIED', ?, CURRENT_TIMESTAMP)", [$schedule, $users['collector'], $users['officer']]);
    $record = $insert("INSERT INTO collection_records (schedule_collection_id, request_id, pickup_result, collected_at) VALUES (?, ?, 'COLLECTED', CURRENT_TIMESTAMP)", [$collection, $request]);
    $recordItem = $insert("INSERT INTO collection_record_items (collection_record_id, request_item_id, actual_quantity, actual_weight_kg, actual_condition, item_result) VALUES (?, ?, 5, 12.1, 'DAMAGED', 'COLLECTED')", [$record, $requestItem]);
    $lot = $insert("INSERT INTO e_lots (lot_code, created_by_collector_user_id, category_id, title, lot_status, verified_by_officer_user_id, verified_at, bidding_open_at, bidding_close_at) VALUES (?, ?, ?, 'Development laptop lot', 'OPEN_FOR_BIDDING', ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP - INTERVAL 1 DAY, CURRENT_TIMESTAMP + INTERVAL 7 DAY)", [$tag, $users['collector'], $category, $users['officer']]);
    $insert('INSERT INTO e_lot_items (record_item_id, e_lot_id) VALUES (?, ?)', [$recordItem, $lot]);
    $competitorBid = $insert("INSERT INTO recycler_bids (e_lot_id, recycler_user_id, bid_amount) VALUES (?, ?, 1000)", [$lot, $users['competitor']]);
    return compact('users', 'mobiles', 'category', 'lot', 'requestItem', 'competitorBid', 'tag');
}

if (PHP_SAPI === 'cli' && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    if (getenv('ECOLOT_DEMO_FIXTURE') !== '1' || !getenv('ECOLOT_DEMO_PASSWORD') || !str_starts_with((string) getenv('DB_DATABASE'), 'ecolot_demo_')) {
        fwrite(STDERR, "Set ECOLOT_DEMO_FIXTURE=1, ECOLOT_DEMO_PASSWORD, and DB_DATABASE=ecolot_demo_<name>. Import the schema into that development database first.\n");
        exit(1);
    }
    require dirname(__DIR__, 2) . '/app/Core/Database.php';
    $db = Database::connection();
    $db->beginTransaction();
    try {
        $fixture = recyclerBidFixture($db, (string) getenv('ECOLOT_DEMO_PASSWORD'));
        $db->commit();
        echo json_encode($fixture, JSON_PRETTY_PRINT) . "\n";
    } catch (Throwable $error) {
        if ($db->inTransaction()) $db->rollBack();
        throw $error;
    }
}
