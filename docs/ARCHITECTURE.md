# Arsitektur dan Status Implementasi

Dokumen ini mencatat kondisi kode saat ini, bukan pengganti kebutuhan produk pada [PRD](./PRD.md) atau [BRD](./BRD.md). Stack backend dan panel web adalah Laravel 12, PHP 8.2+, MySQL, Blade, Tailwind CSS melalui Vite, dan autentikasi web berbasis session. Sanctum tersedia untuk autentikasi API. Teknologi aplikasi Android belum dipilih. Teks UI dan validasi menggunakan bahasa Indonesia.

## 1. Struktur Laravel Saat Ini

```text
app/
  Enums/Role.php
  Http/
    Controllers/Web/
      Auth/AdminAuthController.php
      Admin/
    Middleware/
      EnsureRole.php
      RequireAdminPasswordChange.php
    Requests/
      Auth/
      Admin/
  Models/
    User.php
    Module.php
    Unit.php
    UnitQuiz.php
    TestQuestion.php
    TestAttempt.php
    ForumPost.php
    ForumReport.php
  Services/
    AdminResultsService.php
    MaterialHtmlSanitizer.php
    ModulePublishValidator.php
    VideoUrlParser.php
resources/
  views/
    auth/
    layouts/admin.blade.php
    admin/
routes/
  web.php
  api.php
database/
  migrations/
  seeders/
tests/Feature/
```

Tabel `test_attempt_answers`, `learner_unit_progress`, `unit_quiz_attempts`, dan `user_badges` sudah ada pada skema, tetapi belum memiliki model Eloquent khusus di `app/Models`; kode terkait yang ada memakai Query Builder. Tidak ada controller, Form Request, view, atau service Superadmin untuk pengelolaan akun.

## 2. Kondisi Fitur

| Area | Kondisi implementasi saat ini |
|---|---|
| Autentikasi web Admin | Login, logout, lupa/reset password melalui email, session, serta ganti password sementara tersedia. Pengiriman email mengikuti konfigurasi Laravel; untuk pengembangan gunakan `MAIL_MAILER=log`. Reset password dibatasi pada akun Admin. |
| Akses role | Role backend `learner`, `admin`, `superadmin`. Route Admin menolak Superadmin dan Learner; route placeholder `/superadmin` hanya dapat diakses Superadmin. |
| Dashboard Admin | Ringkasan Learner, modul, tes selesai, skor, dan pintasan tersedia. |
| Modul | CRUD, sampul, status draft/terbit; validasi penerbitan berjalan di server. Penghapusan ditolak jika modul punya riwayat tes atau kuis unit. |
| Unit dan kuis | CRUD unit, urutan naik/turun, pratinjau, bank soal kuis unit, dan penjagaan riwayat kuis tersedia. Editor materi, impor DOCX/PDF, serta gambar privat tersedia. |
| Bank soal tes | Bank soal Pre/Post, pengaturan jumlah soal, input beberapa soal, pratinjau/impor CSV, validasi batch, dan penyimpanan transaksional tersedia. |
| Forum dan laporan | Admin dapat membaca topik/balasan, membalas, menghapus posting, menyelesaikan laporan, dan menghapus topik beserta balasan sambil menutup laporan terkait dalam satu transaksi. |
| Learner dan rekap | Daftar Learner hanya-baca, rekap hasil, filter, dan ekspor CSV tersedia. Superadmin tidak memperoleh akses ke data skor. |
| Superadmin | Belum ada fitur pengelolaan akun Admin/Learner atau pengubahan role. Yang tersedia hanya guard role pada `GET /superadmin`; setelah login, Superadmin diarahkan ke halaman placeholder. |
| Learner/mobile | Aplikasi Android, alur belajar, pengambilan tes/kuis, forum Learner, progres, dan lencana belum diimplementasikan. API saat ini hanya `GET /api/user` (Sanctum) dan endpoint gambar materi yang dibatasi role Learner. |
| Status aktif unit | Kolom `units.is_active` tersedia dan rekap menghitung unit aktif. Belum ada aksi UI/route Admin untuk menonaktifkan unit; daftar unit Admin tetap menampilkan semuanya. Alur Learner yang seharusnya menyembunyikan unit nonaktif belum tersedia. |

