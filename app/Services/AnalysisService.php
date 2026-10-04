<?php

namespace App\Services;

use App\Models\ExamAttempt;
use App\Models\ExamPackage;
use App\Support\Catalog;
use App\Support\Fmt;

/**
 * Analisis butir soal dari percobaan yang sudah selesai pada satu paket & kelas.
 */
class AnalysisService
{
    public function analyse(ExamPackage $package, ?int $classroomId): array
    {
        $questions = $package->snapshot ?? [];
        $attempts = ExamAttempt::where('exam_package_id', $package->id)->where('status', 'selesai')
            ->whereNotNull('answers')
            ->when($classroomId, fn ($q) => $q->whereHas('user.student', fn ($s) => $s->where('classroom_id', $classroomId)))
            ->get();

        // pakai percobaan terakhir tiap peserta
        $latest = $attempts->sortBy('id')->groupBy('user_id')->map->last()->values();
        $n = $latest->count();

        $items = [];
        foreach ($questions as $i => $q) {
            $dist = [0, 0, 0, 0];
            foreach ($latest as $a) {
                $ans = $a->answers[$i] ?? null;
                if ($ans !== null) {
                    $dist[(int) $ans]++;
                }
            }
            $pct = array_map(fn ($c) => Fmt::pct($c, $n), $dist);
            $wrong = array_values(array_diff([0, 1, 2, 3], [$q['key']]));
            usort($wrong, fn ($x, $y) => $dist[$y] <=> $dist[$x]);
            $items[] = ['i' => $i, 'q' => $q, 'p' => $pct[$q['key']], 'dist' => $pct, 'top' => $wrong[0]];
        }

        $bins = [['< 40', 0], ['40–59', 0], ['60–79', 0], ['80–100', 0]];
        foreach ($latest as $a) {
            $t = $a->total;
            $bins[$t < 40 ? 0 : ($t < 60 ? 1 : ($t < 80 ? 2 : 3))][1]++;
        }

        $secAvg = [];
        foreach (array_keys(Catalog::SECTIONS) as $s) {
            $list = array_filter($items, fn ($x) => $x['q']['section'] === $s);
            $secAvg[$s] = $list ? (int) round(array_sum(array_column($list, 'p')) / count($list)) : 0;
        }

        $hard = $items;
        usort($hard, fn ($a, $b) => $a['p'] <=> $b['p']);

        return [
            'participants' => $n,
            'below' => $bins[0][1] + $bins[1][1],
            'bins' => $bins,
            'secAvg' => $secAvg,
            'hard' => $hard,
            'items' => $items,
        ];
    }
}
