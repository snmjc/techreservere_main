<?php

namespace App\Shared\Utils;

final class AppClock
{
    public static function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now', self::timezone());
    }

    public static function timezone(): \DateTimeZone
    {
        $configuredTimezone = trim((string)($_ENV['APP_TIMEZONE'] ?? 'Asia/Manila'));

        try {
            return new \DateTimeZone($configuredTimezone !== '' ? $configuredTimezone : 'Asia/Manila');
        } catch (\Throwable) {
            return new \DateTimeZone('Asia/Manila');
        }
    }
}
