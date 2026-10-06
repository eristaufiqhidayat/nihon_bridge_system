<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Batch;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use App\Support\Catalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Data Peserta (admin): daftar, tambah, ubah biodata & keikutsertaan, hapus.
 * Struktur: Program → Batch (Angkatan) → Kelas → Peserta.
 */
class PesertaController extends Controller
{
    private const STUDENT_FIELDS = [
        'nis', 'program', 'batch_id', 'classroom_id', 'class_mode', 'class_start', 'class_end', 'enrollment_status', 'total_fee',
        'birth_place', 'birth_date', 'gender', 'height_cm', 'religion', 'marital_status', 'passport_no',
        'address_ktp', 'address_domicile', 'guardian_contact', 'note',
    ];

    public function index(Request $request): View
    {
        $f = [
            'q' => trim((string) $request->query('q', '')),
            'angkatan' => (int) $request->query('angkatan') ?: null,
            'kelas' => (int) $request->query('kelas') ?: null,
            'status' => array_key_exists($request->query('status'), Catalog::ENROLLMENT) ? $request->query('status') : null,
        ];

        $list = Student::with('user', 'batch', 'classroom')
            ->when($f['q'] !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('nis', 'like', "%{$f['q']}%")
                ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$f['q']}%")->orWhere('email', 'like', "%{$f['q']}%"))))
            ->when($f['angkatan'], fn ($q, $id) => $q->where('batch_id', $id))
            ->when($f['kelas'], fn ($q, $id) => $q->where('classroom_id', $id))
            ->when($f['status'], fn ($q, $st) => $q->where('enrollment_status', $st))
            ->orderBy('nis')
            ->paginate(25)->withQueryString();

        return view('admin.peserta', [
            'list' => $list,
            'f' => $f,
            'total' => Student::count(),
            'batches' => Batch::orderBy('mulai')->get(),
            'classes' => Classroom::orderBy('kode')->get(),
        ]);
    }

