<?php
declare(strict_types=1);

// Run every standalone regression in a fresh process to isolate sessions/classes.
$root = dirname(__DIR__);
$phpTests = [
    'run.php',
    'login-rate-limit-storage.php',
    'otp-locking.php',
    'area-schedules.php',
    'collector-crud.php',
    'public-pickup-crud.php',
    'public-pickup-pages.php',
    'pickup-concurrency.php',
    'ewaste-catalogue-seed.php',
    'seed-compatibility.php',
    'schedule-index-migration.php',
];
$run = static function (array $arguments): void {
    $command = implode(' ', array_map('escapeshellarg', $arguments));
    passthru($command, $status);
    if ($status !== 0) {
        exit($status);
    }
};
chdir($root);
// Profile integration tests own guarded random databases; never target the application DB.
if (in_array('--recycler-profile', $argv, true)) {
    foreach (['recycler-profile-ui.php', 'recycler-dashboard-ui.php'] as $test) {
        $run([PHP_BINARY, '-d', 'session.save_path=' . sys_get_temp_dir(), __DIR__ . '/' . $test]);
    }
    foreach (['ECOLOT_PROFILE_SECURITY_TEST' => 'recycler-profile-security.php',
        'ECOLOT_PHONE_TEST' => 'recycler-phone-change.php',
        'ECOLOT_CAPABILITY_TEST' => 'recycler-capability-changes.php',
        'ECOLOT_COVERAGE_TEST' => 'recycler-licence-coverage.php'] as $optIn => $test) {
        putenv($optIn . '=1');
        $run([PHP_BINARY, '-d', 'session.save_path=' . sys_get_temp_dir(), __DIR__ . '/' . $test]);
        putenv($optIn);
    }
    $run(['node', __DIR__ . '/escaping.js']);
    echo "PASS: Recycler Profile regression suite (disposable databases; fake SMS only)\n";
    exit;
}
foreach ($phpTests as $test) {
    $run([PHP_BINARY, '-d', 'session.save_path=' . sys_get_temp_dir(), __DIR__ . '/' . $test]);
}
$run(['node', __DIR__ . '/escaping.js']);
if (in_array('--browser', $argv, true)) {
    $run(['node', __DIR__ . '/pickup-browser.cjs']);
    $run(['node', __DIR__ . '/collector-browser.cjs']);
}
echo "PASS: complete regression suite\n";
