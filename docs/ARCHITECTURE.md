# Arsitektur Panel Web Admin

Dokumen ini menjelaskan panel web Admin dan Superadmin sesuai [PRD](./PRD.md), menggunakan Laravel 12 (mengikuti `composer.json`), MySQL, Blade, Tailwind CSS melalui Vite, session Laravel, dan Sanctum untuk API mobile. Langkah 1 mengimplementasikan autentikasi web, pembatasan role, ganti password sementara, dan placeholder navigasi Admin; route pengelolaan fitur lainnya di bawah tetap menjadi target langkah berikutnya.

## 1. Struktur Laravel

```text
app/
  Http/
    Controllers/
      Web/
        Auth/
        Admin/
        Superadmin/
    Middleware/
      EnsureWebRole.php
      RequirePasswordChange.php
    Requests/
      Auth/
      Admin/
      Superadmin/
  Models/
    User.php
    Module.php
    Unit.php
    UnitQuiz.php
    TestQuestion.php
    TestAttempt.php
    TestAttemptAnswer.php
    LearnerUnitProgress.php
    UnitQuizAttempt.php
    UserBadge.php
    ForumPost.php
    ForumReport.php
resources/
  views/
    layouts/
    components/
    auth/
    admin/
      dashboard/
      modules/
      units/
      questions/
      learners/
      results/
      forum/
    superadmin/
      admins/
      learners/
routes/
  web.php
  api.php
```

`routes/web.php` hanya memuat endpoint browser berbasis session. Controller mengorkestrasi permintaan, Form Request memvalidasi payload, Model memetakan tabel/relasi, dan Blade merender tampilan berbahasa Indonesia. Komponen Tailwind dibangun melalui Vite, bukan CDN. `routes/api.php` memakai Sanctum untuk klien mobile; endpoint API bukan cara alternatif untuk mengakses panel.

## 2. Strategi autentikasi dan middleware role

1. Rute panel memakai `web` dan `auth`; login, reset password, dan logout memakai session bawaan Laravel. Akun panel tidak memiliki registrasi publik.
2. `EnsureWebRole` mengizinkan hanya role yang ditentukan pada grup route. Role diambil dari pengguna terautentikasi di backend. Menu yang tidak berwenang disembunyikan sebagai UX, tetapi middleware tetap menjadi pembatas sebenarnya (FR-S02, FR-S05, FR-SA07).
3. Grup Admin hanya menerima `admin`; Grup Superadmin hanya menerima `superadmin`. Learner ditolak dari semua route panel. Superadmin tidak menerima route konten, skor, rekap, atau forum Admin; Admin tidak menerima route pengelolaan akun Superadmin.
4. `RequirePasswordChange` memeriksa `is_temp_password` setelah autentikasi dan mengizinkan hanya route penggantian password serta logout sampai password diganti (FR-A12, FR-S06).
5. Semua Form Request menolak role pada registrasi dan perubahan profil mandiri. Perubahan role yang disediakan FR-SA04/FR-SA06 memakai operasi khusus Superadmin: UI mengirim role tujuan untuk akun Admin/Learner yang dikelola, lalu backend memeriksa autentikasi dan otorisasi Superadmin serta memvalidasi role tujuan terhadap role yang diizinkan. Operasi ini tidak memberi akses ke pengguna biasa untuk mengganti rolenya sendiri.
6. Route yang membaca skor/progres harus tetap membatasi data Learner berdasarkan pemilik atau role Admin; Superadmin tidak boleh memperoleh skor lewat URL atau API (FR-S04).

## 3. Rute web yang direncanakan

Route login/reset, ganti password, logout, route placeholder Admin, dan guard placeholder Superadmin sudah tersedia. Route operasi fitur Admin lainnya pada tabel ini adalah rencana path. Semua route panel berada di balik autentikasi session kecuali endpoint login/reset.

### 3.1 Autentikasi bersama

| Method | URI | Akses | FR |
|---|---|---|---|
| GET, POST | `/login` | Publik | FR-A01, FR-SA01 |
| GET, POST | `/forgot-password`, `/reset-password/{token}` | Publik | FR-A01, FR-SA01 |
| POST | `/logout` | Session terautentikasi | FR-A01, FR-SA01 |
| GET, PUT | `/password/temporary` | Admin dengan password sementara | FR-A12, FR-S06 |

### 3.2 Route Admin

Prefix `/admin`, middleware `auth` + `role:admin` + pemeriksaan password sementara.

