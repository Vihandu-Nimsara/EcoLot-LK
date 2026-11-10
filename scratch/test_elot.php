<?php
declare(strict_types=1);

// Autoloader – adjust if your project uses a different path
require __DIR__.'/../vendor/autoload.php';
require __DIR__.'/../app/Models/ELot.php';

// Simple Auth mock – replace with real Auth if you run inside the app
class Auth {
    public static function id(): int { return 3; } // demo collector ID – ensure this user exists
    public static function requireRole(string $role): void { /* no‑op for test */ }
}

try {
    $collectorId = Auth::id();
    $model = new ELot();

    echo "--- Creating a new E‑Lot ---\n";
    // Use existing verified record_item_id values from your DB (replace 101,102 with real IDs)
    $newLotId = $model->createCollectorLot($collectorId, 'Test Lot', 5, [101, 102]);
    echo "Created lot with e_lot_id = $newLotId\n";

    $lot = $model->findCollectorLot($newLotId, $collectorId);
    echo "Fetched after create:\n";
    var_export($lot);
    echo "\n\n";

    echo "--- Updating the E‑Lot (title + items) ---\n";
    $model->updateCollectorLot($newLotId, $collectorId, 'Test Lot – Updated', [103, 104]);
    $updated = $model->findCollectorLot($newLotId, $collectorId);
    echo "Fetched after update:\n";
    var_export($updated);
    echo "\n\n";

    echo "--- Submitting the E‑Lot ---\n";
    $model->submitCollectorLot($newLotId, $collectorId);
    $submitted = $model->findCollectorLot($newLotId, $collectorId);
    echo "Fetched after submit (status should be PENDING_VERIFICATION):\n";
    var_export($submitted);
    echo "\n\n";

    echo "--- Deleting the E‑Lot ---\n";
    $model->deleteCollectorLot($newLotId, $collectorId);
    $deleted = $model->findCollectorLot($newLotId, $collectorId);
    echo "Fetched after delete (should be null):\n";
    var_export($deleted);
    echo "\n\nFinished test.\n";
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>