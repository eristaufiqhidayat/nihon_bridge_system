<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Question;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class QuestionBankService
{
    public function search(array $f, int $perPage = 8): LengthAwarePaginator
    {
        return Question::query()
            ->when(($f['lvl'] ?? 'Semua') !== 'Semua', fn ($q) => $q->where('level', $f['lvl']))
            ->when(($f['kat'] ?? 'Semua') !== 'Semua', fn ($q) => $q->where('section', $f['kat']))
            ->when(! empty($f['q']), function ($q) use ($f) {
                $term = '%' . $f['q'] . '%';
                $q->where(fn ($w) => $w->where('question', 'like', $term)->orWhere('code', 'like', $term)->orWhere('options', 'like', $term));
            })
            ->orderBy('code')
            ->paginate($perPage)->withQueryString();
    }

    public function create(array $data, User $by): Question
    {
        $q = Question::create($this->map($data) + ['code' => Question::nextCode(), 'created_by' => $by->id]);
        Activity::log('🗂️', 'ic-bg-blue', ($by->role === 'instruktur' ? $by->sensei_name : $by->name) . " menambah soal {$q->level} ({$q->code})");

        return $q;
    }

    public function update(Question $q, array $data): Question
    {
        $q->update($this->map($data));

        return $q;
    }

    private function map(array $d): array
    {
        return [
            'question' => trim($d['question']),
            'options' => array_map('trim', array_values($d['options'])),
            'answer_key' => (int) $d['answer_key'],
            'section' => $d['section'],
            'level' => $d['level'],
            'explanation' => $d['explanation'] ?? null,
        ];
    }
}
