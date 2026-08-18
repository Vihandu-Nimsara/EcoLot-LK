<?php
declare(strict_types=1);

$environmentValue = static function (string $name): string {
    $value = getenv($name);
    return $value === false ? '' : trim($value);
};

return [
    'endpoint' => 'https://app.notify.lk/api/v1/send',
    'user_id' => $environmentValue('NOTIFY_LK_USER_ID'),
    'api_key' => $environmentValue('NOTIFY_LK_API_KEY'),
    'sender_id' => $environmentValue('NOTIFY_LK_SENDER_ID'),
    'connect_timeout_seconds' => 5,
    'timeout_seconds' => 12,
];