| Method | URI | Fungsi | FR |
|---|---|---|---|
| GET | `/admin` | Dashboard ringkas | FR-A10 |
| GET | `/admin/modules` | Daftar modul | FR-A02 |
| GET | `/admin/modules/create` | Form modul baru | FR-A02 |
| POST | `/admin/modules` | Simpan modul baru | FR-A02 |
| GET | `/admin/modules/{module}` | Detail modul | FR-A02 |
| GET | `/admin/modules/{module}/edit` | Form edit modul | FR-A02 |
| PUT/PATCH, DELETE | `/admin/modules/{module}` | Ubah/hapus modul; hapus ditolak bila ada riwayat tes (EC-22) | FR-A02 |
| PUT | `/admin/modules/{module}/status` | Terbitkan atau kembalikan ke draft | FR-A04, FR-A11 |
| GET, POST | `/admin/modules/{module}/units` | Daftar dan buat unit | FR-A03 |
| GET | `/admin/units/{unit}` | Detail unit | FR-A03 |
| PUT/PATCH, DELETE | `/admin/units/{unit}` | Ubah/hapus unit; penghapusan fisik ditolak bila ada riwayat kuis | FR-A03 |
| PATCH | `/admin/units/{unit}/status` | Aktifkan/nonaktifkan unit; unit nonaktif tidak tersedia untuk belajar baru dan riwayat tetap disimpan | FR-A03, FR-L30 |
| PUT | `/admin/modules/{module}/units/order` | Atur urutan unit | FR-A04 |
| GET | `/admin/units/{unit}/preview` | Pratinjau unit | FR-A03 |
| GET, POST | `/admin/modules/{module}/questions` | Daftar/tambah bank soal pre/post | FR-A05 |
| PUT, DELETE | `/admin/questions/{question}` | Ubah/hapus bank soal | FR-A05 |
| GET, POST | `/admin/units/{unit}/quizzes` | Daftar/tambah kuis unit | FR-A11 |
| PUT, DELETE | `/admin/unit-quizzes/{question}` | Ubah/hapus soal kuis unit | FR-A11 |
| GET | `/admin/learners` | Daftar, cari, dan filter Learner; hanya baca | FR-A06 |
| GET | `/admin/results` | Rekap skor dan progres, dengan filter modul/periode | FR-A08 |
| GET | `/admin/results/export` | Ekspor rekap ke CSV | FR-A09 |
| GET | `/admin/forum` | Daftar topik dan balasan | FR-A13 |
| POST | `/admin/forum/{post}/replies` | Balas forum sebagai Admin/Pengajar | FR-A13 |
| DELETE | `/admin/forum/{post}` | Hapus topik beserta balasan dan selesaikan laporan terkait secara transaksional | FR-A14, FR-A15, EC-12 |
| GET | `/admin/forum-reports` | Daftar laporan dan rincian posting | FR-A15 |
| PUT | `/admin/forum-reports/{report}/resolve` | Tandai laporan selesai tanpa menghapus konten | FR-A15 |

### 3.3 Route Superadmin

Prefix `/superadmin`, middleware `auth` + `role:superadmin` + pemeriksaan password sementara bila berlaku. Tidak ada endpoint konten, forum Admin, skor, ataupun rekap.

| Method | URI | Fungsi | FR |
|---|---|---|---|
| GET | `/superadmin/admins` | Daftar akun Admin | FR-SA03 |
| GET, POST | `/superadmin/admins/create`, `/superadmin/admins` | Form dan pembuatan akun Admin; password sementara dibuat sistem | FR-SA02 |
| GET, PUT, DELETE | `/superadmin/admins/{user}` | Detail, ubah, dan hapus akun Admin | FR-SA03 |
| PUT | `/superadmin/admins/{user}/role` | Ubah role Admin melalui operasi khusus | FR-SA04 |
| GET | `/superadmin/learners` | Daftar/cari akun Learner | FR-SA05 |
| GET, PUT, DELETE | `/superadmin/learners/{user}` | Detail, ubah, dan hapus/anonimkan akun Learner | FR-SA05 |
| PUT | `/superadmin/learners/{user}/role` | Ubah role Learner melalui operasi khusus | FR-SA06 |

## 4. Garis besar API Learner

API mobile dipisahkan dari route panel dan memakai autentikasi token Sanctum. Semua operasi Learner membatasi query pada pengguna yang terautentikasi; role akun baru ditetapkan backend. Kelompok kapabilitas meliputi registrasi/login/consent/profil, daftar modul terbit dan progres, unit/video/progres, pre/post-test dan jawaban, kuis unit, riwayat dan lencana milik sendiri, forum modul, serta pelaporan posting. Pemeriksaan pre-test selesai untuk forum dan seluruh aturan akses data dijalankan server-side.

Tidak ada route API mobile yang memberi Superadmin akses ke skor atau route web yang memberi Learner akses ke panel.
