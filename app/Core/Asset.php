<?php
declare(strict_types=1);

final class Asset
{
    /** Modification timestamp for a trusted, repository-relative asset path. */
    public static function version(string $path): string
    {
        return (string) filemtime(APP_ROOT . '/public/assets/' . $path);
    }
}
