<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\BatchInstallment;
use App\Models\Payment;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Data Angkatan (admin): CRUD angkatan dan tahapan pembayaran yang dipakai
 * Pembayaran Peserta, Keuangan, dan halaman Pembayaran peserta.
 */
class DataAngkatanTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function admin(): User
    {
        $u = User::where('role', 'admin')->firstOrFail();
        $this->actingAs($u);

        return $u;
    }

    private function payload(array $over = []): array
    {
        return [
            'program_id' => Program::firstOrFail()->id, 'kode' => '2027-01', 'nama' => 'Angkatan 5',
            'mulai' => '2027-01-11', 'selesai' => '2027-07-10', 'kuota' => 30, 'biaya' => 9000000,
            'jatuh_tempo' => ['2027-03-15', '2027-01-15', '2027-02-15'],
            ...$over,
        ];
    }

    /** Peserta tanpa pembayaran, dipindah ke angkatan $b. */
    private function studentIn(Batch $b, ?int $totalFee = null): Student
    {
        $s = Student::firstOrFail();
        $s->payments()->delete();
        $s->update(['batch_id' => $b->id, 'total_fee' => $totalFee]);

        return $s->fresh();
    }

    public function test_hanya_admin_dan_daftar_angkatan_tampil(): void
    {
        $this->actingAs(User::where('role', 'direktur')->first());
        $this->get(route('angkatan.index'))->assertForbidden();

        $this->admin();
        $this->get(route('angkatan.index'))->assertOk()->assertSee('Data Angkatan')->assertSee('Angkatan 1')->assertSee('6 tahap');
        $this->get(route('angkatan.create'))->assertOk()->assertSee('Tahapan pembayaran');
        $this->get(route('angkatan.edit', Batch::first()))->assertOk()->assertSee('jatuh_tempo[]', false);
    }

    public function test_tambah_angkatan_dengan_tahapan_terurut(): void
    {
        $this->admin();
        $this->post(route('angkatan.store'), $this->payload())->assertRedirect(route('angkatan.index'));

        $b = Batch::where('kode', '2027-01')->firstOrFail();
        $this->assertSame(9000000, $b->biaya);
        $this->assertSame(
            [1 => '2027-01-15', 2 => '2027-02-15', 3 => '2027-03-15'],
            $b->installments->mapWithKeys(fn ($t) => [$t->tahap => $t->jatuh_tempo->toDateString()])->all()
        );
    }

    public function test_validasi_tahapan_dan_kode(): void
    {
        $this->admin();
        $this->from(route('angkatan.create'))->post(route('angkatan.store'), $this->payload(['jatuh_tempo' => []]))
            ->assertRedirect(route('angkatan.create'))->assertSessionHasErrors('jatuh_tempo');
        $this->post(route('angkatan.store'), $this->payload(['jatuh_tempo' => ['2027-01-15', '']]))->assertSessionHasErrors('jatuh_tempo.1');
        $this->post(route('angkatan.store'), $this->payload(['kode' => Batch::first()->kode]))->assertSessionHasErrors('kode');
        $this->assertSame(0, Batch::where('nama', 'Angkatan 5')->count());
    }

    public function test_ubah_tahapan_tidak_boleh_kurang_dari_yang_sudah_dibayar(): void
    {
        $this->admin();
        $this->post(route('angkatan.store'), $this->payload());
        $b = Batch::where('kode', '2027-01')->firstOrFail();
        $s = $this->studentIn($b);
        foreach ([1, 2, 3] as $no) {
            Payment::create(['student_id' => $s->id, 'installment_no' => $no, 'amount' => 1, 'status' => 'lunas', 'paid_at' => '2026-01-01']);
        }

        $this->put(route('angkatan.update', $b), $this->payload(['jatuh_tempo' => ['2027-01-15', '2027-02-15']]))
            ->assertSessionHasErrors('jatuh_tempo');
        $this->assertSame(3, $b->installments()->count());

        $this->put(route('angkatan.update', $b), $this->payload(['jatuh_tempo' => ['2027-01-15', '2027-02-15', '2027-03-15', '2027-04-15']]))
            ->assertRedirect(route('angkatan.edit', $b));
        $this->assertSame(4, $b->installments()->count());
    }

    public function test_hapus_ditolak_bila_masih_ada_kelas_atau_peserta(): void
    {
        $this->admin();
        $used = Batch::has('students')->firstOrFail();
        $this->from(route('angkatan.index'))->delete(route('angkatan.destroy', $used))->assertSessionHasErrors('hapus');
        $this->assertModelExists($used);

        $this->post(route('angkatan.store'), $this->payload());
        $empty = Batch::where('kode', '2027-01')->firstOrFail();
        $this->delete(route('angkatan.destroy', $empty))->assertRedirect(route('angkatan.index'));
        $this->assertModelMissing($empty);
        $this->assertSame(0, BatchInstallment::where('batch_id', $empty->id)->count());
    }

    public function test_jadwal_pembayaran_mengikuti_angkatan(): void
    {
        $this->admin();
        $this->post(route('angkatan.store'), $this->payload());
        $b = Batch::where('kode', '2027-01')->firstOrFail();
        $pay = app(PaymentService::class);

        $s = $this->studentIn($b);
        $this->assertSame([3000000, 3000000, 3000000], $pay->schedule($s)->pluck('amount')->all());

        // Total biaya peserta menggantikan biaya angkatan; sisa pembulatan di tahap terakhir.
        $s = $this->studentIn($b, 10000000);
        $this->assertSame([3333333, 3333333, 3333334], $pay->schedule($s)->pluck('amount')->all());
        $this->assertSame('2027-02-15', $pay->schedule($s)[1]['due']->toDateString());

        // Entri di menu Pembayaran Peserta memakai nominal tahap angkatan.
        $this->get(route('pembayaran-admin.index', ['q' => $s->nis]))->assertOk()->assertSee('Tahap 1: Rp3.333.333', false);
        $this->post(route('pembayaran-admin.store'), ['student_id' => $s->id, 'method' => 'Transfer VA', 'paid_at' => '2027-01-14'])->assertSessionHasNoErrors();
        $this->assertSame(3333333, (int) $s->payments()->where('installment_no', 1)->value('amount'));

        // Tahap terakhir lunas → tidak bisa dicatat lagi.
        $this->post(route('pembayaran-admin.store'), ['student_id' => $s->id, 'method' => 'Transfer VA', 'paid_at' => '2027-02-14']);
        $this->post(route('pembayaran-admin.store'), ['student_id' => $s->id, 'method' => 'Transfer VA', 'paid_at' => '2027-03-14']);
        $this->assertSame(3333334, (int) $s->payments()->where('installment_no', 3)->value('amount'));
        $this->post(route('pembayaran-admin.store'), ['student_id' => $s->id, 'method' => 'Transfer VA', 'paid_at' => '2027-03-20'])->assertStatus(422);
    }

    public function test_keuangan_dan_tagihan_peserta_memakai_tahapan_angkatan(): void
    {
        $this->admin();
        $this->post(route('angkatan.store'), $this->payload());
        $b = Batch::where('kode', '2027-01')->firstOrFail();
        $s = $this->studentIn($b);
        $pay = app(PaymentService::class);

        Carbon::setTestNow('2027-02-20');
        $this->assertSame(2, $pay->overdue($s));
        $this->assertSame(6000000, $pay->overdueAmount($s));
        $this->get(route('keuangan.index'))->assertOk()->assertSee($b->nama)->assertSee('Menunggak 2 tahap')->assertSee('0/3');

        $this->actingAs($s->user);
        $this->get(route('tagihan.index'))->assertOk()
            ->assertSee('dalam 3 tahap')->assertSee('15 Mar 2027')->assertSee('Tagihan berikutnya: tahap ke-1');
    }
}
