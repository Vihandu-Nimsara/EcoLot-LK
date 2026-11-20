<?php
declare(strict_types=1);

// Pure presentation regression; no application bootstrap or database connection.
$view = dirname(__DIR__) . '/app/Views/admin/dashboard.php';
$basePath = '/EcoLot-LK/public';
$check = static function (bool $condition, string $description): void {
    if (!$condition) {
        throw new RuntimeException('Dashboard view regression: ' . $description);
    }
};
$render = static function (?array $stats, array $events, bool $unavailable = false) use ($view, $basePath): string {
    $dashboardStats = $stats;
    $dashboardActivity = $events;
    $dashboardUnavailable = $unavailable;
    ob_start();
    try {
        include $view;
        return (string) ob_get_clean();
    } catch (Throwable $error) {
        ob_end_clean();
        throw $error;
    }
};
$stats = [
    'total_users' => 17,
    'admin_users' => 1,
    'public_users' => 8,
    'municipal_officers' => 1,
    'collectors' => 2,
    'recycler_users' => 5,
    'pending_recyclers' => 4,
    'verified_recyclers' => 2,
    'e_waste_categories' => 6,
    'e_waste_items' => 51,
    'risk_rules' => 0,
];
$events = [
    ['title' => 'Recycler “A” verified', 'group' => 'Recycler Verification', 'class' => 'activity-success', 'icon' => '✓', 'time' => '2026-11-20 11:00 SLT', 'href' => '/admin/recycler-verification/12'],
    ['title' => 'Created <script>alert(1)</script>', 'group' => 'System Audit', 'class' => 'activity-info', 'icon' => '+', 'time' => '', 'href' => 'javascript:alert(1)'],
];
$html = $render($stats, $events);
$check(str_contains($html, '4 Recycler applications are pending verification'), 'pending count visible');
$check(str_contains($html, '/admin/recycler-verification?status=PENDING'), 'pending action points to verified queue route');
$check(substr_count($html, 'Open queue') === 1, 'pending review has one primary queue CTA');
$check(!str_contains($html, 'View queue'), 'duplicate queue CTA removed');
$check(str_contains($html, 'conic-gradient('), 'role chart populated');
$check(str_contains($html, 'Registered accounts by role'), 'role chart clarifies meaning');
$check(str_contains($html, '>17</strong>'), 'role chart total visible');
$check(str_contains($html, '/admin/recycler-verification/12'), 'review points to existing detail route');
$check(!str_contains($html, 'href="javascript:'), 'untrusted activity link denied');
$check(!str_contains($html, '<script>alert(1)</script>'), 'activity title escaped');
$check(str_contains($html, '&lt;script&gt;'), 'escaped text is displayed');
$check(str_contains($html, 'system-overview-panel'), 'overview stays as a distinct full-width section');
$check(str_contains($html, 'quick-actions-panel') && str_contains($html, 'recent-activity-panel'), 'both primary dashboard panels preserved');

$stats['pending_recyclers'] = 0;
$html = $render($stats, []);
$check(str_contains($html, 'No Recycler registrations are currently pending verification.'), 'zero pending state');
$check(str_contains($html, 'No recent administrative activity has been recorded.'), 'empty activity state');

$html = $render(null, [], true);
$check(str_contains($html, 'Pending applications are temporarily unavailable.'), 'unknown counts do not display zero');
$check(str_contains($html, 'User-role breakdown is temporarily unavailable.'), 'unknown role breakdown does not display zero');

echo "PASS: Admin dashboard pending/actions, role chart, links, escaping and empty/error states\n";
