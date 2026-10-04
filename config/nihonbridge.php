<?php

return [

    'org' => [
        'name' => 'LPK Nihon Bridge',
        'tagline' => 'Japanese Language Learning & Testing System',
        'address' => [
            'Lorong Jaya (samping Sambal Lalap Plaju), Jaya 7, Lorong Mufakat No. 1105',
            'RT 019/RW 006, Kel. 16 Ulu, Kec. Seberang Ulu II, Palembang',
        ],
        'short_address' => '16 Ulu, Seberang Ulu II · Palembang',
        'director' => env('NB_DIRECTOR_NAME', '[Nama Direktur]'),
    ],

    /*
     * Tombol "masuk cepat sebagai" di halaman login. Matikan di produksi.
     */
    'demo_login' => env('NB_DEMO_LOGIN', true),

    /*
     * Biaya pelatihan & proses keberangkatan (contoh).
     */
    'fee' => [
        'total' => 21000000,
        'installments' => 6,
        'per' => 3500000,
        'due' => ['2026-02-15', '2026-04-15', '2026-06-15', '2026-08-15', '2026-10-15', '2026-12-15'],
        'va_prefix' => '8808',
        'bank' => 'Bank contoh',
    ],

    'exam' => [
        'pass_total' => 60,
        'pass_section' => 40,
        'readiness_ready' => 70,
    ],

    /* Kelas yang dianggap lewat batas kehadiran. */
    'attendance_min' => 85,

    'upload_max_kb' => 2048,
];
