<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\FirstPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Instruktur\KehadiranController;
use App\Http\Controllers\Peserta;
use App\Http\Controllers\Publik\DaftarController;
use App\Http\Controllers\Publik\VerifikasiController;
use App\Http\Controllers\Shared;
use App\Support\Catalog;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check()
    ? redirect()->route(Catalog::HOME[auth()->user()->role])
    : redirect()->route('login'));

/* ---------------- Publik ---------------- */
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:10,1');
    Route::post('/login/demo/{role}', [LoginController::class, 'demo'])->name('login.demo');

    Route::get('/lupa-password', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/lupa-password', [PasswordResetController::class, 'send'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/lupa-password/terkirim', [PasswordResetController::class, 'sent'])->name('password.sent');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->name('password.update');
    Route::get('/reset-password-selesai', [PasswordResetController::class, 'done'])->name('password.done');
});

Route::get('/daftar', [DaftarController::class, 'show'])->name('daftar');
Route::post('/daftar', [DaftarController::class, 'store'])->name('daftar.store');
Route::get('/daftar/selesai', [DaftarController::class, 'done'])->name('daftar.selesai');
Route::post('/daftar/baru', [DaftarController::class, 'reset'])->name('daftar.reset');
Route::get('/verifikasi/{no?}', VerifikasiController::class)->name('verifikasi');

