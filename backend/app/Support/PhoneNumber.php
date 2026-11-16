<?php

namespace App\Support;

class PhoneNumber
{
    public static function display(?string $value): string
    {
        $value = trim($value ?? '');
        // Only format recognisable Malaysian numbers. Preserve other countries/extensions.
        if (preg_match('/[^\d\s+()\-.]/u', $value)) return $value;
        $digits = preg_replace('/\D/', '', $value);
        if (str_starts_with($digits, '0060')) $digits = substr($digits, 2);
        if (str_starts_with($digits, '0') && strlen($digits) >= 9 && strlen($digits) <= 11) $digits = '60'.substr($digits, 1);
        if (str_starts_with($digits, '60') && strlen($digits) >= 10 && strlen($digits) <= 12) return '+(60) - '.substr($digits, 2);
        return $value;
    }
}
