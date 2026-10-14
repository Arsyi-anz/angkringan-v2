<?php

namespace App\Support;

class PhoneNumber
{
    /**
     * Normalisasi nomor WhatsApp ke format 628xxxxxxxx.
     */
    public static function normalize(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        $value = trim($raw);
        $digits = preg_replace('/\D+/', '', $value);

        if ($digits === '') {
            return '';
        }

        if (str_starts_with($digits, '62')) {
            return $digits;
        }

        if (str_starts_with($digits, '0')) {
            return '62'.substr($digits, 1);
        }

        if (str_starts_with($digits, '8')) {
            return '62'.$digits;
        }

        return $digits;
    }

    public static function isValid(?string $normalized): bool
    {
        return is_string($normalized) && (bool) preg_match('/^628[0-9]{6,12}$/', $normalized);
    }
}
