<?php
// Simple CRUD test for e_lots table using PDO

$dsn = 'mysql:host=127.0.0.1;dbname=ecolot_lk;charset=utf8mb4';
$username = 'root';
$password = '';

try {
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    echo "Connected to DB\n";
} catch (Exception $e) {
    die('Connection failed: ' . $e->getMessage());
}

// Insert a new e_lot (no items yet)
$lotCode = 'LOT' . time();
$title = 'Test Lot';
$categoryId = 1; // assuming category exists
$collectorId = 3; // assuming collector user exists

$stmt = $pdo->prepare('INSERT INTO e_lots (lot_code, title, category_id, created_by_collector_user_id, lot_status) VALUES (:lot_code, :title, :cat, :collector, "DRAFT")');
$stmt->execute([
    ':lot_code' => $lotCode,
    ':title' => $title,
    ':cat' => $categoryId,
    ':collector' => $collectorId,
]);
$lotId = $pdo->lastInsertId();

echo "Created e_lot with ID $lotId\n";

// Verify insertion
$row = $pdo->query('SELECT * FROM e_lots WHERE e_lot_id = ' . $lotId)->fetch();
print_r($row);

// Update the lot title
$newTitle = 'Updated Test Lot';
$pdo->prepare('UPDATE e_lots SET title = :title WHERE e_lot_id = :id')->execute([':title' => $newTitle, ':id' => $lotId]);

echo "Updated title.\n";
$row = $pdo->query('SELECT title FROM e_lots WHERE e_lot_id = ' . $lotId)->fetch();
print_r($row);

// Delete the lot
$pdo->prepare('DELETE FROM e_lots WHERE e_lot_id = :id')->execute([':id' => $lotId]);

echo "Deleted e_lot.\n";
$row = $pdo->query('SELECT * FROM e_lots WHERE e_lot_id = ' . $lotId)->fetch();
var_dump($row);
?>