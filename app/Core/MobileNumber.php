<?php
declare(strict_types=1);

final class MobileNumber
{
    public static function normalize(string $mobile): ?string
    {
        $mobile = trim($mobile);

        // Allow common formatting characters only.
        $mobile = preg_replace('/[\s\-()]/', '', $mobile);

        if ($mobile === null || $mobile === '') {
            return null;
        }

        // Remove optional leading "+".
        if (str_starts_with($mobile, '+')) {
            $mobile = substr($mobile, 1);
        }

        // Convert 0771234567 into 94771234567.
        if (preg_match('/^07\d{8}$/', $mobile) === 1) {
            $mobile = '94' . substr($mobile, 1);
        }

        // Final stored format: 947XXXXXXXX.
        if (preg_match('/^947\d{8}$/', $mobile) !== 1) {
            return null;
        }

        return $mobile;
    }
}