<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/autoload.php';
require_once __DIR__ . '/TestDatabase.php';

function verify(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}
