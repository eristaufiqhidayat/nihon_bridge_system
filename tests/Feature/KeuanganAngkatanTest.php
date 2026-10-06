<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Student;
use App\Models\User;
use App\Services\PaymentService;
use App\Support\Fmt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Menu Keuangan: rekap per angkatan dan rincian peserta tiap angkatan.
 */
class KeuanganAngkatanTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_rekap_per_angkatan_dan_rincian_peserta(): void
    {
        $this->actingAs(User::where('role', 'admin')->firstOrFail());
        $pay = app(PaymentService::class);
        $b = Batch::has('students')->firstOrFail();
        $students = Student::with(PaymentService::RELATIONS)->where('batch_id', $b->id)->get();
        $other = Student::where('batch_id', '!=', $b->id)->orWhereNull('batch_id')->first();
        $total = $students->sum(fn ($s) => $pay->total($s));
        $paid = $students->sum(fn ($s) => $pay->paidAmount($s));

        $this->get(route('keuangan.index'))->assertOk()
            ->assertSee($b->nama)->assertSee(Fmt::rupiah($total))->assertSee(Fmt::rupiah($paid))
            ->assertSee(route('keuangan.batch', $b->id))->assertDontSee($students->first()->nis);

        $res = $this->get(route('keuangan.batch', $b->id))->assertOk()->assertSee('Kembali ke rekap');
        foreach ($students as $s) {
            $res->assertSee($s->nis);
        }
        if ($other) {
            $res->assertDontSee($other->nis);
        }
        $this->get(route('keuangan.batch', 99999))->assertNotFound();
    }

    public function test_peserta_tanpa_angkatan_dan_direktur_hanya_lihat(): void
    {
        $s = Student::firstOrFail();
        $s->update(['batch_id' => null]);

        $this->actingAs(User::where('role', 'direktur')->firstOrFail());
        $this->get(route('keuangan.index'))->assertOk()->assertSee('Tanpa angkatan')->assertSee(route('keuangan.batch', 'tanpa'));
        $this->get(route('keuangan.batch', 'tanpa'))->assertOk()->assertSee($s->nis)->assertDontSee('Kirim pengingat');

        $this->actingAs(User::where('role', 'peserta')->firstOrFail());
        $this->get(route('keuangan.batch', 'tanpa'))->assertForbidden();
    }
}
