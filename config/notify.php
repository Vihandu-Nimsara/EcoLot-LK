<?php
declare(strict_types=1);

$environmentValue = static function (string $name): string {
    $value = getenv($name);

    return is_string($value) ? trim($value) : '';
};

$config = [
    'endpoint' => 'https://app.notify.lk/api/v1/send',
    'user_id' => $environmentValue('NOTIFY_USER_ID'),
    'api_key' => $environmentValue('NOTIFY_API_KEY'),
    'sender_id' => $environmentValue('NOTIFY_SENDER_ID'),
    'connect_timeout_seconds' => 5,
    'request_timeout_seconds' => 10,
];

$localConfigFile = __DIR__ . '/notify.local.php';

if (is_file($localConfigFile)) {
    $localConfig = require $localConfigFile;

    if (!is_array($localConfig)) {
        throw new RuntimeException(
            'The private Notify.lk configuration must return an array.'
        );
    }

    $config = array_replace($config, $localConfig);
}

return $config;
