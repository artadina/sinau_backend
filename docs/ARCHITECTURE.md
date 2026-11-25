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
      Superadmin/
    Middleware/
      EnsureRole.php
      EnsureModuleOwner.php
      EnsureEditableModule.php
      RequireAdminPasswordChange.php
    Requests/
      Auth/
      Admin/
      Superadmin/
  Models/
    User.php
    Module.php
    ModuleReview.php
    ModuleExtensionRequest.php
    Unit.php
    UnitQuiz.php
    TestQuestion.php
    TestAttempt.php
    ForumPost.php
    ForumReport.php
  Services/
    AdminResultsService.php
    MaterialHtmlSanitizer.php
    ModuleGovernanceService.php
    ModuleQualityGate.php
    ModuleVersionFamily.php
    VideoUrlParser.php
  Console/Commands/
    ProcessModuleGovernanceSchedule.php
resources/
  views/
    auth/
    layouts/admin.blade.php
    layouts/superadmin.blade.php
    admin/
    superadmin/
routes/
  web.php
  api.php
database/
  migrations/
  seeders/
tests/Feature/
```

Tabel `test_attempt_answers`, `learner_unit_progress`, `unit_quiz_attempts`, dan `user_badges` sudah ada pada skema, tetapi belum memiliki model Eloquent khusus di `app/Models`; kode terkait yang ada memakai Query Builder. Pengelolaan akun Superadmin memakai controller, Form Request, route binding role, dan view terpisah.

## 2. Kondisi Fitur

| Area | Kondisi implementasi saat ini |
|---|---|
| Autentikasi web Admin | Login, logout, lupa/reset password melalui email, session, serta ganti password sementara tersedia. Pengiriman email mengikuti konfigurasi Laravel; untuk pengembangan gunakan `MAIL_MAILER=log`. Reset password dibatasi pada akun Admin. |
| Akses role | Role backend `learner`, `admin`, `superadmin`. Middleware memisahkan route Admin/Superadmin; Admin dibatasi ke modul miliknya, Superadmin hanya mendapat pratinjau konten baca-saja dan endpoint keputusan governance, tanpa skor/forum. |
| Dashboard Admin | Ringkasan Learner, modul, tes selesai, skor, dan pintasan tersedia. |
| Modul dan Content Governance | Daftar Admin menampilkan modul; detail dan perubahan hanya tersedia untuk Admin pemilik. Admin membuat modul miliknya, mengelola draft/revisi, mengajukan review dan perpanjangan; Quality Gate PS-01–PS-20 dijalankan di server. Superadmin membaca detail/pratinjau, menyetujui, meminta revisi, takedown, dan memutuskan perpanjangan. Workflow tercatat, notifikasi dikirim, dan scheduler mengelola periode, arsip, pengingat H-14/H-3. Penghapusan ditolak bila ada riwayat tes atau kuis unit. |
| Unit dan kuis | CRUD unit, urutan naik/turun, pratinjau, bank soal kuis unit, dan penjagaan riwayat kuis tersedia. Editor materi, impor DOCX/PDF, serta gambar privat tersedia. |
| Bank soal tes | Bank soal Pre/Post mendukung format pilihan ganda, checkbox, dan benar/salah; pengaturan jumlah soal, input beberapa soal, pratinjau/impor CSV, validasi batch, dan penyimpanan transaksional tersedia. Kuis unit Admin juga mendukung ketiga format tersebut. |
| Forum dan laporan | Admin dapat membaca topik/balasan, membalas, menghapus posting, menyelesaikan laporan, dan menghapus topik beserta balasan sambil menutup laporan terkait dalam satu transaksi. |
| Learner dan rekap | Daftar Learner hanya-baca, rekap hasil, filter, dan ekspor CSV tersedia. Superadmin tidak memperoleh akses ke data skor. |
| Superadmin | Daftar, pencarian, detail, tambah Admin, ubah dan hapus akun Admin/Learner, ubah role lewat endpoint khusus, anonimisasi Learner, serta Content Governance tersedia. Login mengarah ke `/superadmin/admins`; akses dibatasi middleware dan route binding per role. Tidak ada route untuk menulis konten, forum, rekap, test attempts, atau skor. |
| Learner/mobile | Aplikasi Android, alur belajar, pengambilan tes/kuis, forum Learner, progres, dan lencana belum diimplementasikan. API saat ini hanya `GET /api/user` (Sanctum) dan endpoint gambar materi yang dibatasi role Learner. |
| Status aktif unit | Kolom `units.is_active` tersedia dan rekap menghitung unit aktif. Belum ada aksi UI/route Admin untuk menonaktifkan unit; daftar unit Admin tetap menampilkan semuanya. Alur Learner yang seharusnya menyembunyikan unit nonaktif belum tersedia. |

Kebutuhan PRD tetap menjadi target walaupun implementasinya belum ada. Secara khusus, sidebar saat ini memiliki lima item: Dashboard, Kelola Modul, Forum & Laporan, Daftar Learner, dan Rekap Hasil. Unit/kuis dan bank soal dicapai dari modul, bukan melalui dua item sidebar tersendiri seperti sitemap PRD 8.2. Perbedaan ini adalah gap implementasi, bukan perubahan kebutuhan.

## 3. Autentikasi dan Middleware Role

1. Login, lupa/reset password, dan logout memakai session Laravel. Tidak ada route registrasi web.
2. `EnsureRole` (alias `role`) memeriksa role dari akun terautentikasi. Akses role yang tidak sesuai menghasilkan HTTP 403; menyembunyikan menu hanya untuk UX.
3. Grup `/admin` memakai `auth`, `role:admin`, dan `temporary.password`. `EnsureModuleOwner` membatasi modul ke Admin pembuat; `EnsureEditableModule` menolak mutasi konten bila status tidak dapat diedit. Learner dan Superadmin ditolak pada route Admin.
4. `RequireAdminPasswordChange` (alias `temporary.password`) mengarahkan Admin dengan `is_temp_password = true` ke form penggantian password. Route form itu sendiri hanya untuk Admin yang sudah login. Perubahan role mandiri bukan fitur yang tersedia.
5. Grup `/superadmin` memakai `auth` dan `role:superadmin`; akun Admin/Learner dibatasi dengan route binding berdasarkan role. Superadmin memiliki daftar/detail modul dan pratinjau baca-saja serta endpoint keputusan governance, tetapi tidak memiliki route tulis isi modul, forum, rekap, test attempts, atau skor.
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
| `GET /modules`, `/modules/create`; `POST /modules` | Daftar modul dan buat draft baru | FR-A02 |
| `GET /modules/{module}`; `GET /modules/{module}/edit`; `PUT`, `DELETE /modules/{module}` | Detail/edit modul milik Admin; edit hanya sesuai status (minor edit khusus modul published); hapus ditolak bila ada riwayat tes atau kuis unit | FR-A02, PS-30, EC-22 |
| `POST /modules/{module}/submit`, `POST /modules/{module}/revision`, `POST /modules/{module}/extensions` | Ajukan review/ulang, buat salinan versi, dan minta perpanjangan | FR-A17, PS-01–PS-30 |
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
| GET | `/superadmin` | Arahkan ke daftar akun Admin; Superadmin saja |
| GET | `/superadmin/modules`, `/superadmin/modules/{module}` | Antrean dan detail/pratinjau modul baca-saja |
| POST | `/superadmin/modules/{module}/approve`, `/revision`, `/takedown` | Setujui, minta revisi, atau takedown; Superadmin saja |
| POST | `/superadmin/extensions/{extension}/decision` | Setujui/tolak permintaan perpanjangan |
| GET, POST | `/superadmin/admins`, `/superadmin/admins/create` | Daftar, pencarian, dan tambah Admin; password sementara hanya ditampilkan satu kali |
| GET, PUT, DELETE | `/superadmin/admins/{user}` | Detail, ubah, dan soft-delete Admin; binding hanya untuk role Admin |
| PUT | `/superadmin/admins/{user}/role` | Ubah role Admin menjadi `learner` atau `admin` |
| GET | `/superadmin/learners` | Daftar, pencarian, dan pagination akun Learner tanpa skor |
| GET, PUT, DELETE | `/superadmin/learners/{user}` | Detail, ubah, dan soft-delete + anonimisasi Learner; binding hanya untuk role Learner |
| PUT | `/superadmin/learners/{user}/role` | Ubah role Learner menjadi `learner` atau `admin` |
| GET | `/api/user` | Mengembalikan pengguna yang terautentikasi dengan Sanctum |
| GET | `/api/units/{unit}/material/images/{image}` | Learner terautentikasi saja; gambar hanya tersedia jika modul published dalam periode aktif |

Tidak ada route lain untuk alur belajar mobile atau API CRUD Superadmin saat ini.

## 5. Validasi Konten dan Materi

- Pengajuan review mensyaratkan Quality Gate PS-01–PS-20; semua unit harus lengkap dan memiliki 3–5 kuis serta bank Pre/Post sesuai standar. Validasi dilakukan server-side terhadap semua unit modul, termasuk unit dengan `is_active = false`.
- Form unit memvalidasi judul, tujuan, URL video, durasi positif opsional, urutan unik per modul, dan maksimal 10 gambar referensi. Durasi dari input menit dikonversi ke detik dan dapat `NULL`.
- Unit dengan riwayat kuis tidak dapat dihapus. Modul dengan riwayat tes atau kuis unit tidak dapat dihapus; bila akses perlu ditutup, Superadmin dapat melakukan takedown melalui workflow.
- Materi disimpan pada `units.reading_content` sebagai HTML terbatas dan disanitasi server. DOCX dan PDF digital diproses di browser (maksimal 10 MB; PDF maksimal 50 halaman). Gambar JPG/PNG maksimal 5 MB per file dan 10 per unit, disimpan privat melalui route terkontrol.
- Nilai tes dihitung dan jawaban disimpan per attempt. Rekap hanya menggunakan tes selesai dan mengambil attempt terbaru; ekspor CSV memuat semua baris yang cocok filter dan menetralkan sel yang diawali formula.

## 6. Garis Besar API Learner

API menggunakan token Sanctum, tetapi belum menyediakan fitur belajar. Saat ini hanya autentikasi pengguna (`GET /api/user`) dan pengambilan gambar materi untuk Learner pada modul terbit yang tersedia. Endpoint registrasi, profil, modul, unit, kuis, tes, jawaban, progres, lencana, forum, dan pelaporan masih belum ada. Ketika endpoint tersebut dibangun, akses data harus dibatasi pada akun pemilik dan role yang sesuai; role pengguna tidak boleh ditetapkan dari input umum klien.

## 7. Gap terhadap PRD

| Area | Status |
|---|---|
| Sitemap Admin PRD 8.2 | Belum sama persis: dua menu Kelola Unit & Kuis dan Kelola Bank Soal belum menjadi item sidebar sendiri. |
| Penonaktifan unit | Kolom skema dan penggunaan aktif di rekap tersedia; aksi Admin serta perilaku Learner belum tersedia. |
| Superadmin | Manajemen akun Admin/Learner serta Content Governance tersedia; tidak memiliki akses tulis isi modul maupun skor/forum/rekap. |
| Akun Learner | Daftar Admin hanya-baca tersedia; alur permintaan hapus akun dan anonimisasi belum tersedia. |
| Produk Learner/mobile | Sebagian besar belum tersedia; lihat cakupan API di atas. |

Status di atas tidak mengubah requirement PRD. Perubahan dokumen ini hanya membedakan kebutuhan produk dari apa yang benar-benar tersedia di kode saat ini.
