<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use App\Services\FinanceReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Laporan keuangan bulanan (pemasukan, pengeluaran, laba, cashflow) di dashboard admin dan laporan direktur.
 */
class LaporanKeuanganTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function data(): void
    {
        Payment::query()->delete();
        Expense::query()->delete();
        [$a, $b] = Student::orderBy('id')->take(2)->get()->all();
        $kas = Account::where('kode', 'like', '1-11%')->firstOrFail()->id;
        $akun = fn ($k) => Account::where('kode', $k)->firstOrFail()->id;

        // Desember 2025: masuk 1.000.000, keluar 300.000 → saldo awal 2026 = 700.000
        Payment::create(['student_id' => $a->id, 'installment_no' => 1, 'amount' => 1000000, 'method' => 'Tunai', 'status' => 'lunas', 'paid_at' => '2025-12-10']);
        Expense::create(['nomor' => 'BKK/2025/12/0001', 'tanggal' => '2025-12-12', 'account_id' => $akun('6-1301'), 'cash_account_id' => $kas, 'uraian' => 'Listrik', 'jumlah' => 300000]);
        // Maret 2026: masuk 2.500.000 (bukti menunggu tidak dihitung), beban pokok 400.000, operasional 600.000, lain 100.000
        Payment::create(['student_id' => $a->id, 'installment_no' => 2, 'amount' => 1500000, 'method' => 'Transfer VA', 'status' => 'lunas', 'paid_at' => '2026-03-05']);
        Payment::create(['student_id' => $b->id, 'installment_no' => 1, 'amount' => 1000000, 'method' => 'Tunai', 'status' => 'lunas', 'paid_at' => '2026-03-20']);
        Payment::create(['student_id' => $b->id, 'installment_no' => 2, 'amount' => 9999999, 'method' => 'Transfer VA', 'status' => 'menunggu', 'paid_at' => '2026-03-25']);
        $pokok = Account::where('kode', 'like', '5-%')->firstOrFail()->id;
        $lain = Account::where('kode', 'like', '8-%')->firstOrFail()->id;
        Expense::create(['nomor' => 'BKK/2026/03/0001', 'tanggal' => '2026-03-02', 'account_id' => $pokok, 'cash_account_id' => $kas, 'uraian' => 'Honor', 'jumlah' => 400000]);
        Expense::create(['nomor' => 'BKK/2026/03/0002', 'tanggal' => '2026-03-03', 'account_id' => $akun('6-1301'), 'cash_account_id' => $kas, 'uraian' => 'Listrik', 'jumlah' => 600000]);
        Expense::create(['nomor' => 'BKK/2026/03/0003', 'tanggal' => '2026-03-04', 'account_id' => $lain, 'cash_account_id' => $kas, 'uraian' => 'Admin bank', 'jumlah' => 100000]);
        // April 2026: hanya pengeluaran 2.000.000 → rugi
        Expense::create(['nomor' => 'BKK/2026/04/0001', 'tanggal' => '2026-04-01', 'account_id' => $akun('6-1301'), 'cash_account_id' => $kas, 'uraian' => 'Listrik', 'jumlah' => 2000000]);
    }

    public function test_perhitungan_bulanan_laba_dan_cashflow(): void
    {
        $this->data();
        $r = app(FinanceReportService::class)->report('2026', '3');

        $this->assertSame([2026, 2025], array_slice($r['years'], -2));
        $this->assertSame(['month' => 3, 'pemasukan' => 2500000, 'pengeluaran' => 1100000, 'laba' => 1400000, 'saldo_awal' => 700000, 'saldo_akhir' => 2100000], $r['cur']);
        $this->assertSame(700000, $r['months'][0]['saldo_awal']);
        $this->assertSame(-2000000, $r['months'][3]['laba']);
        $this->assertSame(100000, $r['months'][3]['saldo_akhir']);
        $this->assertSame(100000, $r['totals']['saldo_akhir']);
        $this->assertSame(-600000, $r['totals']['laba']);

        $lr = $r['labaRugi'];
        $this->assertSame(2100000, $lr['laba_kotor']);
        $this->assertSame(1500000, $lr['laba_operasional']);
        $this->assertSame(1400000, $lr['laba_bersih']);
        $this->assertSame(['Transfer VA' => 1500000, 'Tunai' => 1000000], $r['incomeByMethod']->all());
    }

    public function test_tampil_di_dashboard_admin_dan_laporan_direktur(): void
    {
        $this->data();
        $this->actingAs(User::where('role', 'admin')->firstOrFail());
        $this->get(route('admin.dashboard', ['ktahun' => 2026, 'kbulan' => 3]))->assertOk()
            ->assertSee('Laporan keuangan bulanan')->assertSee('Laba rugi · Maret 2026')
            ->assertSee('Rp2.500.000')->assertSee('Rp1.400.000')->assertSee('Rp2.100.000')->assertDontSee('Rp9.999.999');
        $this->get(route('admin.dashboard', ['ktahun' => 2026, 'kbulan' => 4]))->assertOk()
            ->assertSee('Rugi bersih')->assertSee('−Rp2.000.000');
        // Nilai filter tidak valid kembali ke bawaan
        $this->get(route('admin.dashboard', ['ktahun' => 1990, 'kbulan' => 99]))->assertOk()->assertSee('Laporan keuangan bulanan');

        $this->actingAs(User::where('role', 'direktur')->firstOrFail());
        $this->get(route('laporan.index', ['ktahun' => 2026, 'kbulan' => 3]))->assertOk()
            ->assertSee('Arus kas · Maret 2026')->assertSee('Rp2.100.000');
    }
}
