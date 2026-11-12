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

## Akun demo lokal

Kredensial berikut hanya untuk pengembangan lokal:

| Role | Email | Password awal |
|---|---|---|
| Superadmin | `superadmin@sinau.test` | `Password!123` |
| Admin | `admin@sinau.test` | `AdminSementara!123` |
| Admin | `admin2@sinau.test` | `AdminSementara!234` |
| Learner | `learner1@sinau.test` | `LearnerDemo!123` |
| Learner | `learner2@sinau.test` | `LearnerDemo!123` |
| Learner | `learner3@sinau.test` | `LearnerDemo!123` |

Akun Admin demo ditandai sebagai password sementara dan harus mengganti password saat login pertama. Seeder tidak menimpa akun yang sudah ada. Superadmin dapat mengelola akun Admin dan Learner melalui panel khusus.

## Email reset password lokal

Dengan `MAIL_MAILER=log`, tautan reset tercatat di `storage/logs/laravel.log`; email tidak dikirim ke alamat eksternal.

## Pengujian

Jalankan `php artisan test`. Test feature memakai SQLite in-memory dan tidak memodifikasi database pengembangan.
