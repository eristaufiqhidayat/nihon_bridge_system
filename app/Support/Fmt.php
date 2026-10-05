<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Format tampilan berbahasa Indonesia (tanggal, rupiah, persen).
 */
class Fmt
{
    private const MONTHS = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    private const MONTHS_SHORT = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    private const DAYS = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    private const DAYS_SHORT = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];

    public static function rupiah(int|float|null $n): string
    {
        return 'Rp' . number_format((float) $n, 0, ',', '.');
    }

    public static function pct(int|float $a, int|float $b): int
    {
        return $b ? (int) round($a / $b * 100) : 0;
    }

    private static function c($d): ?CarbonInterface
    {
        if ($d === null || $d === '') {
            return null;
        }

        return $d instanceof CarbonInterface ? $d : Carbon::parse($d);
    }

    /** 28 Sep 2026 */
    public static function date($d): string
    {
        $d = self::c($d);

        return $d ? $d->day . ' ' . self::MONTHS_SHORT[$d->month - 1] . ' ' . $d->year : '–';
    }

    /** 28 September 2026 */
    public static function dateLong($d): string
    {
        $d = self::c($d);

        return $d ? $d->day . ' ' . self::MONTHS[$d->month - 1] . ' ' . $d->year : '–';
    }

    /** Senin, 28 September 2026 */
    public static function dayDateLong($d): string
    {
        $d = self::c($d);

        return $d ? self::DAYS[$d->dayOfWeek] . ', ' . self::dateLong($d) : '–';
    }

    /** Senin, 28 Sep 2026 */
    public static function dayDate($d): string
    {
        $d = self::c($d);

        return $d ? self::DAYS[$d->dayOfWeek] . ', ' . self::date($d) : '–';
    }

    /** Sen 28 */
    public static function dayShort($d): string
    {
        $d = self::c($d);

        return self::DAYS_SHORT[$d->dayOfWeek] . ' ' . $d->day;
    }

    /** Sep 2026 */
    public static function monthYear($d): string
    {
        $d = self::c($d);

        return $d ? self::MONTHS_SHORT[$d->month - 1] . ' ' . $d->year : '–';
    }

    /**
     * Urai tanggal berbahasa Indonesia ("14 Mei 2002", "30 0ktober 2002", "7 Des 2004") atau ISO ke Y-m-d.
     * Mengembalikan null bila tidak lengkap (mis. hanya tahun) atau tidak bisa diurai.
     */
    public static function parseDate(?string $text): ?string
    {
        $text = trim((string) $text);
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $text, $m)) {
            return checkdate((int) $m[2], (int) $m[3], (int) $m[1]) ? "$m[1]-$m[2]-$m[3]" : null;
        }
        if (! preg_match('/^(\d{1,2})\s+([[:alpha:]0]+)\.?\s+(\d{4})$/u', $text, $m)) {
            return null;
        }
        $name = mb_strtolower(preg_replace('/^0/', 'o', $m[2])); // typo angka nol: "0ktober"
        foreach (self::MONTHS as $i => $month) {
            if (str_starts_with(mb_strtolower($month), mb_substr($name, 0, 3)) && mb_strlen($name) >= 3) {
                return checkdate($i + 1, (int) $m[1], (int) $m[3]) ? sprintf('%04d-%02d-%02d', $m[3], $i + 1, $m[1]) : null;
            }
        }

        return null;
    }

    /** Waktu relatif ringkas untuk pesan & notifikasi: "Baru saja", "10:20", "Kemarin, 19:30", "Sab, 08:12", "25 Sep, 10:00". */
    public static function chatTime($d): string
    {
        $d = self::c($d);
        if (! $d) {
            return '';
        }
        $now = Carbon::now();
        if ($d->diffInMinutes($now) < 2) {
            return 'Baru saja';
        }
        if ($d->isSameDay($now)) {
            return 'Hari ini, ' . $d->format('H:i');
        }
        if ($d->isSameDay($now->copy()->subDay())) {
            return 'Kemarin, ' . $d->format('H:i');
        }
        if ($d->greaterThan($now->copy()->subDays(6)->startOfDay())) {
            return self::DAYS_SHORT[$d->dayOfWeek] . ', ' . $d->format('H:i');
        }

        return $d->day . ' ' . self::MONTHS_SHORT[$d->month - 1] . ', ' . $d->format('H:i');
    }

    /** "Hari ini", "Kemarin", "2 hari lalu", "26 Sep". */
    public static function ago($d): string
    {
        $d = self::c($d);
        if (! $d) {
            return '';
        }
        $days = (int) $d->copy()->startOfDay()->diffInDays(Carbon::now()->startOfDay());
        if ($days === 0) {
            return 'Hari ini';
        }
        if ($days === 1) {
            return 'Kemarin';
        }
        if ($days < 7) {
            return $days . ' hari lalu';
        }

        return $d->day . ' ' . self::MONTHS_SHORT[$d->month - 1];
    }

    public static function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim(preg_replace('/[^\pL\s]/u', '', $name)));
        $init = mb_substr($parts[0] ?? '', 0, 1) . mb_substr($parts[1] ?? '', 0, 1);

        return mb_strtoupper($init ?: mb_substr($name, 0, 2));
    }

    public static function duration(int $seconds): string
    {
        $seconds = max(0, $seconds);

        return sprintf('%02d:%02d:%02d', intdiv($seconds, 3600), intdiv($seconds % 3600, 60), $seconds % 60);
    }
}