    public function create(): View
    {
        return $this->form(new Student([
            'program' => array_key_first(Catalog::PROGRAMS),
            'class_mode' => 'tatap_muka',
            'enrollment_status' => 'aktif',
        ]));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $student = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'], 'email' => $data['email'], 'phone' => $data['phone'] ?? null,
                'role' => 'peserta', 'password' => Hash::make(Str::random(32)), 'is_active' => $data['is_active'],
            ]);
            $class = isset($data['classroom_id']) ? Classroom::find($data['classroom_id']) : null;

            return $user->student()->create([
                ...collect($data)->only(self::STUDENT_FIELDS)->all(),
                'nis' => $data['nis'] ?: Student::nextNis(),
                'stage' => 1,
                'materi_total' => $class ? (int) Student::whereHas('classroom', fn ($c) => $c->where('level', $class->level))->max('materi_total') : 0,
            ]);
        });
        Activity::log('👤', 'ic-bg-blue', "{$student->user->name} ditambahkan sebagai peserta");
        Password::broker()->sendResetLink(['email' => $student->user->email]);

        return redirect()->route('peserta-admin.index')->with('toast', "{$student->user->name} ditambahkan. Tautan aktivasi dikirim ke email.");
    }

    public function edit(Student $student): View
    {
        return $this->form($student->load('user'));
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        $data = $this->validated($request, $student);

        DB::transaction(function () use ($student, $data) {
            $student->user->update(collect($data)->only('name', 'email', 'phone', 'is_active')->all());
            $student->update([...collect($data)->only(self::STUDENT_FIELDS)->all(), 'nis' => $data['nis'] ?: $student->nis]);
        });

        return redirect()->route('peserta-admin.edit', $student)->with('toast', 'Data ' . $student->user->name . ' disimpan');
    }

    public function destroy(Student $student): RedirectResponse
    {
        $name = $student->user->name;
        $blockers = $student->deleteBlockers();
        if ($blockers) {
            $rincian = collect($blockers)->map(fn ($n, $label) => "{$n} {$label}")->join(', ', ' dan ');

            return back()->withErrors([
                'hapus' => "{$name} tidak bisa dihapus karena masih terhubung dengan data lain: {$rincian}. "
                    . 'Hapus atau pindahkan data tersebut lebih dulu, atau ubah status peserta menjadi "Keluar" dan nonaktifkan akunnya.',
            ]);
        }

        // Akun login ikut dihapus; baris peserta terhapus lewat cascade users → students.
        DB::transaction(fn () => $student->user->delete());
        Activity::log('🗑️', 'ic-bg-grey', "Data peserta {$name} dihapus");

        return redirect()->route('peserta-admin.index')->with('toast', "Data {$name} dihapus");
    }

    private function form(Student $student): View
    {
        return view('admin.peserta-form', [
            'student' => $student,
            'user' => $student->user ?? new User(['is_active' => true]),
            'batches' => Batch::with('program')->orderBy('mulai')->get(),
            'classes' => Classroom::with('batch')->orderBy('kode')->get(),
            'blockers' => $student->exists ? $student->deleteBlockers() : [],
        ]);
    }

    private function validated(Request $request, ?Student $student = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', Rule::unique('users')->ignore($student?->user_id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'is_active' => ['boolean'],
            'nis' => ['nullable', 'string', 'max:20', Rule::unique('students')->ignore($student?->id)],
            'program' => ['required', Rule::in(array_keys(Catalog::PROGRAMS))],
            'batch_id' => ['nullable', 'exists:batches,id'],
            'classroom_id' => ['nullable', 'exists:classrooms,id'],
            'class_mode' => ['required', Rule::in(array_keys(Catalog::CLASS_MODES))],
            'class_start' => ['nullable', 'date'],
            'class_end' => ['nullable', 'date', 'after_or_equal:class_start'],
            'enrollment_status' => ['required', Rule::in(array_keys(Catalog::ENROLLMENT))],
            'total_fee' => ['nullable', 'integer', 'min:0'],
            'birth_place' => ['nullable', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', Rule::in(array_keys(Catalog::GENDERS))],
            'height_cm' => ['nullable', 'integer', 'between:100,230'],
            'religion' => ['nullable', Rule::in(Catalog::RELIGIONS)],
            'marital_status' => ['nullable', Rule::in(array_keys(Catalog::MARITAL))],
            'passport_no' => ['nullable', 'string', 'max:20'],
            'address_ktp' => ['nullable', 'string', 'max:255'],
            'address_domicile' => ['nullable', 'string', 'max:255'],
            'guardian_contact' => ['nullable', 'string', 'max:120'],
            'note' => ['nullable', 'string', 'max:255'],
        ], [
            'name.required' => 'Nama peserta wajib diisi.',
            'email.required' => 'Email wajib diisi sebagai alamat login.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah dipakai akun lain.',
            'nis.unique' => 'NIS sudah dipakai peserta lain.',
            'class_end.after_or_equal' => 'Tanggal selesai kelas tidak boleh sebelum tanggal mulai.',
            'birth_date.before' => 'Tanggal lahir harus sebelum hari ini.',
            'height_cm.between' => 'Tinggi badan diisi dalam cm (100–230).',
            'total_fee.integer' => 'Total biaya diisi angka rupiah tanpa titik.',
            'total_fee.min' => 'Total biaya tidak boleh negatif.',
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $data['nis'] = trim((string) ($data['nis'] ?? ''));

        // Kelas harus berada di angkatan yang dipilih; bila angkatan kosong, ikuti angkatan kelas.
        if (! empty($data['classroom_id'])) {
            $class = Classroom::with('batch')->find($data['classroom_id']);
            if (empty($data['batch_id'])) {
                $data['batch_id'] = $class->batch_id;
            } elseif ($class->batch_id && (int) $class->batch_id !== (int) $data['batch_id']) {
                throw ValidationException::withMessages([
                    'classroom_id' => "Kelas {$class->kode} termasuk {$class->batch->nama}, bukan angkatan yang dipilih.",
                ]);
            }
        }

        return $data;
    }
}
