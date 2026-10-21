<?php

namespace App\Helpers;

use Carbon\Carbon;

class FormatHelper
{
    public static function formatRupiah($value): string
    {
        if ($value === null || $value === '') {
            return 'Rp 0';
        }
        return 'Rp ' . number_format((float) $value, 0, ',', '.');
    }

    public static function rupiah($value): string
    {
        return self::formatRupiah($value);
    }

    public static function formatNip(?string $nip): string
    {
        if (empty($nip)) {
            return '-';
        }
        $digits = preg_replace('/\D/', '', $nip);
        if (strlen($digits) === 18) {
            // 8-6-1-3 digit (contoh: 19820608 200901 2 001)
            return substr($digits, 0, 8) . ' ' . substr($digits, 8, 6) . ' ' . substr($digits, 14, 1) . ' ' . substr($digits, 15, 3);
        }
        return $nip;
    }

    public static function nip(?string $nip): string
    {
        return self::formatNip($nip);
    }

    public static function formatDateIndo($date): string
    {
        if (empty($date)) {
            return '-';
        }
        $c = Carbon::parse($date)->timezone('Asia/Makassar');
        $months = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember'
        ];
        return $c->day . ' ' . ($months[$c->month] ?? $c->format('F')) . ' ' . $c->year;
    }

    public static function getMonthName(int $month): string
    {
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        return $months[$month] ?? '';
    }

    public static function indonesianDate($date, bool $withTime = false): string
    {
        if (empty($date)) {
            return '-';
        }
        $c = Carbon::parse($date)->timezone('Asia/Makassar');
        $dateStr = self::formatDateIndo($date);
        if ($withTime) {
            return $dateStr . ', ' . $c->format('H:i') . ' WITA';
        }
        return $dateStr;
    }

    public static function toRoman(int $number): string
    {
        $map = [
            'M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400,
            'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40,
            'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1
        ];
        $result = '';
        foreach ($map as $roman => $value) {
            $matches = intval($number / $value);
            $result .= str_repeat($roman, $matches);
            $number = $number % $value;
        }
        return $result;
    }
}
