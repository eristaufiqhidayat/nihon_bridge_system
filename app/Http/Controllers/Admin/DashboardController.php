<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Applicant;
use App\Models\Classroom;
use App\Models\ExamPackage;
use App\Models\Question;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use App\Services\FinanceReportService;
use App\Services\PaymentService;
use App\Support\Catalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request, PaymentService $payments, FinanceReportService $finance): View
    {
        $students = Student::with(['documents', ...PaymentService::RELATIONS])->get();

        return view('admin.dashboard', [
            'stats' => [
                'peserta' => User::where('role', 'peserta')->where('is_active', true)->count(),
                'kelas' => Classroom::count(),
                'instruktur' => User::where('role', 'instruktur')->where('is_active', true)->count(),
                'soal' => Question::count(),
                'dokumen' => $students->sum(fn ($s) => collect(Catalog::DOCS)->filter(fn ($d) => $s->docStatus($d) === 'proses')->count()),
            ],
            'users' => User::with('student.classroom', 'waliClasses', 'roleInfo')->orderByRaw("CASE role WHEN 'peserta' THEN 0 WHEN 'instruktur' THEN 1 WHEN 'admin' THEN 2 ELSE 3 END")->orderBy('id')->get(),
            'activities' => Activity::latest()->limit(5)->get(),
            'todo' => [
                'baru' => Applicant::where('status', 'baru')->count(),
                'telat' => $students->filter(fn ($s) => $payments->overdue($s) > 0)->count(),
                'draf' => ExamPackage::where('status', 'Draf')->count(),
            ],
            'classes' => Classroom::orderBy('kode')->get(),
            'roles' => Role::orderByDesc('is_system')->orderBy('id')->get(),
            'fin' => $finance->report($request->query('ktahun'), $request->query('kbulan')),
        ]);
    }

    public function storeUser(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'unique:users,email'],
            'role' => ['required', Rule::exists('roles', 'key')],
            'classroom_id' => ['nullable', 'exists:classrooms,id'],
        ], [
            'name.required' => 'Isi nama dan email yang valid.',
            'email.required' => 'Isi nama dan email yang valid.',
            'email.email' => 'Isi nama dan email yang valid.',
            'email.unique' => 'Email sudah terdaftar.',
        ]);

        $user = User::create([
            'name' => $data['name'], 'email' => $data['email'], 'role' => $data['role'],
            'password' => Hash::make(Str::random(32)),
        ]);
        if ($data['role'] === 'peserta') {
            $class = Classroom::find($data['classroom_id']);
            $user->student()->create([
                'classroom_id' => $class?->id,
                'nis' => 'NB-' . now()->format('y') . '-' . str_pad((string) ($user->id + 100), 4, '0', STR_PAD_LEFT),
                'program' => array_key_first(Catalog::PROGRAMS),
                'stage' => 1,
                'materi_total' => $class ? (int) Student::whereHas('classroom', fn ($c) => $c->where('level', $class->level))->max('materi_total') : 0,
            ]);
        }
        Password::broker()->sendResetLink(['email' => $user->email]);

        return back()->with('toast', "{$user->name} ditambahkan. Tautan aktivasi dikirim ke email.");
    }

    /** Ganti peran pengguna non-peserta. Peserta dikelola lewat Data Peserta karena terikat data siswa. */
    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate(['role' => ['required', Rule::exists('roles', 'key')->whereNot('key', 'peserta')]], [
            'role.exists' => 'Pilih peran yang tersedia. Peran Peserta diatur lewat Data Peserta.',
        ]);
        abort_if($user->role === 'peserta', 422, 'Peran peserta diatur lewat Data Peserta.');
        abort_if($user->id === $request->user()->id, 422, 'Tidak bisa mengubah peran akun sendiri.');

        $user->update(['role' => $data['role']]);

        return back()->with('toast', "Peran {$user->name} diubah menjadi {$user->fresh()->role_label}");
    }

    public function toggleUser(Request $request, User $user): RedirectResponse
    {
        abort_if($user->id === $request->user()->id, 422, 'Tidak bisa menonaktifkan akun sendiri.');
        $user->update(['is_active' => $request->boolean('active')]);

        return back()->with('toast', $user->name . ($user->is_active ? ' diaktifkan' : ' dinonaktifkan'));
    }
}