Kebutuhan PRD tetap menjadi target walaupun implementasinya belum ada. Secara khusus, sidebar saat ini memiliki lima item: Dashboard, Kelola Modul, Forum & Laporan, Daftar Learner, dan Rekap Hasil. Unit/kuis dan bank soal dicapai dari modul, bukan melalui dua item sidebar tersendiri seperti sitemap PRD 8.2. Perbedaan ini adalah gap implementasi, bukan perubahan kebutuhan.

## 3. Autentikasi dan Middleware Role

1. Login, lupa/reset password, dan logout memakai session Laravel. Tidak ada route registrasi web.
2. `EnsureRole` (alias `role`) memeriksa role dari akun terautentikasi. Akses role yang tidak sesuai menghasilkan HTTP 403; menyembunyikan menu hanya untuk UX.
3. Grup `/admin` memakai `auth`, `role:admin`, dan `temporary.password`. Learner dan Superadmin ditolak pada seluruh fungsi Admin, termasuk konten, forum, dan rekap.
4. `RequireAdminPasswordChange` (alias `temporary.password`) mengarahkan Admin dengan `is_temp_password = true` ke form penggantian password. Route form itu sendiri hanya untuk Admin yang sudah login. Perubahan role mandiri bukan fitur yang tersedia.
5. `GET /superadmin` memakai `auth` dan `role:superadmin`, tetapi tidak mempunyai fitur selain halaman placeholder. Tidak ada route pengelolaan akun atau konten untuk Superadmin.
6. API menggunakan `auth:sanctum`. Endpoint gambar materi Learner menambahkan `role:learner`; endpoint Admin untuk gambar memakai grup role Admin. Skor tidak tersedia pada API Learner saat ini.

## 4. Pemetaan Route Web Saat Ini

### 4.1 Autentikasi

| Method | URI | Akses / fungsi | FR |
|---|---|---|---|
| GET, POST | `/login` | Publik; form dan autentikasi panel | FR-A01, FR-SA01 |
| GET, POST | `/forgot-password` | Publik; meminta tautan reset untuk akun Admin | FR-A01 |
| GET, POST | `/reset-password/{token}`, `/reset-password` | Publik; form dan pemrosesan reset password Admin | FR-A01 |
| GET, PUT | `/password/temporary` | Admin terautentikasi; ubah password sementara | FR-A12, FR-S06 |
| POST | `/logout` | Pengguna terautentikasi | FR-A01 |

### 4.2 Admin

Semua URI berikut memakai prefix `/admin` dan middleware `auth`, `role:admin`, serta pemeriksaan password sementara. `GET /admin` mengarahkan ke `/admin/dashboard`.

| URI / method | Fungsi yang tersedia | FR |
|---|---|---|
| `GET /dashboard` | Dashboard ringkas | FR-A10 |
| `GET /learners` | Daftar, pencarian, dan filter Learner; tanpa edit/hapus | FR-A06 |
| `GET /results`, `GET /results/export` | Rekap, filter, dan ekspor CSV | FR-A08, FR-A09 |
| `GET /forum`, `GET /forum/{post}` | Daftar topik dan detail percakapan | FR-A13, FR-A15 |
| `POST /forum/{post}/replies` | Balas sebagai Admin/Pengajar | FR-A13 |
| `DELETE /forum/{post}` | Hapus posting; laporan terkait ditutup, penghapusan topik juga menghapus turunannya | FR-A14, FR-A15, EC-12 |
| `GET /forum-reports`, `PUT /forum-reports/{report}/resolve` | Daftar/filter laporan dan tandai selesai | FR-A15 |
| `GET /modules`, `/modules/create`; `POST /modules` | Daftar dan buat modul | FR-A02 |
| `GET /modules/{module}/edit`; `PUT`, `DELETE /modules/{module}` | Ubah/hapus modul; hapus ditolak bila ada riwayat tes atau kuis unit | FR-A02, EC-22 |
| `PUT /modules/{module}/status` | Ubah draft/terbit; publikasi memvalidasi isi modul | FR-A04, FR-A05, FR-A11 |
| `/modules/{module}/units` | Daftar, tambah, ubah, dan hapus unit | FR-A03 |
| `PATCH /modules/{module}/units/{unit}/move/{direction}` | Atur urutan unit naik/turun | FR-A04 |
| `GET /units/{unit}/preview` | Pratinjau unit | FR-A03 |
| `/modules/{module}/questions/{type}` | Kelola bank soal `pre` atau `post`, termasuk tambah, ubah, dan hapus | FR-A05 |
| `POST /modules/{module}/questions/{type}/bulk`; `GET .../import` | Simpan batch tervalidasi atau buka pratinjau impor CSV | FR-A05 |
| `PUT /modules/{module}/question-settings` | Atur jumlah soal Pre/Post dan pengacakan | FR-A05 |
| `/units/{unit}/quizzes` | Kelola soal kuis unit | FR-A11 |
| `POST /units/{unit}/material/images`; `GET /units/{unit}/material/images/{image}` | Unggah dan tampilkan gambar materi privat | FR-A16 |
| `GET /{page}` untuk `questions` | Placeholder panel | — |

