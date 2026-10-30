<?php
declare(strict_types=1);

// CLI only: never expose maintenance commands as web endpoints.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once dirname(__DIR__) . '/app/autoload.php';
try {
    (new MonthlyCampaign())->closeExpired();
    echo "Expired campaign scheduling closed (Asia/Colombo).\n";
} catch (Throwable $error) {
    error_log('Campaign expiry job failed: ' . $error::class);
    fwrite(STDERR, "Campaign expiry failed. Check database connectivity and configuration.\n");
    exit(1);
}
