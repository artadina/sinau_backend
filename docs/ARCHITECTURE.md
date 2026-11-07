# Arsitektur Panel Web Admin

Dokumen ini menjelaskan panel web Admin dan Superadmin sesuai [PRD](./PRD.md), menggunakan Laravel 12 (mengikuti `composer.json`), MySQL, Blade, Tailwind CSS melalui Vite, session Laravel, dan Sanctum untuk API mobile. Langkah 1 mengimplementasikan autentikasi web, pembatasan role, dan ganti password sementara. Langkah 2A mengimplementasikan pengelolaan modul dan unit; fitur Admin lainnya tetap menjadi target langkah berikutnya.

## 1. Struktur Laravel

```text
app/
  Enums/
    Role.php
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
  Services/
    ModulePublishValidator.php
    MaterialImportService.php
    MaterialContentSanitizer.php
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

Route login/reset, ganti password, logout, pengelolaan modul/unit Langkah 2A, route placeholder Admin, dan guard placeholder Superadmin sudah tersedia. Route fitur Admin lainnya pada tabel ini adalah rencana path. Semua route panel berada di balik autentikasi session kecuali endpoint login/reset.

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
| GET | `/admin/modules/{module}/edit` | Form edit modul | FR-A02 |
| PUT, DELETE | `/admin/modules/{module}` | Ubah/hapus modul; hapus ditolak bila ada riwayat tes (EC-22) | FR-A02 |
| PUT | `/admin/modules/{module}/status` | Terbit/draft; penerbitan memvalidasi unit, kuis unit, serta bank Pre/Post | FR-A04, A05, A11 |
| GET | `/admin/modules/{module}/units` | Daftar unit | FR-A03 |
| GET | `/admin/modules/{module}/units/create` | Form unit baru | FR-A03 |
| POST | `/admin/modules/{module}/units` | Simpan unit | FR-A03 |
| GET | `/admin/modules/{module}/units/{unit}/edit` | Form edit unit | FR-A03 |
| PUT, DELETE | `/admin/modules/{module}/units/{unit}` | Ubah/hapus unit; hapus ditolak bila ada riwayat kuis | FR-A03 |
| PATCH | `/admin/modules/{module}/units/{unit}/move/{direction}` | Atur urutan unit dengan tombol naik/turun | FR-A04 |
| GET | `/admin/units/{unit}/preview` | Pratinjau unit baca-saja | FR-A03 |
| — | (pemrosesan saat render) | Validasi URL YouTube dan ubah ID video ke YouTube embed; URL file langsung memakai pemutar video | FR-S07 |
| — | (pemrosesan browser) | Impor DOCX atau ekstraksi teks PDF digital tanpa mengunggah berkas sumber; DOC lama/PDF scan ditolak | FR-A16 |
| POST | `/admin/units/{unit}/material/images` | Unggah gambar materi dengan batas tipe, ukuran, dan jumlah | FR-A16 |
| GET | `/admin/units/{unit}/material/images/{image}` | Sajikan gambar materi untuk Admin/pratinjau | FR-A16 |
| GET | `/api/units/{unit}/material/images/{image}` | Sajikan gambar materi hanya jika modul berstatus terbit | FR-A16, FR-L11 |
| GET, POST | `/admin/modules/{module}/questions/{type}` | Daftar/tambah bank soal pada tab `pre` atau `post` | FR-A05 |
| POST | `/admin/modules/{module}/questions/{type}/bulk` | Validasi dan simpan beberapa soal dalam satu transaksi; tipe mengikuti tab | FR-A05, AC-48 |
| GET | `/admin/modules/{module}/questions/{type}/import` | Halaman pratinjau CSV terpisah; data CSV sementara disimpan di browser sebelum batch dikirim | FR-A05, AC-48 |
| GET, PUT, DELETE | `/admin/modules/{module}/questions/{type}/{question}` | Form ubah serta ubah/hapus soal yang terikat pada modul dan jenis tes | FR-A05 |
| PUT | `/admin/modules/{module}/question-settings` | Simpan jumlah soal Pre/Post dan opsi acak | FR-A05, AC-23 |
| GET, POST | `/admin/units/{unit}/quizzes` | Daftar/tambah kuis unit | FR-A11 |
| GET, PUT, DELETE | `/admin/units/{unit}/quizzes/{quiz}` | Form ubah serta ubah/hapus soal kuis yang terikat pada unit | FR-A11 |
| GET | `/admin/learners` | Daftar, cari, dan filter Learner; hanya baca | FR-A06 |
| GET | `/admin/results` | Rekap skor dan progres, dengan filter modul/periode | FR-A08 |
| GET | `/admin/results/export` | Ekspor rekap ke CSV | FR-A09 |
| GET | `/admin/forum` | Daftar topik dan balasan | FR-A13 |
| POST | `/admin/forum/{post}/replies` | Balas forum sebagai Admin/Pengajar | FR-A13 |
| DELETE | `/admin/forum/{post}` | Hapus topik beserta balasan dan selesaikan laporan terkait secara transaksional | FR-A14, FR-A15, EC-12 |
| GET | `/admin/forum-reports` | Daftar laporan dan rincian posting | FR-A15 |
| PUT | `/admin/forum-reports/{report}/resolve` | Tandai laporan selesai tanpa menghapus konten | FR-A15 |

Materi singkat menggunakan `units.reading_content` sebagai HTML terbatas yang disanitasi server. Editor mengizinkan paragraf, judul, bold, miring, daftar, dan gambar JPG/JPEG/PNG maksimal 5 MB per gambar serta 10 gambar per unit. Gambar disimpan secara privat dengan nama acak dan disajikan melalui route terkontrol; semua Admin dapat melihatnya melalui panel, sedangkan Learner hanya dapat melihat gambar dari modul terbit. Hapus aset unit yang tidak lagi dipakai hanya setelah penyimpanan berhasil. Impor DOCX dan ekstraksi teks PDF digital maksimal 10 MB per berkas serta 50 halaman PDF dilakukan di browser; berkas sumber tidak diunggah. DOC lama dan PDF scan/tanpa teks belum didukung dan ditolak dengan pesan bahasa Indonesia; OCR tidak dilakukan. Hasil impor masuk ke editor untuk ditinjau/diedit, sedangkan kegagalan ekstraksi menampilkan pesan ramah tanpa mengganti konten tersimpan. Semua komponen harus sesuai NFR-17 dan NFR-18 serta tidak memerlukan layanan berbayar.

URL video YouTube divalidasi pada Form Request dan dipetakan ke `youtube-nocookie.com/embed/{id}` menggunakan ID yang lolos allowlist format; jangan menaruh URL input langsung pada `iframe src`. Video langsung tetap memakai elemen `<video>`. Video YouTube harus publik dan mengizinkan embed untuk dapat diputar.

Bank soal menyimpan pilihan sebagai objek JSON berlabel `A`–`D`; minimal tiga pilihan harus tersedia dan `correct_key` harus menunjuk salah satunya. Admin dapat mengisi beberapa soal sekaligus atau mengimpor CSV di browser menggunakan kolom `question_text, option_a, option_b, option_c, option_d, correct_key, explanation`; baris impor ditampilkan dan dapat diedit sebelum dikirim. Server memvalidasi dan menyimpan seluruh batch dalam satu transaksi, dengan tipe soal diambil dari tab aktif. Jumlah Pre-test dan Post-test disimpan pada modul, tidak boleh melebihi bank soal masing-masing. Saat modul diterbitkan, setiap unit wajib memiliki 3–5 soal kuis serta bank Pre/Post harus mencukupi konfigurasi. Karena unit tidak memiliki status, jumlah kuis divalidasi pada penerbitan modul; penghapusan dari modul terbit ditolak bila menurunkan jumlah unit di bawah tiga. Seluruh pemeriksaan dijalankan pada server.

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
