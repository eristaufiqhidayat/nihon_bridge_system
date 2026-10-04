<?php

namespace App\Services;

use App\Models\PassRate;
use App\Models\YearlyStat;

class ReportService
{
    public function years(): array
    {
        return YearlyStat::whereNotNull('levels')->orderByDesc('year')->pluck('year')->all();
    }

    public function forYear(int $year): array
    {
        $cur = YearlyStat::where('year', $year)->firstOrFail();
        $prev = YearlyStat::where('year', $year - 1)->first();

        return ['cur' => $cur, 'prev' => $prev];
    }

    /** @return array<string, array<int,int>> level => [tahun => persen] */
    public function trend(): array
    {
        $out = [];
        foreach (PassRate::orderBy('year')->get() as $r) {
            $out[$r->level][$r->year] = $r->rate;
        }

        return $out;
    }

    public static function trendPct(int $cur, ?int $prev): ?int
    {
        return $prev ? (int) round(($cur - $prev) / $prev * 100) : null;
    }

    /** CSV ringkasan untuk dibuka di Excel. */
    public function csv(int $year): string
    {
        ['cur' => $c, 'prev' => $p] = $this->forYear($year);
        $rows = [
            ['Laporan LPK Nihon Bridge', $year],
            [],
            ['Indikator', $year, $year - 1],
            ['Peserta aktif', $c->peserta, $p?->peserta],
            ['Kelas aktif', $c->kelas, $p?->kelas],
            ['Lulus JLPT', $c->lulus, $p?->lulus],
            ['Siap berangkat', $c->berangkat, $p?->berangkat],
            [],
            ['Peserta per level', $year],
        ];
        foreach ($c->levels ?? [] as $lv => $n) {
            $rows[] = [$lv, $n];
        }
        $rows[] = [];
        $trend = $this->trend();
        $years = collect($trend)->flatMap(fn ($v) => array_keys($v))->unique()->sort()->values()->all();
        $rows[] = array_merge(['Tingkat kelulusan (%)'], $years);
        foreach ($trend as $lv => $vals) {
            $rows[] = array_merge([$lv], array_map(fn ($y) => $vals[$y] ?? '', $years));
        }

        $fh = fopen('php://temp', 'r+');
        fwrite($fh, "\xEF\xBB\xBF");
        foreach ($rows as $r) {
            fputcsv($fh, $r, ';');
        }
        rewind($fh);

        return stream_get_contents($fh);
    }
}
