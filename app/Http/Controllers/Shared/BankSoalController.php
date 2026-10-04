<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Services\QuestionBankService;
use App\Support\Catalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BankSoalController extends Controller
{
    public function __construct(private QuestionBankService $bank)
    {
    }

    public function index(Request $request): View
    {
        $f = [
            'lvl' => $request->query('lvl', 'Semua'),
            'kat' => $request->query('kat', 'Semua'),
            'q' => trim((string) $request->query('q', '')),
        ];

        return view('shared.banksoal', [
            'f' => $f,
            'rows' => $this->bank->search($f),
            'total' => Question::count(),
            'filtered' => $f['lvl'] !== 'Semua' || $f['kat'] !== 'Semua' || $f['q'] !== '',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $q = $this->bank->create($this->validated($request), $request->user());
        $last = (int) ceil(Question::count() / 8);

        return redirect()->route('banksoal.index', ['page' => $last])->with('toast', "Soal {$q->code} ditambahkan");
    }

    public function update(Request $request, Question $question): RedirectResponse
    {
        $this->bank->update($question, $this->validated($request));

        return back()->with('toast', "Soal {$question->code} diperbarui");
    }

    public function destroy(Question $question): RedirectResponse
    {
        $question->delete();

        return back()->with('toast', "Soal {$question->code} dihapus");
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'question' => ['required', 'string'],
            'options' => ['required', 'array', 'size:4'],
            'options.*' => ['required', 'string', 'distinct'],
            'answer_key' => ['required', 'integer', 'between:0,3'],
            'section' => ['required', Rule::in(array_keys(Catalog::SECTIONS))],
            'level' => ['required', Rule::in(Catalog::LEVELS)],
            'explanation' => ['nullable', 'string'],
        ], [
            'question.required' => 'Pertanyaan dan keempat opsi wajib diisi.',
            'options.*.required' => 'Pertanyaan dan keempat opsi wajib diisi.',
            'options.*.distinct' => 'Setiap opsi harus berbeda.',
        ]);
    }
}
