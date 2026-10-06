<?php

namespace App\Support;

/**
 * Menu sidebar per peran: [route, ikon, label, pola route yang menandai aktif].
 */
class Navigation
{
    public const MENU = [
        'peserta' => [
            ['dashboard', '🏠', 'Dashboard', 'dashboard'],
            ['materi.show', '📘', 'Materi', 'materi.*'],
            ['kelas.index', '🏫', 'Kelas Saya', 'kelas.*'],
            ['ujian.index', '📝', 'Ujian', 'ujian.*'],
            ['hasil.index', '📊', 'Nilai', 'hasil.*'],
            ['sertifikat.index', '🏅', 'Sertifikat', 'sertifikat.*'],
            ['program.index', '✈️', 'Program Jepang', 'program.*'],
            ['tagihan.index', '💳', 'Pembayaran', 'tagihan.*'],
            ['pesan.index', '✉️', 'Pesan', 'pesan.*'],
            ['profil.show', '👤', 'Profil', 'profil.*'],
        ],
        'instruktur' => [
            ['monitoring.index', '👥', 'Monitoring Peserta', 'monitoring.*'],
            ['kelas.index', '🏫', 'Kelas Saya', 'kelas.*'],
            ['kehadiran.index', '✅', 'Input Kehadiran', 'kehadiran.*'],
            ['analisis.index', '🔍', 'Analisis Ujian', 'analisis.*'],
            ['materi-admin.index', '📘', 'Kelola Materi', 'materi-admin.*'],
            ['banksoal.index', '🗂️', 'Bank Soal', 'banksoal.*'],
            ['pesan.index', '✉️', 'Pesan', 'pesan.*'],
            ['profil.show', '👤', 'Profil', 'profil.*'],
        ],
        'admin' => [
            ['admin.dashboard', '🏠', 'Dashboard', 'admin.*'],
            ['pendaftaran.index', '📝', 'Pendaftaran', 'pendaftaran.*'],
            ['angkatan.index', '🗓️', 'Data Angkatan', 'angkatan.*'],
            ['peserta-admin.index', '🎓', 'Data Peserta', 'peserta-admin.*'],
            ['pengguna-admin.index', '🧑‍💼', 'Data Pengguna', 'pengguna-admin.*'],
            ['kelas-admin.index', '🏫', 'Kelas & Jadwal', 'kelas-admin.*'],
            ['materi-admin.index', '📘', 'Kelola Materi', 'materi-admin.*'],
            ['banksoal.index', '🗂️', 'Bank Soal', 'banksoal.*'],
            ['paket.index', '🧾', 'Paket & Jadwal Ujian', 'paket.*'],
            ['analisis.index', '🔍', 'Analisis Ujian', 'analisis.*'],
            ['monitoring.index', '👥', 'Monitoring Peserta', 'monitoring.*'],
            ['perusahaan.index', '🏢', 'Perusahaan & Job Order', 'perusahaan.*'],
            ['pembayaran-admin.index', '🧾', 'Pembayaran Peserta', 'pembayaran-admin.*'],
            ['keuangan.index', '💳', 'Keuangan', 'keuangan.*'],
            ['laporan.index', '📈', 'Laporan', 'laporan.*'],
            ['role-admin.index', '🔐', 'Data Role', 'role-admin.*'],
            ['pesan.index', '✉️', 'Pesan', 'pesan.*'],
            ['profil.show', '👤', 'Profil', 'profil.*'],
        ],
        'direktur' => [
            ['laporan.index', '📈', 'Laporan & Analitik', 'laporan.*'],
            ['perusahaan.index', '🏢', 'Perusahaan & Job Order', 'perusahaan.*'],
            ['keuangan.index', '💳', 'Keuangan', 'keuangan.*'],
            ['monitoring.index', '👥', 'Monitoring Peserta', 'monitoring.*'],
            ['pesan.index', '✉️', 'Pesan', 'pesan.*'],
            ['profil.show', '👤', 'Profil', 'profil.*'],
        ],
    ];

    /** Menu yang selalu dimiliki setiap peran tambahan. */
    public const ALWAYS = [
        ['pesan.index', '✉️', 'Pesan', 'pesan.*'],
        ['profil.show', '👤', 'Profil', 'profil.*'],
    ];

    /** Menu yang bisa dipilih untuk peran tambahan: menu admin selain Pesan dan Profil. */
    public static function options(): array
    {
        $always = array_column(self::ALWAYS, 0);

        return array_values(array_filter(self::MENU['admin'], fn ($m) => ! in_array($m[0], $always, true)));
    }
}
