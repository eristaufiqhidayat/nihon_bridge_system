<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Bagan akun pengeluaran (pola SAK EMKM yang lazim di Indonesia) dan contoh transaksi Agustus–Oktober 2026.
 */
class ExpenseSeeder extends Seeder
{
    public const ACCOUNTS = [
        // Kas & Bank (sumber dana)
        ['1-1101', 'Kas Kecil', 'Dana tunai untuk pengeluaran rutin bernilai kecil'],
        ['1-1102', 'Kas Besar', 'Kas tunai kantor'],
        ['1-1201', 'Bank BCA', 'Rekening operasional'],
        ['1-1202', 'Bank Mandiri', 'Rekening penerimaan biaya pelatihan'],
        // Beban Pokok Pendapatan
        ['5-1101', 'Beban Honor Instruktur', 'Honor sensei dan instruktur tamu'],
        ['5-1102', 'Beban Modul & Bahan Ajar', 'Buku Minna no Nihongo, modul, fotokopi materi'],
        ['5-1103', 'Beban Ujian JLPT/JFT & Sertifikasi', 'Biaya pendaftaran ujian peserta'],
        ['5-1104', 'Beban Medical Check-up Peserta', null],
        ['5-1105', 'Beban Pengurusan Dokumen (Paspor & Visa)', null],
        ['5-1106', 'Beban Asrama & Konsumsi Peserta', null],
        // Beban Operasional
        ['6-1101', 'Beban Gaji & Tunjangan Karyawan', null],
        ['6-1102', 'Beban BPJS Ketenagakerjaan & Kesehatan', null],
        ['6-1201', 'Beban Sewa Gedung', null],
        ['6-1301', 'Beban Listrik', null],
        ['6-1302', 'Beban Air (PDAM)', null],
        ['6-1303', 'Beban Telepon & Internet', null],
        ['6-1401', 'Beban Alat Tulis Kantor (ATK)', null],
        ['6-1402', 'Beban Perlengkapan & Kebersihan', null],
        ['6-1501', 'Beban Iklan & Promosi', 'Iklan media sosial, brosur, pameran'],
        ['6-1601', 'Beban Transportasi & Perjalanan Dinas', null],
        ['6-1701', 'Beban Pemeliharaan & Perbaikan', null],
        ['6-1801', 'Beban Penyusutan Aset Tetap', null],
        ['6-1901', 'Beban Perizinan & Legalitas', 'Izin LPK, SO/SIP2MI, akreditasi'],
        ['6-1902', 'Beban Jasa Profesional', 'Konsultan, notaris, akuntan'],
        // Beban Lain-lain
        ['8-1101', 'Beban Administrasi Bank', null],
        ['8-1102', 'Beban Bunga Pinjaman', null],
        ['8-1103', 'Beban Pajak', 'PBB, PPh final, dan pajak lain yang menjadi beban'],
    ];

    public function run(): void
    {
        foreach (self::ACCOUNTS as [$kode, $nama, $ket]) {
            Account::create(['kode' => $kode, 'nama' => $nama, 'keterangan' => $ket, 'is_active' => true]);
        }
        $id = Account::pluck('id', 'kode');
        $admin = User::where('role', 'admin')->value('id');

        // [tanggal, akun beban, sumber dana, penerima, uraian, jumlah]
        $rows = [
            ['2026-08-01', '6-1201', '1-1201', 'PT Graha Sentosa', 'Sewa gedung kantor & kelas Agustus 2026', 15000000],
            ['2026-08-05', '6-1301', '1-1201', 'PLN', 'Tagihan listrik Juli 2026', 2350000],
            ['2026-08-05', '6-1303', '1-1201', 'IndiHome', 'Internet kantor Agustus 2026', 750000],
            ['2026-08-12', '5-1102', '1-1102', 'Toko Buku Gramedia', 'Buku Minna no Nihongo I untuk Angkatan 3', 4200000],
            ['2026-08-20', '6-1401', '1-1101', 'Toko ATK Sinar', 'Kertas HVS, spidol, tinta printer', 485000],
            ['2026-08-25', '6-1101', '1-1201', 'Karyawan', 'Gaji karyawan Agustus 2026', 28500000],
            ['2026-08-25', '5-1101', '1-1201', 'Instruktur', 'Honor instruktur Agustus 2026', 18000000],
            ['2026-08-31', '8-1101', '1-1201', 'Bank BCA', 'Biaya administrasi rekening Agustus', 30000],
            ['2026-09-01', '6-1201', '1-1201', 'PT Graha Sentosa', 'Sewa gedung kantor & kelas September 2026', 15000000],
            ['2026-09-04', '6-1301', '1-1201', 'PLN', 'Tagihan listrik Agustus 2026', 2480000],
            ['2026-09-04', '6-1302', '1-1102', 'PDAM', 'Tagihan air Agustus 2026', 320000],
            ['2026-09-10', '5-1103', '1-1202', 'The Japan Foundation', 'Pendaftaran JFT-Basic 12 peserta', 3600000],
            ['2026-09-15', '6-1501', '1-1201', 'Meta Ads', 'Iklan rekrutmen Angkatan 5 di media sosial', 2500000],
            ['2026-09-18', '5-1104', '1-1202', 'Klinik Medika Utama', 'Medical check-up 8 peserta kandidat Jepang', 4000000],
            ['2026-09-22', '6-1601', '1-1101', 'Sopir travel', 'Transport interview user ke Jakarta', 1250000],
            ['2026-09-25', '6-1101', '1-1201', 'Karyawan', 'Gaji karyawan September 2026', 28500000],
            ['2026-09-25', '5-1101', '1-1201', 'Instruktur', 'Honor instruktur September 2026', 18000000],
            ['2026-09-30', '6-1102', '1-1201', 'BPJS', 'Iuran BPJS Ketenagakerjaan & Kesehatan September', 3150000],
            ['2026-10-01', '6-1201', '1-1201', 'PT Graha Sentosa', 'Sewa gedung kantor & kelas Oktober 2026', 15000000],
            ['2026-10-02', '6-1402', '1-1101', 'Toko Bersih Jaya', 'Sabun, tisu, dan alat kebersihan', 375000],
            ['2026-10-05', '6-1301', '1-1201', 'PLN', 'Tagihan listrik September 2026', 2410000],
            ['2026-10-05', '5-1105', '1-1202', 'Kantor Imigrasi', 'Pembuatan paspor 5 peserta', 3250000],
            ['2026-10-06', '6-1701', '1-1102', 'CV Teknik Dingin', 'Servis AC ruang kelas N5', 900000],
        ];
        foreach ($rows as [$tgl, $akun, $sumber, $penerima, $uraian, $jumlah]) {
            Expense::create([
                'nomor' => Expense::nextNumber($tgl), 'tanggal' => $tgl,
                'account_id' => $id[$akun], 'cash_account_id' => $id[$sumber],
                'penerima' => $penerima, 'uraian' => $uraian, 'jumlah' => $jumlah, 'user_id' => $admin,
            ]);
        }
    }
}
