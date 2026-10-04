<?php

namespace App\Support;

/**
 * Daftar nilai tetap (lookup) yang dipakai di seluruh aplikasi:
 * bagian ujian, mata pelajaran, slot jadwal, tahapan program, dsb.
 */
class Catalog
{
    public const ROLES = [
        'peserta' => 'Peserta',
        'instruktur' => 'Instruktur',
        'admin' => 'Admin',
        'direktur' => 'Direktur',
    ];

    public const ROLE_BADGE = [
        'peserta' => 'b-blue',
        'instruktur' => 'b-green',
        'admin' => 'b-purple',
        'direktur' => 'b-orange',
    ];

    /** Halaman awal tiap peran setelah login. */
    public const HOME = [
        'peserta' => 'dashboard',
        'instruktur' => 'monitoring.index',
        'admin' => 'admin.dashboard',
        'direktur' => 'laporan.index',
    ];

    public const SECTIONS = [
        'kosakata' => ['name' => 'Kosakata', 'jp' => 'Moji・Goi (Huruf & Kosakata)', 'color' => 'var(--blue)', 'badge' => 'b-blue'],
        'bunpou' => ['name' => 'Tata Bahasa', 'jp' => 'Bunpou', 'color' => 'var(--green)', 'badge' => 'b-green'],
        'dokkai' => ['name' => 'Membaca', 'jp' => 'Dokkai', 'color' => 'var(--orange)', 'badge' => 'b-orange'],
        'choukai' => ['name' => 'Mendengar', 'jp' => 'Choukai', 'color' => 'var(--purple)', 'badge' => 'b-purple'],
    ];

    /** Komposisi paket simulasi JLPT N4 (jumlah soal per bagian). */
    public const COMPOSE = ['kosakata' => 10, 'bunpou' => 10, 'dokkai' => 5, 'choukai' => 5];

    public const LEVELS = ['N5', 'N4', 'N3', 'N2', 'N1'];

    public const LV_BADGE = ['N5' => 'b-blue', 'N4' => 'b-sakura', 'N3' => 'b-orange', 'N2' => 'b-purple', 'N1' => 'b-grey'];

    public const LV_COLOR = ['N5' => 'var(--blue)', 'N4' => 'var(--sakura-deep)', 'N3' => 'var(--orange)', 'N2' => 'var(--purple)', 'N1' => 'var(--ink-soft)'];

    public const MAPEL = [
        'TB' => 'Tata Bahasa', 'KK' => 'Kanji & Kosakata', 'PC' => 'Percakapan', 'LS' => 'Listening',
        'BC' => 'Membaca', 'LJ' => 'Latihan Soal JLPT', 'BD' => 'Budaya & Etika Kerja', 'KG' => 'Kaigo Nihongo',
    ];

    public const MAPEL_BG = [
        'TB' => 'ic-bg-sakura', 'KK' => 'ic-bg-orange', 'PC' => 'ic-bg-blue', 'LS' => 'ic-bg-purple',
        'BC' => 'ic-bg-green', 'LJ' => 'ic-bg-grey', 'BD' => 'ic-bg-grey', 'KG' => 'ic-bg-green',
    ];

    public const SLOTS = ['08.00–10.00', '10.15–12.15', '13.00–15.00'];

    public const DAYS = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];

    public const STAGES = ['Pendaftaran', 'Pelatihan Bahasa', 'Tryout JLPT', 'Interview', 'MCU', 'Visa', 'Keberangkatan'];

    /** [kunci, label, tahap program yang menandai dokumen selesai]. */
    public const DOCS = [
        ['paspor', 'Paspor', 2],
        ['jlpt', 'Sertifikat Simulasi JLPT N4', 3],
        ['interview', 'Hasil Interview', 4],
        ['mcu', 'Medical Check Up', 5],
        ['visa', 'COE & Visa', 6],
        ['tiket', 'Tiket Pesawat', 7],
    ];

    public const DOC_STATUS = ['selesai' => 'Selesai', 'proses' => 'Proses', 'belum' => 'Belum'];

    public const APP_STATUS = [
        'baru' => ['Baru', 'b-sakura'],
        'berkas' => ['Lolos berkas', 'b-blue'],
        'tes' => ['Tes & wawancara', 'b-purple'],
        'diterima' => ['Diterima', 'b-green'],
        'ditolak' => ['Ditolak', 'b-grey'],
    ];

    /** Berkas pendaftaran: [kunci, label, wajib]. */
    public const APP_FILES = [
        ['ktp', 'KTP', true],
        ['ijazah', 'Ijazah terakhir', true],
        ['foto', 'Pas foto 3×4 latar putih', true],
        ['izin', 'Surat izin orang tua/wali', true],
        ['kk', 'Kartu keluarga', false],
    ];

    public const PROGRAMS = [
        'Tokutei Ginou · Kaigo' => 'Perawat lansia · kontrak hingga 5 tahun',
        'Tokutei Ginou · Pengolahan Makanan' => 'Pabrik makanan dan minuman',
        'Magang · Manufaktur' => 'Ginou Jisshu · operator mesin',
        'Magang · Konstruksi' => 'Ginou Jisshu · bangunan dan sipil',
    ];

    public const EDUCATION = ['SMP', 'SMA/SMK', 'D3', 'S1'];

    public const JP_LEVEL = ['Belum pernah belajar', 'Pernah belajar sendiri', 'Setara N5', 'Setara N4 atau lebih'];

    public const REFERRAL = ['Media sosial', 'Teman/keluarga', 'Sekolah', 'Alumni LPK', 'Lainnya'];

    public const REJECT_REASONS = [
        'Berkas tidak lengkap setelah pengingat',
        'Tidak lulus tes tertulis',
        'Hasil tes kesehatan awal belum memenuhi syarat',
        'Tidak hadir tes tanpa kabar',
    ];

    public const ATTENDANCE = [
        'H' => ['Hadir', 'b-green'],
        'I' => ['Izin', 'b-blue'],
        'S' => ['Sakit', 'b-orange'],
        'A' => ['Alpa', 'b-red'],
    ];

    public const INTERVIEW = [
        'menunggu' => ['Menunggu', 'b-grey'],
        'lulus' => ['Lulus', 'b-green'],
        'tidak' => ['Tidak lulus', 'b-red'],
    ];

    public const LESSON_TYPES = ['Video' => 'b-blue', 'PDF' => 'b-sakura', 'PPT' => 'b-orange', 'Audio' => 'b-purple'];

    public const PAYMENT_METHODS = ['Transfer VA', 'Transfer bank', 'Tunai di kantor'];

    public static function sectionName(string $key): string
    {
        return self::SECTIONS[$key]['name'] ?? $key;
    }
}