### 4.3 Route lainnya

| Method | URI | Kondisi |
|---|---|---|
| GET | `/superadmin` | Guard role Superadmin dan halaman placeholder; belum ada pengelolaan akun |
| GET | `/api/user` | Mengembalikan pengguna yang terautentikasi dengan Sanctum |
| GET | `/api/units/{unit}/material/images/{image}` | Learner terautentikasi saja; gambar hanya tersedia jika modul terbit |

Tidak ada route lain untuk alur belajar mobile atau API CRUD Superadmin saat ini.

## 5. Validasi Konten dan Materi

- Penerbitan modul memerlukan setidaknya satu unit; setiap unit harus memiliki 3–5 kuis; jumlah soal Pre/Post harus diatur dan tersedia di bank soal. Validasi dilakukan server-side terhadap semua unit modul, termasuk unit dengan `is_active = false`.
- Form unit memvalidasi judul, tujuan, URL video, durasi positif opsional, urutan unik per modul, dan maksimal 10 gambar referensi. Durasi dari input menit dikonversi ke detik dan dapat `NULL`.
- Unit dengan riwayat kuis tidak dapat dihapus. Modul dengan riwayat tes atau kuis unit tidak dapat dihapus; pesan mengarahkan Admin mengembalikannya ke draft.
- Materi disimpan pada `units.reading_content` sebagai HTML terbatas dan disanitasi server. DOCX dan PDF digital diproses di browser (maksimal 10 MB; PDF maksimal 50 halaman). Gambar JPG/PNG maksimal 5 MB per file dan 10 per unit, disimpan privat melalui route terkontrol.
- Nilai tes dihitung dan jawaban disimpan per attempt. Rekap hanya menggunakan tes selesai dan mengambil attempt terbaru; ekspor CSV memuat semua baris yang cocok filter dan menetralkan sel yang diawali formula.

## 6. Garis Besar API Learner

API menggunakan token Sanctum, tetapi belum menyediakan fitur belajar. Saat ini hanya autentikasi pengguna (`GET /api/user`) dan pengambilan gambar materi untuk Learner pada modul terbit yang tersedia. Endpoint registrasi, profil, modul, unit, kuis, tes, jawaban, progres, lencana, forum, dan pelaporan masih belum ada. Ketika endpoint tersebut dibangun, akses data harus dibatasi pada akun pemilik dan role yang sesuai; role pengguna tidak boleh ditetapkan dari input umum klien.

## 7. Gap terhadap PRD

| Area | Status |
|---|---|
| Sitemap Admin PRD 8.2 | Belum sama persis: dua menu Kelola Unit & Kuis dan Kelola Bank Soal belum menjadi item sidebar sendiri. |
| Penonaktifan unit | Kolom skema dan penggunaan aktif di rekap tersedia; aksi Admin serta perilaku Learner belum tersedia. |
| Superadmin | Login/guard placeholder saja; manajemen akun, perubahan role, dan anonimisasi belum tersedia. |
| Akun Learner | Daftar Admin hanya-baca tersedia; alur permintaan hapus akun dan anonimisasi belum tersedia. |
| Produk Learner/mobile | Sebagian besar belum tersedia; lihat cakupan API di atas. |

Status di atas tidak mengubah requirement PRD. Perubahan dokumen ini hanya membedakan kebutuhan produk dari apa yang benar-benar tersedia di kode saat ini.
