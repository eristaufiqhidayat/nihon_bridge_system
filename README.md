# LPK Nihon Bridge · Japanese Language Learning & Testing System

Aplikasi Laravel 13 + Blade yang dibangun dari mockup `NIHON_BRIDGE_SYSTEM.html`. Ada empat peran (Peserta, Instruktur, Admin, Direktur) dan halaman publik untuk pendaftaran serta verifikasi sertifikat. Tampilan memakai CSS mockup apa adanya (`public/css/app.css`) dan JavaScript ringan tanpa build step.

## Kebutuhan

- PHP 8.3 atau lebih baru, dengan ekstensi `pdo_sqlite` (atau `pdo_mysql`), `mbstring`, `fileinfo`
- Composer 2
- Tidak perlu Node/NPM

## Instalasi cepat (SQLite)

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite        # Windows: type nul > database\database.sqlite
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

Buka http://127.0.0.1:8000 lalu pakai tombol **Masuk cepat sebagai…** di halaman login.

## Memakai MySQL

Ubah `.env`:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nihon_bridge
DB_USERNAME=root
DB_PASSWORD=
```

Buat database `nihon_bridge` (utf8mb4), lalu `php artisan migrate:fresh --seed`.

## Akun demo

Semua password: **`sakura2026`**

| Peran | Email |
|---|---|
| Peserta | `ahmad.fauzi@nihonbridge.id` (juga `siti.r@`, `dewi.l@`, `budi.s@`, `nur.a@` … `@nihonbridge.id`) |
| Instruktur | `sato.sensei@nihonbridge.id` (juga `yamada.sensei@`, `tanaka.sensei@` …) |
| Admin | `rina.info@nihonbridge.id` |
| Direktur | `direktur@nihonbridge.id` |

Matikan tombol masuk cepat di produksi dengan `NB_DEMO_LOGIN=false`. Nama direktur di sertifikat diatur lewat `NB_DIRECTOR_NAME`.

## Fitur per peran

**Peserta:** dashboard dan tahapan program Jepang, materi (video/PDF, tandai selesai), ujian CBT (autosave ke server, antrean offline di perangkat, timer dari server, kirim otomatis saat waktu habis, audio choukai lewat text-to-speech browser), nilai per bagian dan pembahasan, sertifikat dengan QR verifikasi (cetak/PDF lewat browser, kirim ke email), program Jepang dan status dokumen, tagihan cicilan dan unggah bukti transfer, kelas dan jadwal, pesan, profil.

**Instruktur:** monitoring peserta dan status dokumen, input kehadiran per sesi, analisis ujian (rata-rata per bagian, soal tersulit), kelola materi (bab, bagian, draf/terbit, urutan), bank soal (tambah, ubah, hapus).

**Admin:** semua fitur instruktur, ditambah dashboard dan kelola pengguna, seleksi pendaftar (lolos berkas, jadwal tes, terima ke kelas, tolak, pengingat), kelas dan jadwal (cek bentrok instruktur), paket ujian (susun sesuai komposisi, terbitkan sebagai salinan, jadwalkan untuk kelas), perusahaan dan job order (ajukan kandidat, hasil interview), keuangan (catat bayar, verifikasi bukti, pengingat tunggakan), laporan dan ekspor CSV.

**Direktur:** laporan dan analitik, ditambah keuangan, perusahaan, monitoring dan pesan dalam mode baca saja.

**Publik:** formulir pendaftaran 4 langkah dengan unggah berkas (`/daftar`), verifikasi sertifikat (`/verifikasi?no=NB-JLPT-2026-0061`), lupa password.

## Aturan bisnis utama

Semua angka ini ada di `config/nihonbridge.php`.

- Lulus ujian jika nilai total ≥ 60 dan setiap bagian ≥ 40. Sertifikat terbit otomatis dengan nomor `NB-JLPT-YYYY-NNNN`.
- Indeks kesiapan = 0,7 × nilai total + 0,3 × nilai bagian terlemah. Peserta dianggap siap jika indeks ≥ 70.
- Paket ujian menyimpan salinan soal saat diterbitkan. Perubahan di bank soal tidak mengubah paket yang sudah terbit.
- Biaya Rp21.000.000 dibayar dalam 6 cicilan Rp3.500.000.
- Batas kehadiran minimal 85%.
- Pendaftar wajib mengisi email. Email harus unik, tidak boleh dipakai akun lain atau pendaftaran lain yang masih berjalan, dan menjadi alamat login setelah pendaftar diterima.

## Struktur kode

| Bagian | Lokasi |
|---|---|
| Model (27) | `app/Models` |
| Service (logika bisnis) | `app/Services` (Exam, Package, QuestionBank, Schedule, Learning, Applicant, Program, Payment, Attendance, Analysis, Report, Message, Notification) |
| Controller | `app/Http/Controllers/{Auth,Peserta,Instruktur,Admin,Shared,Publik}` |
| Middleware peran | `app/Http/Middleware/EnsureRole.php` (alias `role:admin,direktur`) |
| Routes | `routes/web.php` |
| Migrasi | `database/migrations` (akademik, materi, ujian, operasional, komunikasi) |
| Seeder | `database/seeders` (data contoh dari mockup, termasuk 30 soal simulasi JLPT N4 di `database/seeders/data/questions.php`) |
| View Blade | `resources/views/{layouts,auth,peserta,instruktur,admin,shared,publik,partials,components}` |
| CSS/JS | `public/css/app.css`, `public/js/app.js`, `public/js/cbt.js` |
| Daftar nilai tetap & format tanggal/rupiah | `app/Support/Catalog.php`, `app/Support/Fmt.php` |

## Pengujian

```bash
php artisan test
```

Ada 12 tes. Mereka membuka setiap halaman untuk tiap peran, memeriksa akses yang ditolak, dan menjalankan alur utama: login, ujian CBT dari mulai sampai sertifikat terbit, pendaftaran publik sampai diterima admin, keuangan, menyusun dan menerbitkan paket, kehadiran, dan bank soal.

## Catatan

- Email (reset password, kuitansi, sertifikat) memakai mailer `log` secara bawaan, jadi isinya tercatat di `storage/logs/laravel.log`. Di lingkungan lokal, halaman "cek email" juga menampilkan tautan reset agar mudah dicoba. Atur `MAIL_*` di `.env` untuk mengirim email sungguhan.
- Berkas unggahan (berkas pendaftar, bukti transfer, dokumen peserta) disimpan di disk `local` (`storage/app/private`) dan hanya bisa diunduh lewat route yang dicek perannya.
- Pembayaran VA, panggilan video, dan PDF sertifikat tidak memakai layanan pihak ketiga. Sertifikat dicetak atau disimpan sebagai PDF lewat dialog cetak browser.
