<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Batch;
use App\Models\BatchInstallment;
use App\Models\Payment;
use App\Models\Program;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Data Angkatan (admin): master batch beserta tahapan pembayaran dan tanggal jatuh temponya.
 * Tahapan ini dipakai menu Pembayaran Peserta, Keuangan, dan halaman Pembayaran peserta.
 */
class AngkatanController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $list = Batch::with('program', 'installments')->withCount('classrooms', 'students')
            ->when($q !== '', fn ($b) => $b->where(fn ($w) => $w->where('nama', 'like', "%{$q}%")->orWhere('kode', 'like', "%{$q}%")))
            ->orderByDesc('mulai')->orderByDesc('id')
            ->get();

        return view('admin.angkatan', ['list' => $list, 'q' => $q]);
    }

    public function create(): View
    {
        return $this->form(new Batch(['program_id' => Program::orderBy('nama')->value('id')]));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $batch = DB::transaction(function () use ($data) {
            $batch = Batch::create($data['batch']);
            $this->saveStages($batch, $data['stages']);

            return $batch;
        });
        Activity::log('🗓️', 'ic-bg-blue', "{$batch->nama} ditambahkan dengan " . count($data['stages']) . ' tahap pembayaran');

        return redirect()->route('angkatan.index')->with('toast', "{$batch->nama} ditambahkan");
    }

    public function edit(Batch $batch): View
    {
        return $this->form($batch->load('installments'));
    }

    public function update(Request $request, Batch $batch): RedirectResponse
    {
        $data = $this->validated($request, $batch);

        // Jumlah tahap tidak boleh lebih sedikit dari tahap yang sudah dibayar peserta angkatan ini.
        $maxPaid = (int) Payment::whereIn('student_id', $batch->students()->select('id'))
            ->selectRaw('count(*) as n')->groupBy('student_id')->get()->max('n');
        if (count($data['stages']) < $maxPaid) {
            throw ValidationException::withMessages([
                'jatuh_tempo' => "Sudah ada peserta {$batch->nama} yang membayar {$maxPaid} tahap. Jumlah tahap minimal {$maxPaid}.",
            ]);
        }

        DB::transaction(function () use ($batch, $data) {
            $batch->update($data['batch']);
            $this->saveStages($batch, $data['stages']);
        });

        return redirect()->route('angkatan.edit', $batch)->with('toast', "{$batch->nama} disimpan");
    }

    public function destroy(Batch $batch): RedirectResponse
    {
        $blockers = $batch->deleteBlockers();
        if ($blockers) {
            $rincian = collect($blockers)->map(fn ($n, $label) => "{$n} {$label}")->join(', ', ' dan ');

            return back()->withErrors([
                'hapus' => "{$batch->nama} tidak bisa dihapus karena masih punya {$rincian}. Pindahkan kelas dan peserta ke angkatan lain lebih dulu.",
            ]);
        }

        $batch->delete(); // tahapan pembayaran ikut terhapus (cascade)
        Activity::log('🗑️', 'ic-bg-grey', "{$batch->nama} dihapus");

        return redirect()->route('angkatan.index')->with('toast', "{$batch->nama} dihapus");
    }

    private function form(Batch $batch): View
    {
        $dues = old('jatuh_tempo', $batch->exists
            ? $batch->installments->map(fn ($t) => $t->jatuh_tempo->toDateString())->all()
            : ['']);

        return view('admin.angkatan-form', [
            'batch' => $batch,
            'programs' => Program::orderBy('nama')->get(),
            'dues' => $dues ?: [''],
            'blockers' => $batch->exists ? $batch->deleteBlockers() : [],
        ]);
    }

    /** @return array{batch: array, stages: list<string>} */
    private function validated(Request $request, ?Batch $batch = null): array
    {
        $data = $request->validate([
            'program_id' => ['required', 'exists:programs,id'],
            'kode' => ['required', 'string', 'max:20', Rule::unique('batches')->ignore($batch?->id)],
            'nama' => ['required', 'string', 'max:100'],
            'mulai' => ['nullable', 'date'],
            'selesai' => ['nullable', 'date', 'after_or_equal:mulai'],
            'kuota' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'biaya' => ['nullable', 'integer', 'min:0'],
            'jatuh_tempo' => ['required', 'array', 'min:1', 'max:24'],
            'jatuh_tempo.*' => ['required', 'date'],
        ], [
            'program_id.required' => 'Pilih program kursus angkatan ini.',
            'kode.required' => 'Kode angkatan wajib diisi, mis. 2026-09.',
            'kode.unique' => 'Kode angkatan sudah dipakai.',
            'nama.required' => 'Nama angkatan wajib diisi, mis. Angkatan 5.',
            'selesai.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
            'biaya.integer' => 'Total biaya diisi angka rupiah tanpa titik.',
            'jatuh_tempo.required' => 'Tambahkan minimal satu tahap pembayaran.',
            'jatuh_tempo.min' => 'Tambahkan minimal satu tahap pembayaran.',
            'jatuh_tempo.max' => 'Maksimal 24 tahap pembayaran.',
            'jatuh_tempo.*.required' => 'Isi tanggal jatuh tempo setiap tahap.',
            'jatuh_tempo.*.date' => 'Tanggal jatuh tempo tidak valid.',
        ]);

        $stages = collect($data['jatuh_tempo'])->sort()->values()->all();

        return ['batch' => collect($data)->except('jatuh_tempo')->all(), 'stages' => $stages];
    }

    /** Ganti tahapan pembayaran angkatan dengan daftar tanggal jatuh tempo (urut 1..n). */
    private function saveStages(Batch $batch, array $dues): void
    {
        BatchInstallment::where('batch_id', $batch->id)->delete();
        foreach ($dues as $i => $due) {
            $batch->installments()->create(['tahap' => $i + 1, 'jatuh_tempo' => $due]);
        }
    }
}
