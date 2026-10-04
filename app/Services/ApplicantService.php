<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Applicant;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class ApplicantService
{
    public function __construct(private NotificationService $notifier)
    {
    }

    /** Simpan pendaftaran dari formulir publik. */
    public function register(array $data, array $files): Applicant
    {
        $a = Applicant::create([
            'reg_no' => Applicant::nextRegNo(),
            'nama' => $data['nama'],
            'nik' => $data['nik'],
            'ttl' => $data['ttl'],
            'asal' => trim(explode(',', $data['ttl'])[0]),
            'usia' => $this->ageFromTtl($data['ttl']),
            'pendidikan' => $data['pend'],
            'program' => $data['prog'],
            'level_bahasa' => $data['level'],
            'referensi' => $data['ref'],
            'hp' => $data['hp'],
            'email' => $data['email'] ?: null,
            'status' => 'baru',
            'berkas' => $files,
            'registered_at' => now()->toDateString(),
        ]);
        $this->notifier->notifyRole('admin', '📝', "Pendaftar baru: {$a->nama} ({$a->reg_no})", route('pendaftaran.index', ['sel' => $a->id]));

        return $a;
    }

    private function ageFromTtl(string $ttl): ?int
    {
        if (preg_match('/(\d{4})\s*$/', $ttl, $m)) {
            return max(0, now()->year - (int) $m[1]);
        }

        return null;
    }

    public function passDocuments(Applicant $a): void
    {
        abort_unless($a->status === 'baru' && ! $a->missingFiles(), 422, 'Berkas belum lengkap.');
        $a->update(['status' => 'berkas']);
    }

    public function scheduleTest(Applicant $a, string $when): void
    {
        abort_unless($a->status === 'berkas', 422);
        $a->update(['status' => 'tes', 'tes_jadwal' => $when]);
    }

    /** Terima pendaftar: buat akun peserta, tempatkan di kelas, kirim tautan aktivasi. */
    public function accept(Applicant $a, Classroom $class): User
    {
        abort_unless($a->status === 'tes', 422);

        return DB::transaction(function () use ($a, $class) {
            $email = $a->email ?: Str::of($a->nama)->lower()->ascii()->replaceMatches('/[^a-z ]/', '')->squish()->replace(' ', '.') . '@nihonbridge.id';
            if (User::where('email', $email)->exists()) {
                $email = Str::before($email, '@') . '.' . Str::lower(Str::random(3)) . '@nihonbridge.id';
            }
            $user = User::create([
                'name' => $a->nama, 'email' => $email, 'password' => Hash::make(Str::random(32)),
                'role' => 'peserta', 'phone' => $a->hp,
            ]);
            Student::create([
                'user_id' => $user->id,
                'classroom_id' => $class->id,
                'nis' => $this->nextNis(),
                'program' => $a->program,
                'stage' => 1,
                'birth_place_date' => $a->ttl,
                'materi_total' => Student::whereHas('classroom', fn ($c) => $c->where('level', $class->level))->max('materi_total') ?? 0,
            ]);
            $a->update(['status' => 'diterima', 'classroom_id' => $class->id, 'user_id' => $user->id]);
            Activity::log('🏫', 'ic-bg-purple', "{$a->nama} diterima di kelas {$class->kode}");
            Password::broker()->sendResetLink(['email' => $user->email]);

            return $user;
        });
    }

    public function reject(Applicant $a, string $reason): void
    {
        abort_unless(in_array($a->status, ['baru', 'berkas', 'tes'], true), 422);
        $a->update(['status' => 'ditolak', 'alasan' => $reason]);
    }

    private function nextNis(): string
    {
        $prefix = 'NB-' . now()->format('y') . '-';
        $max = Student::where('nis', 'like', $prefix . '%')->max('nis');

        return $prefix . str_pad((string) ($max ? ((int) substr($max, -4)) + 1 : 1), 4, '0', STR_PAD_LEFT);
    }
}
