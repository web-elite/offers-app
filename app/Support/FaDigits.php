<?php

namespace App\Support;

class FaDigits
{
    private const MAP = [
        '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴',
        '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹',
    ];

    /** Convert latin digits to Persian digits (۰۱۲۳۴۵۶۷۸۹). */
    public static function convert(string|int|float|null $value): string
    {
        return strtr((string) $value, self::MAP);
    }

    /** Human readable relative time in Persian with Persian digits. */
    public static function diffForHumans(?\DateTimeInterface $date, string $fallback = 'نامشخص'): string
    {
        if ($date === null) {
            return $fallback;
        }

        return self::convert(
            \Illuminate\Support\Carbon::instance($date)->locale('fa')->diffForHumans()
        );
    }
}
