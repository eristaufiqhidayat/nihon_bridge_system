<?php

namespace App\Services;

use App\Models\Classroom;
use App\Models\ExamPackage;
use App\Models\ExamSchedule;
use App\Models\Question;
use App\Support\Catalog;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PackageService
{
    public function __construct(private NotificationService $notifier)
    {
    }

    public function createDraft(string $level = 'N4'): ExamPackage
    {
        $n = ExamPackage::where('judul', 'like', "Simulasi JLPT $level · Paket %")->count() + 1;

        return ExamPackage::create([
            'judul' => "Simulasi JLPT $level · Paket $n",
            'level' => $level,
            'status' => 'Draf',
            'composition' => Catalog::COMPOSE,
        ]);
    }

    /** Jumlah soal terpilih per bagian. */
    public function counts(ExamPackage $p): array
    {
        $c = array_fill_keys(array_keys(Catalog::SECTIONS), 0);
        foreach ($p->picks as $q) {
            $c[$q->section]++;
        }

        return $c;
    }

    public function compositionOk(ExamPackage $p): bool
    {
        return $this->counts($p) == ($p->composition ?? Catalog::COMPOSE);
    }

    public function togglePick(ExamPackage $p, Question $q, bool $on): void
    {
        $this->assertDraft($p);
        $on ? $p->picks()->syncWithoutDetaching([$q->id => ['sort_order' => $p->picks()->count()]]) : $p->picks()->detach($q->id);
    }

    public function autoPick(ExamPackage $p): void
    {
        $this->assertDraft($p);
        $ids = [];
        foreach (($p->composition ?? Catalog::COMPOSE) as $sec => $n) {
            $ids = array_merge($ids, Question::where('level', $p->level)->where('section', $sec)->orderBy('code')->limit($n)->pluck('id')->all());
        }
        $p->picks()->sync(collect($ids)->mapWithKeys(fn ($id, $i) => [$id => ['sort_order' => $i]])->all());
    }

    /** Terbitkan: kunci isi paket dengan menyimpan salinan soal. */
    public function publish(ExamPackage $p): void
    {
        $this->assertDraft($p);
        $p->load('picks');
        if (! $this->compositionOk($p)) {
            throw ValidationException::withMessages(['paket' => 'Jumlah soal tiap bagian belum sesuai komposisi.']);
        }
        $order = array_keys(Catalog::SECTIONS);
        $snapshot = $p->picks->sortBy(fn ($q) => [array_search($q->section, $order), $q->pivot->sort_order])
            ->map->toSnapshot()->values()->all();

        $p->update(['status' => 'Terbit', 'published_at' => now()->toDateString(), 'snapshot' => $snapshot, 'question_count' => count($snapshot)]);
    }

    public function schedule(ExamPackage $p, array $data): ExamSchedule
    {
        if (! $p->isPublished()) {
            throw ValidationException::withMessages(['paket' => 'Paket draf belum bisa dijadwalkan.']);
        }

        return DB::transaction(function () use ($p, $data) {
            $s = ExamSchedule::create([
                'exam_package_id' => $p->id,
                'classroom_id' => $data['classroom_id'],
                'mode' => 'cbt',
                'opens_at' => $data['opens_at'],
                'closes_at' => $data['closes_at'],
                'duration' => $data['duration'],
            ]);
            $class = Classroom::find($data['classroom_id']);
            $this->notifier->notify($class->students()->with('user')->get()->pluck('user'), '📝',
                "Ujian baru: {$p->judul} dibuka " . \App\Support\Fmt::date($s->opens_at), route('ujian.index'));

            return $s;
        });
    }

    private function assertDraft(ExamPackage $p): void
    {
        if ($p->isPublished()) {
            throw ValidationException::withMessages(['paket' => 'Paket sudah terbit dan terkunci.']);
        }
    }
}
