<?php

namespace Tests\Feature;

use App\Models\Applicant;
use App\Models\Classroom;
use App\Models\ExamAttempt;
use App\Models\ExamPackage;
use App\Models\ExamSchedule;
use App\Models\Payment;
use App\Models\Question;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Uji alur utama: login, ujian CBT, pendaftaran, keuangan, paket, kehadiran, bank soal.
 */
class AlurTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_login_dan_peran(): void
    {
        $this->post('/login', ['email' => 'ahmad@nihonbridge.id', 'password' => 'salah'])->assertSessionHasErrors();
        $u = User::where('role', 'peserta')->first();
        $this->post('/login', ['email' => $u->email, 'password' => 'sakura2026'])->assertRedirect(route('dashboard'));
        $this->post('/logout');
        $this->post('/login/demo/admin')->assertRedirect(route('admin.dashboard'));
    }

    public function test_ujian_cbt_mulai_simpan_kirim(): void
    {
        $sch = ExamSchedule::with('package')->get()->first(fn ($s) => $s->isOpen());
        $this->assertNotNull($sch, 'Seeder harus punya ujian yang sedang dibuka');
        $user = Student::where('classroom_id', $sch->classroom_id)->first()->user;
        ExamAttempt::where('user_id', $user->id)->where('status', 'berjalan')->delete();
        $this->actingAs($user);

        $this->post(route('ujian.start', $sch))->assertRedirect();
        $attempt = ExamAttempt::where('user_id', $user->id)->latest('id')->first();
        $this->assertSame('berjalan', $attempt->status);
        $this->get(route('ujian.cbt', $attempt))->assertOk();

        $snap = $sch->package->snapshot;
        $answers = collect($snap)->map(fn ($q) => $q['key'])->all();
        $this->postJson(route('ujian.save', $attempt), ['answers' => $answers, 'flags' => [], 'current' => 3])->assertOk()->assertJson(['ok' => true]);
        $this->post(route('ujian.submit', $attempt))->assertRedirect(route('hasil.index', $attempt));

        $attempt->refresh();
        $this->assertSame(100, (int) $attempt->total);
        $this->assertNotNull($attempt->certificate);
        $this->get(route('hasil.index', [$attempt, 'pembahasan' => 1]))->assertOk()->assertSee('Selamat');

        // Peserta lain tidak bisa mengakses attempt ini
        $this->actingAs(User::where('role', 'peserta')->where('id', '!=', $user->id)->first());
        $this->postJson(route('ujian.save', $attempt), ['answers' => []])->assertForbidden();
    }

    public function test_pendaftaran_publik_empat_langkah(): void
    {
        Storage::fake();
        $this->post('/daftar', ['nama' => 'Rina Uji Coba', 'nik' => '123', 'ttl' => 'Cirebon, 3 Maret 2005', 'hp' => '0812'])->assertSessionHasErrors('nik');
        $step1 = ['nama' => 'Rina Uji Coba', 'nik' => '3209123456780001', 'ttl' => 'Cirebon, 3 Maret 2005', 'hp' => '0812', 'pend' => 'SMA/SMK'];
        $this->post('/daftar', $step1)->assertSessionHasErrors('email');
        $this->post('/daftar', $step1 + ['email' => 'ahmad.fauzi@nihonbridge.id'])->assertSessionHasErrors('email');
        $this->post('/daftar', $step1 + ['email' => 'Rina.Uji@Email.com '])->assertRedirect('/daftar');
        $this->post('/daftar', ['prog' => 'Tokutei Ginou · Kaigo', 'level' => 'Setara N5', 'ref' => 'Sekolah'])->assertRedirect('/daftar');
        $this->post('/daftar', ['aksi' => 'lanjut'])->assertSessionHasErrors('berkas');
        $files = collect(['ktp', 'ijazah', 'foto', 'izin'])->mapWithKeys(fn ($k) => [$k => UploadedFile::fake()->image("$k.jpg")])->all();
        $this->post('/daftar', ['aksi' => 'unggah', 'berkas' => $files])->assertRedirect('/daftar');
        $this->get('/daftar')->assertSee('ktp.jpg');
        $this->post('/daftar', ['aksi' => 'lanjut'])->assertRedirect('/daftar');
        $this->post('/daftar', [])->assertSessionHasErrors('setuju');
        $this->post('/daftar', ['setuju' => '1'])->assertRedirect(route('daftar.selesai'));
        $a = Applicant::where('nama', 'Rina Uji Coba')->latest('id')->firstOrFail();
        $this->get(route('daftar.selesai'))->assertOk()->assertSee($a->reg_no);

        // Admin memproses pendaftar sampai diterima
        $this->actingAs(User::where('role', 'admin')->first());
        $this->get(route('pendaftaran.index', ['id' => $a->id]))->assertOk()->assertSee('Rina Uji Coba');
        $this->post(route('pendaftaran.pass', $a))->assertRedirect();
        $this->post(route('pendaftaran.test', $a), ['tanggal' => now()->addDays(3)->toDateString(), 'jam' => '09.00'])->assertRedirect();
        $this->post(route('pendaftaran.accept', $a), ['classroom_id' => Classroom::first()->id])->assertRedirect();
        $this->assertSame('diterima', $a->fresh()->status);
        $this->assertDatabaseHas('users', ['name' => 'Rina Uji Coba', 'role' => 'peserta', 'email' => 'rina.uji@email.com']);

        // Email yang sudah dipakai tidak bisa didaftarkan lagi
        $this->post('/logout');
        $this->post('/daftar/baru');
        $this->post('/daftar', $step1 + ['email' => 'rina.uji@email.com'])->assertSessionHasErrors('email');
    }

    public function test_keuangan_catat_dan_verifikasi(): void
    {
        $this->actingAs(User::where('role', 'admin')->first());
        $pending = Payment::where('status', 'menunggu')->first();
        if ($pending) {
            $this->post(route('keuangan.verify', $pending))->assertRedirect();
            $this->assertSame('lunas', $pending->fresh()->status);
        }
        $s = Student::first();
        $before = $s->payments()->count();
        $this->post(route('keuangan.record'), ['student_id' => $s->id, 'method' => 'Transfer VA', 'paid_at' => now()->toDateString()])->assertRedirect();
        $this->assertGreaterThanOrEqual($before, $s->payments()->count());
        $this->post(route('keuangan.remind', $s))->assertRedirect();
    }

    public function test_paket_susun_terbit_jadwal(): void
    {
        $this->actingAs(User::where('role', 'admin')->first());
        $this->post(route('paket.store'))->assertRedirect();
        $p = ExamPackage::latest('id')->first();
        $this->assertSame('Draf', $p->status);
        $this->post(route('paket.autopick', $p))->assertRedirect();
        $this->get(route('paket.index', ['paket' => $p->id]))->assertOk();
        $this->post(route('paket.publish', $p))->assertRedirect();
        $p->refresh();
        $this->assertSame('Terbit', $p->status);
        $this->assertCount(30, $p->snapshot);
        $this->post(route('paket.schedule', $p), ['classroom_id' => Classroom::first()->id, 'duration' => 60, 'opens_at' => now()->toDateString(), 'closes_at' => now()->addWeek()->toDateString()])->assertRedirect();
        $this->assertDatabaseHas('exam_schedules', ['exam_package_id' => $p->id]);
    }

    public function test_kehadiran_dan_bank_soal_instruktur(): void
    {
        $this->actingAs(User::where('role', 'instruktur')->first());
        $html = $this->get('/kehadiran')->assertOk()->getContent();
        preg_match('/name="sesi" value="([^"]+)"/', $html, $m);
        $this->assertNotEmpty($m, 'Form kehadiran harus punya sesi');
        preg_match_all('/name="status\[(\d+)\]"/', $html, $ids);
        $status = collect(array_unique($ids[1]))->mapWithKeys(fn ($id) => [$id => 'H'])->all();
        $this->post('/kehadiran', ['sesi' => html_entity_decode($m[1]), 'status' => $status])->assertRedirect();
        $this->assertDatabaseCount('attendance_sessions', \App\Models\AttendanceSession::count());

        $n = Question::count();
        $this->post(route('banksoal.store'), ['level' => 'N4', 'section' => 'bunpou', 'question' => 'わたしは がくせい（　）です。', 'options' => ['が', 'を', 'に', 'で'], 'answer_key' => 0, 'explanation' => 'Contoh'])->assertRedirect();
        $this->assertSame($n + 1, Question::count());
        $q = Question::latest('id')->first();
        $this->delete(route('banksoal.destroy', $q))->assertRedirect();
        $this->assertSoftDeleted($q);
    }

    public function test_biodata_peserta_dan_kelas_per_angkatan(): void
    {
        $s = Student::where('nis', 'NB-26-0142')->first();
        $this->actingAs($s->user);
        $this->put(route('profil.update'), [
            'name' => $s->user->name, 'email' => $s->user->email, 'phone' => $s->user->phone,
            'birth_place' => 'Palembang', 'birth_date' => '2002-05-14', 'gender' => 'L', 'height_cm' => 171, 'religion' => 'Islam',
            'marital_status' => 'belum', 'address_ktp' => 'Jl. KTP No. 1', 'address_domicile' => 'Jl. Domisili No. 2',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $s->refresh();
        $this->assertSame('Jl. KTP No. 1', $s->address_ktp);
        $this->assertSame('Jl. Domisili No. 2', $s->address_domicile);
        $this->assertSame('Palembang, 14 Mei 2002', $s->ttl);
        $this->assertNotNull($s->batch->program);

        $this->actingAs(User::where('role', 'admin')->first());
        $batch = \App\Models\Batch::first();
        $this->post(route('kelas-admin.store'), ['kode' => 'N5-Z', 'level' => 'N5', 'wali_id' => User::where('role', 'instruktur')->first()->id])
            ->assertSessionHasErrors('batch_id');
        $this->post(route('kelas-admin.store'), ['batch_id' => $batch->id, 'kode' => 'N5-Z', 'level' => 'N5', 'wali_id' => User::where('role', 'instruktur')->first()->id])
            ->assertRedirect();
        $this->assertSame($batch->id, Classroom::where('kode', 'N5-Z')->first()->batch_id);
    }
}