/* ---------------- Aplikasi (login) ---------------- */
Route::middleware(['auth', 'password.changed'])->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/password-baru', [FirstPasswordController::class, 'show'])->name('password.first');
    Route::put('/password-baru', [FirstPasswordController::class, 'update'])->name('password.first.update');

    // Semua peran
    Route::get('/pesan/{conversation?}', [Shared\PesanController::class, 'index'])->name('pesan.index');
    Route::post('/pesan/{conversation}', [Shared\PesanController::class, 'send'])->name('pesan.send');
    Route::get('/profil', [Shared\ProfilController::class, 'show'])->name('profil.show');
    Route::put('/profil', [Shared\ProfilController::class, 'update'])->name('profil.update');
    Route::put('/profil/password', [Shared\ProfilController::class, 'password'])->name('profil.password');
    Route::post('/profil/notifikasi', [Shared\ProfilController::class, 'preferences'])->name('profil.prefs');
    Route::post('/profil/foto', [Shared\ProfilController::class, 'photo'])->name('profil.photo');
    Route::get('/notifikasi/{notification}', [Shared\NotifikasiController::class, 'open'])->name('notifikasi.open');
    Route::post('/notifikasi/baca-semua', [Shared\NotifikasiController::class, 'readAll'])->name('notifikasi.readAll');

    // Peserta
    Route::middleware('role:peserta')->group(function () {
        Route::get('/dashboard', Peserta\DashboardController::class)->name('dashboard');
        Route::get('/materi/{lesson?}', [Peserta\MateriController::class, 'show'])->name('materi.show');
        Route::post('/materi/{lesson}/selesai', [Peserta\MateriController::class, 'complete'])->name('materi.complete');

        Route::get('/ujian', [Peserta\UjianController::class, 'index'])->name('ujian.index');
        Route::post('/ujian/{schedule}/mulai', [Peserta\UjianController::class, 'start'])->name('ujian.start');
        Route::get('/ujian/cbt/{attempt}', [Peserta\UjianController::class, 'cbt'])->name('ujian.cbt');
        Route::post('/ujian/cbt/{attempt}/simpan', [Peserta\UjianController::class, 'save'])->name('ujian.save');
        Route::post('/ujian/cbt/{attempt}/selesai', [Peserta\UjianController::class, 'submit'])->name('ujian.submit');

        Route::get('/hasil/{attempt?}', Peserta\HasilController::class)->name('hasil.index');
        Route::get('/sertifikat/{certificate?}', [Peserta\SertifikatController::class, 'index'])->name('sertifikat.index');
        Route::get('/sertifikat/{certificate}/cetak', [Peserta\SertifikatController::class, 'print'])->name('sertifikat.print');
        Route::post('/sertifikat/{certificate}/email', [Peserta\SertifikatController::class, 'email'])->name('sertifikat.email');
        Route::get('/program-jepang', Peserta\ProgramController::class)->name('program.index');
        Route::get('/pembayaran', [Peserta\TagihanController::class, 'index'])->name('tagihan.index');
        Route::post('/pembayaran/bukti', [Peserta\TagihanController::class, 'upload'])->name('tagihan.upload');
    });

    Route::get('/kelas', Shared\KelasController::class)->middleware('role:peserta,instruktur')->name('kelas.index');

    // Instruktur
    Route::middleware('role:instruktur')->group(function () {
        Route::get('/kehadiran', [KehadiranController::class, 'index'])->name('kehadiran.index');
        Route::post('/kehadiran', [KehadiranController::class, 'store'])->name('kehadiran.store');
    });

    // Monitoring: instruktur, admin, direktur (direktur baca saja)
    Route::get('/monitoring', [Shared\MonitoringController::class, 'index'])->middleware('role:instruktur,admin,direktur')->name('monitoring.index');

    // Instruktur & admin
    Route::middleware('role:instruktur,admin')->group(function () {
        Route::post('/monitoring/{student}/dokumen', [Shared\MonitoringController::class, 'document'])->name('monitoring.document');
        Route::get('/analisis', Shared\AnalisisController::class)->name('analisis.index');

        Route::get('/kelola-materi', [Shared\MateriAdminController::class, 'index'])->name('materi-admin.index');
        Route::post('/kelola-materi/bab', [Shared\MateriAdminController::class, 'storeChapter'])->name('materi-admin.chapter.store');
        Route::patch('/kelola-materi/bab/{chapter}', [Shared\MateriAdminController::class, 'updateChapter'])->name('materi-admin.chapter.update');
        Route::post('/kelola-materi/bab/{chapter}/bagian', [Shared\MateriAdminController::class, 'storeLesson'])->name('materi-admin.lesson.store');
        Route::patch('/kelola-materi/bagian/{lesson}/status', [Shared\MateriAdminController::class, 'toggle'])->name('materi-admin.lesson.toggle');
        Route::patch('/kelola-materi/bagian/{lesson}/urutan', [Shared\MateriAdminController::class, 'move'])->name('materi-admin.lesson.move');
        Route::delete('/kelola-materi/bagian/{lesson}', [Shared\MateriAdminController::class, 'destroy'])->name('materi-admin.lesson.destroy');

        Route::get('/bank-soal', [Shared\BankSoalController::class, 'index'])->name('banksoal.index');
        Route::post('/bank-soal', [Shared\BankSoalController::class, 'store'])->name('banksoal.store');
        Route::put('/bank-soal/{question}', [Shared\BankSoalController::class, 'update'])->name('banksoal.update');
        Route::delete('/bank-soal/{question}', [Shared\BankSoalController::class, 'destroy'])->name('banksoal.destroy');
    });

    // Admin
    Route::middleware('role:admin')->group(function () {
        Route::get('/admin', [Admin\DashboardController::class, 'index'])->name('admin.dashboard');
        Route::post('/admin/pengguna', [Admin\DashboardController::class, 'storeUser'])->name('admin.users.store');
        Route::patch('/admin/pengguna/{user}/status', [Admin\DashboardController::class, 'toggleUser'])->name('admin.users.toggle');

        Route::get('/data-peserta', [Admin\PesertaController::class, 'index'])->name('peserta-admin.index');
        Route::get('/data-peserta/baru', [Admin\PesertaController::class, 'create'])->name('peserta-admin.create');
        Route::post('/data-peserta', [Admin\PesertaController::class, 'store'])->name('peserta-admin.store');
        Route::get('/data-peserta/{student}/edit', [Admin\PesertaController::class, 'edit'])->name('peserta-admin.edit');
        Route::put('/data-peserta/{student}', [Admin\PesertaController::class, 'update'])->name('peserta-admin.update');
        Route::delete('/data-peserta/{student}', [Admin\PesertaController::class, 'destroy'])->name('peserta-admin.destroy');

        Route::get('/pendaftaran', [Admin\PendaftaranController::class, 'index'])->name('pendaftaran.index');
        Route::get('/pendaftaran/{applicant}/berkas/{key}', [Admin\PendaftaranController::class, 'file'])->name('pendaftaran.file');
        Route::post('/pendaftaran/{applicant}/lolos-berkas', [Admin\PendaftaranController::class, 'passDocuments'])->name('pendaftaran.pass');
        Route::post('/pendaftaran/{applicant}/jadwal-tes', [Admin\PendaftaranController::class, 'scheduleTest'])->name('pendaftaran.test');
        Route::post('/pendaftaran/{applicant}/terima', [Admin\PendaftaranController::class, 'accept'])->name('pendaftaran.accept');
        Route::post('/pendaftaran/{applicant}/tolak', [Admin\PendaftaranController::class, 'reject'])->name('pendaftaran.reject');
        Route::post('/pendaftaran/{applicant}/pengingat', [Admin\PendaftaranController::class, 'remind'])->name('pendaftaran.remind');

        Route::get('/kelas-jadwal', [Admin\KelasAdminController::class, 'index'])->name('kelas-admin.index');
        Route::post('/kelas-jadwal', [Admin\KelasAdminController::class, 'store'])->name('kelas-admin.store');
        Route::post('/kelas-jadwal/{classroom}/slot', [Admin\KelasAdminController::class, 'slot'])->name('kelas-admin.slot');

        Route::get('/paket-ujian', [Admin\PaketController::class, 'index'])->name('paket.index');
        Route::post('/paket-ujian', [Admin\PaketController::class, 'store'])->name('paket.store');
        Route::post('/paket-ujian/{package}/pilih', [Admin\PaketController::class, 'pick'])->name('paket.pick');
        Route::post('/paket-ujian/{package}/pilih-otomatis', [Admin\PaketController::class, 'autoPick'])->name('paket.autopick');
        Route::post('/paket-ujian/{package}/terbitkan', [Admin\PaketController::class, 'publish'])->name('paket.publish');
        Route::post('/paket-ujian/{package}/jadwal', [Admin\PaketController::class, 'schedule'])->name('paket.schedule');

        Route::post('/perusahaan/job-order', [Admin\PerusahaanController::class, 'storeJob'])->name('perusahaan.job.store');
        Route::post('/perusahaan/job-order/{job}/kandidat/{student}', [Admin\PerusahaanController::class, 'addCandidate'])->name('perusahaan.candidate.add');
        Route::patch('/perusahaan/kandidat/{candidate}', [Admin\PerusahaanController::class, 'setInterview'])->name('perusahaan.candidate.update');

        Route::get('/data-pembayaran', [Admin\PembayaranController::class, 'index'])->name('pembayaran-admin.index');
        Route::post('/data-pembayaran', [Admin\PembayaranController::class, 'store'])->name('pembayaran-admin.store');
        Route::put('/data-pembayaran/{payment}', [Admin\PembayaranController::class, 'update'])->name('pembayaran-admin.update');
        Route::delete('/data-pembayaran/{payment}', [Admin\PembayaranController::class, 'destroy'])->name('pembayaran-admin.destroy');

        Route::post('/keuangan/{payment}/verifikasi', [Admin\KeuanganController::class, 'verify'])->name('keuangan.verify');
        Route::get('/keuangan/{payment}/bukti', [Admin\KeuanganController::class, 'proof'])->name('keuangan.proof');
        Route::post('/keuangan/pengingat/{student}', [Admin\KeuanganController::class, 'remind'])->name('keuangan.remind');
    });

    // Admin & direktur
    Route::middleware('role:admin,direktur')->group(function () {
        Route::get('/perusahaan', [Admin\PerusahaanController::class, 'index'])->name('perusahaan.index');
        Route::get('/keuangan', [Admin\KeuanganController::class, 'index'])->name('keuangan.index');
        Route::get('/laporan', [Admin\LaporanController::class, 'index'])->name('laporan.index');
        Route::get('/laporan/ekspor', [Admin\LaporanController::class, 'export'])->name('laporan.export');
    });
});
