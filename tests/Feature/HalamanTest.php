<?php

namespace Tests\Feature;

use App\Models\Applicant;
use App\Models\Certificate;
use App\Models\Conversation;
use App\Models\ExamPackage;
use App\Models\JobOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Uji asap: setiap halaman terbuka tanpa galat untuk peran yang berhak.
 */
class HalamanTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function as(string $role): User
    {
        $u = User::where('role', $role)->where('is_active', true)->firstOrFail();
        $this->actingAs($u);

        return $u;
    }

    public function test_halaman_publik(): void
    {
        $this->get('/login')->assertOk()->assertSee('Masuk');
        $this->get('/lupa-password')->assertOk();
        $this->get('/daftar')->assertOk()->assertSee('Pendaftaran calon peserta');
        $this->get('/verifikasi')->assertOk();
        $no = Certificate::first()->number;
        $this->get('/verifikasi?no=' . $no)->assertOk()->assertSee('Sertifikat asli');
        $this->get('/verifikasi?no=NB-XXX')->assertOk()->assertSee('tidak ditemukan');
    }

    public function test_halaman_peserta(): void
    {
        $this->as('peserta');
        foreach (['/dashboard', '/materi', '/ujian', '/hasil', '/sertifikat', '/program-jepang', '/pembayaran', '/kelas', '/pesan', '/profil'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get('/sertifikat/' . Certificate::first()->id . '/cetak')->assertOk();
        $this->get('/admin')->assertForbidden();
    }

    public function test_halaman_instruktur(): void
    {
        $this->as('instruktur');
        foreach (['/monitoring', '/kelas', '/kehadiran', '/analisis', '/kelola-materi', '/bank-soal', '/pesan', '/profil'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get('/keuangan')->assertForbidden();
    }

    public function test_halaman_admin(): void
    {
        $this->as('admin');
        $draft = ExamPackage::where('status', 'Draf')->first();
        $urls = ['/admin', '/pendaftaran', '/pendaftaran?id=' . Applicant::first()->id, '/kelas-jadwal', '/paket-ujian', '/perusahaan', '/perusahaan?job=' . JobOrder::first()->id,
            '/keuangan', '/laporan', '/laporan?tahun=2025&level=N4', '/monitoring', '/analisis', '/kelola-materi', '/bank-soal', '/pesan', '/profil'];
        if ($draft) {
            $urls[] = '/paket-ujian?paket=' . $draft->id;
        }
        foreach ($urls as $url) {
            $this->get($url)->assertOk();
        }
        $this->get('/laporan/ekspor?tahun=2026')->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_halaman_direktur(): void
    {
        $this->as('direktur');
        foreach (['/laporan', '/keuangan', '/perusahaan', '/monitoring', '/pesan', '/profil'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get('/bank-soal')->assertForbidden();
        $this->post('/keuangan/bayar')->assertForbidden();
    }

    public function test_pesan_terkirim(): void
    {
        $u = $this->as('peserta');
        $c = Conversation::whereHas('participants', fn ($q) => $q->where('users.id', $u->id))->first();
        $this->get('/pesan/' . $c->id)->assertOk();
        $this->post('/pesan/' . $c->id, ['body' => 'Halo sensei'])->assertRedirect();
        $this->assertDatabaseHas('messages', ['conversation_id' => $c->id, 'body' => 'Halo sensei']);
    }
}
