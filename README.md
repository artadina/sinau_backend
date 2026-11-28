# Sinau Backend

Backend Sinau menggunakan Laravel 12, MySQL, Blade, Tailwind CSS melalui Vite, session Laravel untuk panel web, dan Laravel Sanctum untuk API mobile.

## Pengembangan lokal

1. Pasang dependency PHP dan JavaScript: `composer install` dan `npm install`.
2. Salin `.env.example` menjadi `.env`, lalu isi konfigurasi database lokal.
3. Pastikan `MAIL_MAILER=log` agar email reset password ditulis ke log lokal.
4. Jalankan migration dan data demo: `php artisan migrate --seed`.
5. Bangun asset: `npm run build`; untuk development gunakan `npm run dev`.
6. Jalankan aplikasi: `php artisan serve`.

Jangan gunakan `migrate:fresh` pada database yang berisi data.

## Data demo non-production

`php artisan migrate --seed` menyiapkan akun demo, dua modul, learner, dan riwayat pre/post-test contoh agar kartu dan grafik dashboard Admin menampilkan data. Seeder juga menambahkan contoh forum dan laporan moderasi. Seeder aman dijalankan ulang dan menolak berjalan di production.

Kredensial berikut hanya untuk lingkungan non-production:

| Role | Email | Password awal |
|---|---|---|
| Superadmin | `superadmin@sinau.test` | `Password!123` |
| Admin | `admin@sinau.test` | `AdminSementara!123` |
| Admin | `admin2@sinau.test` | `AdminSementara!234` |
| Learner | `learner1@sinau.test` | `LearnerDemo!123` |
| Learner | `learner2@sinau.test` | `LearnerDemo!123` |
| Learner | `learner3@sinau.test` | `LearnerDemo!123` |
| Learner (consent belum disetujui) | `learner4@sinau.test` | `LearnerDemo!123` |

Akun Admin demo ditandai sebagai password sementara dan harus mengganti password saat login pertama. Seeder tidak menimpa akun yang sudah ada. Superadmin dapat mengelola akun Admin dan Learner melalui panel khusus. Jangan gunakan kredensial ini di production.
Learner demo 1–3 dan akun riwayat sudah menyetujui consent; Learner demo 4 belum menyetujuinya.

Tiga akun evaluasi demo pada modul published pertama memiliki kondisi berikut:

| Learner | Kondisi evaluasi |
|---|---|
| `learner1@sinau.test` | Belum menyelesaikan pre-test |
| `learner2@sinau.test` | Pre-test selesai, unit belum selesai seluruhnya |
| `learner3@sinau.test` | Pre-test selesai dan semua unit aktif selesai |

## Email reset password lokal

Dengan `MAIL_MAILER=log`, tautan reset tercatat di `storage/logs/laravel.log`; email tidak dikirim ke alamat eksternal.

## Pengujian

Jalankan `php artisan test`. Test feature memakai SQLite in-memory dan tidak memodifikasi database pengembangan.
