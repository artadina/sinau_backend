# Product Requirements Document (PRD) — SINAU

**Platform Pembelajaran Mandiri Berbasis Mobile untuk Meningkatkan Literasi Data dan Kesiapan Kerja Pelaku UMKM**

Disusun oleh Tim Power Puff — Program Studi S1 Sistem Informasi, Fakultas Rekayasa Industri, Universitas Telkom — Oktober 2026

| NIM | Nama |
|---|---|
| 102022300206 | Artadina Septiani |
| 102022300234 | Kheista Anaya |
| 102022300399 | Jasmine Adinda Wibowo |

## Kontrol Dokumen

| Atribut | Keterangan |
|---|---|
| Nama dokumen | Product Requirements Document (PRD) Sinau |
| Versi / status | 1.1 / Draft untuk ditinjau |
| Tanggal | 31 Oktober 2026 |
| Penyusun | Tim Power Puff (Artadina Septiani, Kheista Anaya, Jasmine Adinda Wibowo) |
| Mitra | Ibu Nur, pemilik Pasta Nafisa (salah satu mitra UMKM) |
| Dosen pendamping | Murahartawaty |
| Dokumen acuan | [BRD Sinau v1.2](./BRD.md); [Sinau ERD dan Flowchart](./ERD.md) (Gambar 1–5); Proposal Capstone Tim Power Puff; Daftar Kebutuhan Fungsional (FR) dan List Fitur |

### Riwayat Revisi

| Versi | Tanggal | Perubahan |
|---|---|---|
| 1.0 | 31 Oktober 2026 | Penyusunan awal PRD berdasarkan BRD v1.2 serta ERD dan flowchart Sinau. |
| 1.1 | 31 Oktober 2026 | Menambahkan kebutuhan fungsional (FR) lengkap per area, kebutuhan non-fungsional (NFR), skenario masalah, aturan bisnis dan keputusan tim, validasi field, sitemap, kasus pengecualian, data seed, kriteria penerimaan, urutan pengerjaan, deliverable, dan matriks keterlacakan. User story diringkas; kriteria rinci dipindahkan ke FR dan kriteria penerimaan. |

### Cara Membaca Dokumen

BRD menjelaskan mengapa Sinau dibuat dan kebutuhan bisnisnya (BR-xx). PRD ini menjelaskan apa yang dibangun: kebutuhan fungsional (FR-xx) dan non-fungsional (NFR-xx), aturan bisnis (AB-xx), data, halaman, kasus pengecualian (EC-xx), dan kriteria penerimaan (AC-xx). Bagian 16 menelusuri setiap kebutuhan bisnis BRD sampai ke FR, user story, dan kriteria penerimaannya.

## Daftar Isi

1. [Ringkasan Produk](#1-ringkasan-produk)
2. [Ruang Lingkup](#2-ruang-lingkup)
3. [Pengguna dan Peran](#3-pengguna-dan-peran)
4. [Aturan Bisnis dan Keputusan Tim](#4-aturan-bisnis-dan-keputusan-tim)
5. [Kebutuhan Fungsional](#5-kebutuhan-fungsional)
6. [User Story](#6-user-story)
7. [Kebutuhan Data dan Validasi](#7-kebutuhan-data-dan-validasi)
8. [Daftar Halaman (Sitemap)](#8-daftar-halaman-sitemap)
9. [Alur Utama dan Kasus Pengecualian](#9-alur-utama-dan-kasus-pengecualian)
10. [Kebutuhan Non-Fungsional](#10-kebutuhan-non-fungsional)
11. [Data Seed Awal](#11-data-seed-awal)
12. [Kriteria Penerimaan](#12-kriteria-penerimaan-acceptance-criteria)
13. [Asumsi dan Ketergantungan](#13-asumsi-dan-ketergantungan)
14. [Rencana Pengerjaan](#14-rencana-pengerjaan)
15. [Deliverable Akhir](#15-deliverable-akhir)
16. [Matriks Keterlacakan](#16-matriks-keterlacakan)
17. [Pertanyaan Terbuka](#17-pertanyaan-terbuka)
18. [Persetujuan](#18-persetujuan)

---

## 1. Ringkasan Produk

Sinau adalah platform pembelajaran mandiri (Learning Management System) berbasis mobile untuk pelaku UMKM dan stafnya. Learner belajar lewat video pendek dan materi singkat di aplikasi Android, mengerjakan kuis unit, pre-test, dan post-test, berdiskusi di forum, dan memantau skor serta progresnya. Admin (Pengajar) dan Superadmin memakai panel web: Admin mengelola modul, unit, soal, rekap hasil, dan forum, sedangkan Superadmin khusus mengelola akun Admin dan Learner. Pasta Nafisa menjadi contoh kasus mitra; platform dirancang umum untuk UMKM lain, dengan dua modul sampel: Supply Chain Management dan Product Identity.

### 1.1 Masalah yang Diselesaikan

- Pengetahuan dari pelatihan satu atau dua kali mudah terlupakan karena tidak ada tempat untuk mengulang.
- Pelatihan tidak merata; staf bergantung pada penjelasan ulang pemilik.
- Staf hanya punya waktu di sela pekerjaan, mengandalkan ponsel, dan takut salah pada sistem yang rumit.
- Tidak ada tempat bertanya saat bingung, dan tidak ada cara mengukur apakah belajar berdampak.

Skenario masalah di lapangan:

| ID | Skenario | Dampak | Dijawab oleh |
|---|---|---|---|
| PS-01 | Staf mengikuti pelatihan sekali, beberapa minggu kemudian lupa isi materi dan tidak ada tempat untuk mengulang | Pengetahuan hilang, praktik usaha tidak berubah | FR-L10 – L12, L14 |
| PS-02 | Hanya pemilik yang pernah dilatih; staf baru belajar informal dan menunggu penjelasan pemilik | Pelatihan tidak merata, pemilik terbebani | FR-L01, L08, L09 |
| PS-03 | Staf hanya punya waktu di sela pekerjaan atau pergantian shift dan hanya membawa ponsel | Materi panjang tidak selesai dipelajari | FR-L10, L14, L15 |
| PS-04 | Sistem digital yang pernah dicoba dinilai rumit (satu pesanan sekitar 15 menit) dan staf takut salah | Sistem tidak dipakai | FR-L01, L03, L07, L17 |
| PS-05 | Staf bingung saat membaca unit dan tidak ada tempat bertanya selain pemilik | Pemahaman terhenti | FR-L31 – L34, A13 |
| PS-06 | Staf tidak tahu apakah sudah paham sebelum lanjut ke materi berikutnya | Materi berikutnya sulit dipahami | FR-L28 – L30 |
| PS-07 | Pemilik atau pengajar tidak punya bukti apakah pelatihan menaikkan pemahaman | Tidak ada dasar evaluasi | FR-L20, L24, A08, A09 |
| PS-08 | Konten forum tidak pantas, atau orang yang tidak berwenang membuka data skor dan fungsi pengajar | Suasana belajar dan data tidak aman | FR-L35, A14, A15, S01, S02, S04, SA07 |

### 1.2 Tujuan Produk

1. Materi dapat diulang kapan saja dan dilanjutkan dari posisi terakhir lewat ponsel.
2. Alur belajar terstruktur: pre-test, unit dengan kuis, lalu post-test, tanpa tekanan.
3. Pemahaman terukur dan skor hanya terlihat oleh pemilik dan Admin.
4. Learner dapat saling membantu di forum dengan moderasi yang terkendali oleh Admin.
5. Wewenang Admin dan Superadmin terpisah dan ditegakkan di backend.

### 1.3 Indikator Keberhasilan

| Indikator | Target |
|---|---|
| Seluruh kebutuhan berprioritas Must pada bagian 5 terpenuhi | 100% |
| Akses halaman aplikasi atau panel tanpa login (AC-01) | Selalu dialihkan ke halaman login |
| Learner mengakses rute panel web (AC-07) | Ditolak sistem |
| Admin mengakses rute Superadmin, atau sebaliknya (AC-09, AC-10) | Ditolak sistem |
| Post-test dibuka sebelum semua unit selesai (AC-27) | Terkunci |
| Unit diterbitkan dengan kuis kurang dari 3 atau lebih dari 5 soal (AC-24) | Ditolak sistem |
| Learner mengakses skor Learner lain (AC-31) | Ditolak sistem |
| Perhitungan skor tes (AC-28) | Akurat pada semua skenario uji |

Metrik dampak setelah sistem dipakai:

| Metrik | Target | Cara mengukur |
|---|---|---|
| Kemudahan penggunaan (SUS) | Rata-rata minimal 68 | Kuesioner SUS pada uji pengguna |
| Skor post-test dibanding pre-test | Rata-rata post lebih tinggi | Ekspor CSV rekap hasil (AC-39) |
| Penerimaan pengguna (TAM) | Dilaporkan dan dianalisis | Kuesioner TAM pada uji pengguna |
| Unit dapat dilanjutkan dari posisi terakhir | 100% | Uji skenario AC-15 |
| Kesesuaian skor di aplikasi dengan rekap CSV | 100% | Pencocokan sampel oleh tim |
| Waktu dari membuka aplikasi sampai video unit diputar (Learner terdaftar) | Kurang dari 1 menit (usulan) | Uji coba saat uji pengguna |
| Laporan forum yang ditindaklanjuti Admin | 100% berstatus Resolved atau kontennya dihapus | Daftar laporan pada panel Admin |
| Modul sampel terbit | 2 modul lengkap (unit, kuis unit, pre-test, post-test) | Status modul pada panel Admin |

> Target yang bertanda "usulan" adalah angka usulan tim dan perlu disepakati bersama dosen pembimbing dan mitra.

## 2. Ruang Lingkup

### 2.1 Dalam Lingkup

- Aplikasi mobile Android untuk Learner dan panel web untuk Admin dan Superadmin (satu aplikasi web, hak akses dipisahkan middleware).
- Registrasi mandiri Learner, persetujuan data (consent), login, logout, reset password lewat email, profil, dan hapus akun.
- Modul berisi unit; tiap unit berisi video pendek (tautan, diputar di aplikasi), materi singkat, dan kuis atau studi kasus 3–5 soal.
- Pre-test, post-test, skor otomatis, pembahasan, riwayat skor, grafik pre vs post, progres, dan lencana personal.
- Forum diskusi per modul, pelaporan konten oleh Learner, dan moderasi oleh Admin (notifikasi di panel web).
- Pengelolaan konten oleh Admin: modul, unit, bank soal, kuis unit; daftar Learner, rekap skor dan progres, dashboard, ekspor CSV.
- Pengelolaan akun oleh Superadmin: akun Admin (tambah, edit, lihat, hapus, ubah role) dan akun Learner (edit, lihat, hapus, ubah role).
- Migration dan seeder data awal, dua modul sampel, uji pengguna, dokumentasi, dan serah terima.

### 2.2 Di Luar Lingkup

Aplikasi kasir atau pencatatan transaksi, perekrutan staf pencatat, pendampingan tatap muka, aplikasi iOS, versi web untuk Learner, notifikasi pengingat dan push (notifikasi laporan forum hanya di panel web Admin), akses offline penuh, dasbor analitik lanjutan, glosarium istilah, peringkat antar pengguna dan gamification kompetitif, serta modul di luar dua materi sampel.

## 3. Pengguna dan Peran

| Peran | Platform | Cara Mendapat Akun | Hak Akses Utama |
|---|---|---|---|
| Learner | Mobile (Android) | Mendaftar sendiri secara terbuka; role otomatis | Belajar, kuis unit, pre-test, post-test, forum (topik, balasan, laporan), riwayat dan skor milik sendiri, profil sendiri |
| Admin (Pengajar) | Web | Dibuat Superadmin dengan password sementara | Kelola modul, unit, bank soal, kuis unit; lihat daftar Learner dan rekap; ekspor CSV; balas forum; tangani laporan dan hapus konten forum |
| Superadmin | Web (login sama, menu terpisah) | Disiapkan tim pengembang | Kelola akun Admin dan Learner saja; tidak menjalankan fungsi Admin dan tidak melihat skor |

### 3.1 Persona

**Learner (staf, pengelola, atau pemilik UMKM).** Merangkap banyak tugas, belajar di sela pekerjaan lewat ponsel, kurang percaya diri pada sistem digital. Butuh alur minim langkah, bahasa awam, video pendek yang bisa dijeda, tempat bertanya, dan gambaran kemajuan tanpa dibandingkan dengan orang lain.

**Admin (Pengajar).** Menyiapkan materi dan soal, memantau hasil belajar, dan menjaga forum tetap sehat. Butuh pengelolaan konten yang tidak rumit, rekap yang dapat diekspor, dan notifikasi laporan.

**Superadmin.** Memastikan hanya orang yang tepat memegang akses Pengajar dan mengelola akun pengguna, terpisah dari urusan konten dan skor.

## 4. Aturan Bisnis dan Keputusan Tim

| Kode | Aturan | Sumber |
|---|---|---|
| AB-01 | Learner mendaftar sendiri secara terbuka. Admin hanya dibuat oleh Superadmin. Akun Superadmin disiapkan tim. Role selalu ditetapkan di backend. | BR-L01, S01, Y01 |
| AB-02 | Semua pengguna wajib login untuk mengakses fungsi selain registrasi, login, dan reset password. | BR-L01, Y02 |
| AB-03 | Urutan belajar: pre-test, unit berurutan, lalu post-test yang terbuka setelah semua unit selesai. | BR-L11 |
| AB-04 | Kuis unit terdiri atas 3–5 soal. Kuis bersifat latihan: tidak dihitung dalam skor pre atau post, tidak mengunci unit berikutnya, dan dapat diulang setelah kembali ke materi (rollback). | BR-L13, A05 |
| AB-05 | Pre-test adalah pengukur awal, bukan penilaian, tanpa batas waktu per soal. Post-test dapat diulang dan setiap pengerjaan tercatat. | BR-L11 |
| AB-06 | Forum per modul terbuka setelah pre-test modul tersebut selesai dikerjakan. | BR-L14 |
| AB-07 | Learner dapat membuat topik, membalas, dan melaporkan konten, tetapi tidak dapat menghapus konten apa pun. Admin dapat membalas dan menjadi satu-satunya yang dapat menghapus. Konten yang dilaporkan tetap tampil sampai Admin bertindak. | BR-L14, A09, A10 |
| AB-08 | Modul berstatus draft tidak terlihat oleh Learner. | BR-A02 |
| AB-09 | Skor hanya dapat dilihat pemiliknya dan Admin. Superadmin tidak melihat skor maupun rekap. | BR-Y04 |
| AB-10 | Tidak ada peringkat antar pengguna. Lencana bersifat personal dan diberikan setelah semua unit dan post-test modul selesai. | BR-L16 |
| AB-11 | Data belajar baru dicatat setelah Learner menyetujui consent. Learner dapat meminta penghapusan akun dan datanya. | BR-L02, L04 |
| AB-12 | Modul, bank soal, dan kuis unit milik bersama: semua Admin dapat melihat dan mengubahnya. Menghapus akun Admin tidak menghapus konten. | BR-A02 – A05 |
| AB-13 | Superadmin hanya mengelola akun Admin dan Learner dan tidak menjalankan fungsi Admin; Admin hanya melihat daftar Learner. | BR-S05, A06 |
| AB-14 | Admin yang login dengan password sementara wajib membuat password baru sebelum memakai panel. | BR-A01, Y05 |
| AB-15 | Satu email hanya untuk satu akun. | BR-L01 |
| AB-16 | Field wajib divalidasi; pesan error berbahasa Indonesia, ramah, dan tampil di dekat field yang bermasalah. | BR-L01 |

**Keputusan tim yang berlaku di seluruh dokumen:**

1. Nama aplikasi Sinau; Pasta Nafisa hanya satu dari banyak UMKM mitra (contoh kasus).
2. Platform mobile hanya Android; teknologi pengembangan belum ditentukan dan diputuskan saat perancangan arsitektur.
3. Video memakai tautan yang diinput Admin dan diputar langsung dalam aplikasi. Sumber dapat berupa URL YouTube yang valid (ditampilkan dengan YouTube embed) atau URL file video langsung yang kompatibel; video tidak membuka aplikasi atau tab eksternal.
4. Kuis atau studi kasus 3–5 soal pilihan ganda di setiap unit sebagai latihan dengan rollback ke materi.
5. Forum per modul; Learner melapor, hanya Admin yang menghapus; notifikasi laporan hanya di panel web.
6. Superadmin hanya mengelola akun; modul dan bank soal milik bersama; Superadmin tidak melihat skor.
7. Lencana personal wajib (Must); glosarium dihapus dari lingkup.
8. Jumlah pengguna sasaran tidak dicantumkan dalam dokumen.

## 5. Kebutuhan Fungsional

Prioritas: **Must** = wajib, **Should** = penting, **Could** = opsional bila kapasitas tim memungkinkan. Kolom Aturan merujuk ke kode AB pada bagian 4 dan BR pada BRD. ID FR mengikuti dokumen kebutuhan fungsional; ID baru ditambahkan untuk kuis unit, forum, moderasi, dan Superadmin.

### 5.1 Akun dan Akses Learner (Mobile)

| ID | Kebutuhan | Prioritas | Aturan / Ref. |
|---|---|---|---|
| FR-L01 | Pengguna dapat mendaftar sendiri dengan nama (boleh nama panggilan), email, password, konfirmasi password, dan nama usaha (opsional), dengan ikon lihat atau sembunyikan password dan petunjuk di bawah kolom (misal "Minimal 8 karakter"); sistem otomatis menetapkan role Learner tanpa pilihan role | Must | AB-01, AB-15, AB-16; BR-L01 |
| FR-L02 | Sistem menampilkan persetujuan data sekali setelah registrasi: penjelasan bahasa awam (data yang dicatat, termasuk tampilnya nama dan isi diskusi di forum), penegasan skor hanya bisa dilihat sendiri, checkbox "Saya setuju" dengan tombol Lanjut nonaktif sampai dicentang, dan tautan kebijakan privasi; data belajar baru dicatat setelah setuju | Must | AB-11; BR-L02 |
| FR-L03 | Pengguna dapat login (email, password, opsi "Ingat saya") dan logout; kegagalan login menampilkan pesan umum yang ramah tanpa menyebut mana yang salah | Must | AB-02; BR-L01 |
| FR-L04 | Pengguna dapat mereset password lewat email: memasukkan email, menerima tautan reset, lalu membuat password baru | Must | BR-L03 |
| FR-L05 | Pengguna dapat melihat dan mengubah profil (nama, nama usaha, password) | Must | BR-L03 |
| FR-L06 | Pengguna dapat meminta penghapusan akun dan datanya melalui tombol dengan konfirmasi | Must | AB-11; BR-L04 |
| FR-L07 | Sistem menampilkan onboarding 2–3 layar sekali saat pertama dipakai (Belajar lewat video pendek, Coba tes, Lihat perkembanganmu) dengan tombol Lewati dan Lanjut | Should | BR-L05 |

### 5.2 Materi Pembelajaran

| ID | Kebutuhan | Prioritas | Aturan / Ref. |
|---|---|---|---|
| FR-L08 | Pengguna dapat melihat daftar modul: gambar sampul, judul, deskripsi satu kalimat, jumlah unit, estimasi total durasi, dan bar progres; hanya modul berstatus terbit yang tampil | Must | AB-08; BR-L06 |
| FR-L09 | Pengguna dapat melihat detail modul: tujuan belajar, daftar unit berurutan dengan status (belum, sedang, selesai), serta status pre-test dan post-test | Must | AB-03; BR-L06 |
| FR-L10 | Pengguna dapat memutar video unit, menjeda, menggeser durasi, dan melanjutkan dari posisi terakhir; judul dan durasi unit tampil sebelum menonton | Must | BR-L07 |
| FR-L11 | Pengguna dapat membaca materi singkat per unit, di bawah video atau pada tab terpisah; format teks dasar dan gambar yang diizinkan ditampilkan dengan aman | Must | BR-L08 |
| FR-L12 | Pengguna dapat membuka ulang unit materi kapan saja tanpa batas | Must | BR-L08 |
| FR-L13 | Sistem menandai unit selesai (otomatis saat video tuntas atau lewat tombol Tandai selesai), memperbarui persentase progres modul, dan menyediakan tombol Unit berikutnya | Must | BR-L09 |
| FR-L14 | Pengguna dapat langsung melanjutkan unit terakhir dari beranda lewat kartu Lanjutkan belajar (unit terakhir, judul, sisa durasi) | Must | BR-L09 |
| FR-L15 | Pengguna dapat memilih kualitas video untuk menghemat kuota, termasuk kualitas default di profil; ketersediaan pilihan bergantung pada sumber tautan video | Could | BR-L10 |
| FR-L16 | ~~(glosarium istilah)~~ dihapus dari lingkup | — | — |

### 5.3 Evaluasi: Pre-test, Post-test, dan Kuis Unit

| ID | Kebutuhan | Prioritas | Aturan / Ref. |
|---|---|---|---|
| FR-L17 | Pengguna dapat mengerjakan pre-test sebelum memulai unit, dengan layar pembuka berisi judul tes, jumlah soal, estimasi waktu, dan keterangan "Ini hanya untuk mengetahui pemahaman awalmu, bukan penilaian" | Must | AB-03, AB-05; BR-L11 |
| FR-L18 | Pengguna dapat mengerjakan post-test setelah seluruh unit modul selesai; sebelum itu post-test terkunci | Must | AB-03; BR-L11 |
| FR-L19 | Soal ditampilkan satu per layar dari bank soal (pilihan ganda) dengan indikator "Soal 3 dari 10", tombol Sebelumnya, Berikutnya, dan Selesai di soal terakhir, konfirmasi bila masih ada soal kosong, dan tanpa batas waktu per soal | Must | AB-05; BR-L11 |
| FR-L20 | Sistem menghitung dan menampilkan skor otomatis setelah tes selesai | Must | BR-L12 |
| FR-L21 | Sistem menampilkan kalimat apresiasi netral, daftar jawaban benar dan salah, serta pembahasan singkat tiap soal, dengan tombol Lihat riwayat dan Kembali ke modul | Must | BR-L12 |
| FR-L22 | Progres pengerjaan tes tersimpan otomatis sehingga dapat dilanjutkan jika terhenti | Must | BR-L11, Y03 |
| FR-L23 | Pengguna dapat mengulang post-test; setiap pengerjaan tercatat | Must | AB-05; BR-L11 |
| FR-L28 | Setiap unit memiliki kuis atau studi kasus berisi 3–5 soal pilihan ganda yang dikerjakan setelah materi unit | Must | AB-04; BR-L13 |
| FR-L29 | Setelah kuis unit selesai, sistem menampilkan hasil dan pembahasan singkat; pengguna dapat kembali (rollback) ke materi unit dan mengulang kuis kapan saja | Must | AB-04; BR-L13 |
| FR-L30 | Hasil kuis unit tersimpan sebagai bagian progres unit, tidak dihitung dalam skor pre-test maupun post-test, dan tidak mengunci unit berikutnya (lihat catatan data 7.4 nomor 5) | Must | AB-04; BR-L13 |

### 5.4 Riwayat dan Progres

| ID | Kebutuhan | Prioritas | Aturan / Ref. |
|---|---|---|---|
| FR-L24 | Pengguna dapat melihat riwayat skor pre-test dan post-test per modul beserta tanggal, hanya miliknya sendiri | Must | AB-09; BR-L15 |
| FR-L25 | Sistem menampilkan perbandingan skor pre vs post dalam grafik batang sederhana | Must | BR-L15 |
| FR-L26 | Pengguna dapat melihat ringkasan progres semua modul | Must | BR-L06, L15 |
| FR-L27 | Pengguna mendapat lencana personal saat menyelesaikan modul (semua unit dan post-test), tanpa peringkat antar pengguna | Must | AB-10; BR-L16 |

### 5.5 Forum Diskusi dan Moderasi

| ID | Kebutuhan | Prioritas | Aturan / Ref. |
|---|---|---|---|
| FR-L31 | Forum diskusi modul terbuka bagi Learner setelah pre-test modul tersebut selesai dan dapat diakses kapan saja, termasuk saat membaca unit | Must | AB-06; BR-L14 |
| FR-L32 | Learner dapat membuat topik atau pertanyaan di forum, dengan opsi mengaitkannya ke unit tertentu | Must | BR-L14 |
| FR-L33 | Learner dapat membalas topik atau pertanyaan Learner lain | Must | BR-L14 |
| FR-L34 | Forum menampilkan nama pengguna pada topik dan balasan serta penanda yang membedakan balasan Pengajar | Must | BR-L14 |
| FR-L35 | Learner dapat melaporkan topik atau balasan yang tidak pantas lewat tombol "Laporkan Konten" (dengan alasan); laporan diteruskan ke Admin dan konten tetap tampil sampai Admin bertindak; Learner tidak dapat menghapus konten, termasuk miliknya sendiri | Must | AB-07; BR-L14 |
| FR-A13 | Admin dapat melihat topik forum dan membalasnya | Must | AB-07; BR-A09 |
| FR-A14 | Admin dapat menghapus topik atau balasan di forum; hanya Admin yang memiliki kemampuan ini | Must | AB-07; BR-A10 |
| FR-A15 | Sistem menampilkan notifikasi laporan konten di panel web Admin beserta rincian posting dan alasan, sehingga Admin dapat menghapus konten yang melanggar atau menandai laporan Resolved bila wajar | Must | AB-07; BR-A10 |

### 5.6 Panel Web Admin (Pengajar)

| ID | Kebutuhan | Prioritas | Aturan / Ref. |
|---|---|---|---|
| FR-A01 | Admin dapat login ke panel web dengan akun dari Superadmin (tidak bisa didaftarkan dari aplikasi), lupa password lewat email, dan logout | Must | AB-01, AB-02; BR-A01 |
| FR-A02 | Admin dapat menambah, mengubah, dan menghapus modul (judul, deskripsi, gambar sampul, tujuan belajar), dengan konfirmasi saat menghapus | Must | AB-12; BR-A02 |
| FR-A03 | Admin dapat menambah, mengubah, dan menghapus unit materi (judul, tujuan belajar satu kalimat, tautan video, durasi, materi singkat) dan melihat pratinjau seperti tampilan Learner | Must | AB-12; BR-A03 |
| FR-A04 | Admin dapat mengatur urutan unit (drag atau tombol naik dan turun) dan status modul draft atau terbit | Must | AB-08; BR-A02, A03 |
| FR-A05 | Admin dapat mengelola bank soal pre-test dan post-test per modul (teks soal, 3–4 pilihan, kunci, pembahasan, jenis pre atau post), menambah beberapa soal sekaligus atau mengimpor CSV, menentukan jumlah soal yang diambil per tes, dan mengaktifkan acak urutan soal | Must | AB-12; BR-A04 |
| FR-A06 | Admin dapat melihat daftar Learner (nama, email, nama usaha, tanggal daftar) dengan pencarian dan filter; Admin hanya melihat | Must | AB-13; BR-A06 |
| FR-A08 | Admin dapat melihat rekap skor dan progres seluruh Learner dalam tabel (pengguna, modul, skor pre, skor post, selisih, unit selesai) dengan filter modul atau periode | Must | AB-09; BR-A07 |
| FR-A09 | Admin dapat mengekspor rekap skor dan progres ke CSV untuk analisis hasil uji | Must | BR-A07 |
| FR-A10 | Admin melihat dashboard ringkas: jumlah pengguna, jumlah modul, jumlah tes selesai, rata-rata skor pre vs post per modul, dan pintasan ke Kelola Modul dan Kelola Soal | Should | BR-A08 |
| FR-A11 | Admin dapat menambah, mengubah, dan menghapus soal kuis atau studi kasus per unit (teks soal atau kasus, pilihan, kunci, pembahasan); sistem menolak penerbitan unit bila jumlah soal kurang dari 3 atau lebih dari 5 | Must | AB-04; BR-A05 |
| FR-A12 | Admin yang login dengan password sementara diwajibkan membuat password baru sebelum dapat memakai panel | Must | AB-14; BR-A01 |
| FR-A16 | Admin dapat menyusun materi singkat dengan editor teks kaya untuk paragraf, judul, bold, miring, daftar, dan gambar; mengimpor DOCX atau PDF berbasis teks menjadi konten yang dapat diedit; serta meninjau dan mengubah hasil sebelum disimpan. DOC lama dan PDF hasil scan belum didukung. | Must | AB-12; BR-A03 |
| FR-A07 | ~~(nonaktifkan atau hapus akun pengguna)~~ dipindahkan menjadi wewenang Superadmin pada FR-SA05 | — | — |

### 5.7 Panel Web Superadmin

| ID | Kebutuhan | Prioritas | Aturan / Ref. |
|---|---|---|---|
| FR-SA01 | Superadmin login melalui halaman login web yang sama dengan Admin; akun Superadmin disiapkan tim dan tidak dapat didaftarkan | Must | AB-01; BR-S01 |
| FR-SA02 | Superadmin dapat menambah akun Admin (nama, email); sistem membuat password sementara dan menetapkan role Admin | Must | AB-01, AB-14; BR-S02 |
| FR-SA03 | Superadmin dapat melihat daftar dan detail, mengedit, serta menghapus akun Admin | Must | AB-12; BR-S03 |
| FR-SA04 | Superadmin dapat mengubah role akun Admin | Must | BR-S03 |
| FR-SA05 | Superadmin dapat melihat daftar dan detail, mengedit, serta menghapus akun Learner | Must | AB-13; BR-S04 |
| FR-SA06 | Superadmin dapat mengubah role akun Learner | Must | BR-S04 |
| FR-SA07 | Menu Superadmin tidak dirender untuk Admin dan menu Admin tidak dirender untuk Superadmin; middleware backend menolak setiap permintaan ke rute di luar hak role. Superadmin tidak memiliki fungsi kelola konten, rekap hasil, dan forum Admin | Must | AB-13; BR-S05 |

### 5.8 Sistem

| ID | Kebutuhan | Prioritas | Aturan / Ref. |
|---|---|---|---|
| FR-S01 | Sistem menetapkan role di backend dan menolak input role dari klien | Must | AB-01; BR-Y01 |
| FR-S02 | Sistem membatasi hak akses berdasarkan role; Learner tidak dapat mengakses panel web | Must | AB-02; BR-Y02 |
| FR-S03 | Sistem menyimpan posisi video, progres unit, hasil kuis unit, dan skor per pengguna | Must | BR-Y03 |
| FR-S04 | Sistem menjaga skor tiap pengguna hanya dapat dilihat pemiliknya dan Admin | Must | AB-09; BR-Y04 |
| FR-S05 | Sistem menegakkan pemeriksaan role melalui middleware pada setiap rute dan API panel web | Must | AB-13; BR-Y02 |
| FR-S06 | Sistem menandai akun dengan password sementara dan mewajibkan penggantian pada login pertama | Must | AB-14; BR-Y05 |
| FR-S07 | Video unit diputar langsung di pemutar dalam aplikasi dari tautan YouTube valid atau URL file video langsung yang diinput Admin, tanpa mengarahkan pengguna ke aplikasi atau tab eksternal | Must | BR-L07 |

## 6. User Story

| ID | Sebagai | Saya ingin | Agar |
|---|---|---|---|
| US-01 | Staf UMKM | mendaftar sendiri, login, dan dikenalkan alur aplikasi | bisa belajar tanpa bantuan pengembang |
| US-02 | Pengguna baru | memahami data apa yang dicatat sebelum belajar | yakin dan datanya terlindungi |
| US-03 | Learner | melihat progres dan melanjutkan unit terakhir dari beranda | bisa belajar di sela pekerjaan tanpa mencari lagi |
| US-04 | Learner | menonton video pendek yang bisa dijeda dan dilanjutkan di dalam aplikasi | materi mudah dipelajari dan tidak terlupakan |
| US-05 | Learner | membaca materi singkat dan membuka ulang unit kapan saja | bisa mengulang saat lupa |
| US-06 | Learner | mengerjakan pre-test tanpa tekanan | tahu titik awal pemahaman saya |
| US-07 | Learner | mengerjakan kuis atau studi kasus 3–5 soal di tiap unit | tahu apakah saya sudah paham |
| US-08 | Learner | kembali ke materi unit bila hasil kuis kurang baik | bisa memperbaiki pemahaman sebelum lanjut |
| US-09 | Learner | mengerjakan post-test setelah semua unit selesai dan boleh mengulang | kemajuan saya terukur |
| US-10 | Learner | melihat riwayat skor, grafik pre vs post, dan progres milik saya | tahu perkembangan tanpa dibandingkan orang lain |
| US-11 | Learner | mendapat lencana personal saat menyelesaikan modul | termotivasi tanpa peringkat |
| US-12 | Learner | bertanya dan menjawab di forum modul | kebingungan saya terjawab oleh sesama Learner atau Pengajar |
| US-13 | Learner | melaporkan konten forum yang tidak pantas | forum tetap nyaman |
| US-14 | Learner | mengelola profil, mereset password, dan menghapus akun serta data saya | data saya terkendali |
| US-15 | Admin | menyusun modul, unit, bank soal, dan kuis unit | materi sesuai kebutuhan UMKM |
| US-16 | Admin | menerbitkan atau mengembalikan modul ke draft | modul yang belum siap tidak terlihat Learner |
| US-17 | Admin | melihat daftar Learner, rekap skor dan progres, dashboard, dan ekspor CSV | bisa menganalisis efektivitas belajar |
| US-18 | Admin | membalas forum dan menangani laporan konten | diskusi tetap sehat |
| US-19 | Superadmin | menambah dan mengelola akun Admin dengan password sementara | hanya Pengajar sah yang memiliki akses |
| US-20 | Superadmin | mengelola akun Learner | data akun tetap tertib |
| US-21 | Pemilik produk | wewenang Admin dan Superadmin dipisahkan di backend | tidak ada yang mengakses fungsi di luar perannya |

## 7. Kebutuhan Data dan Validasi

### 7.1 Struktur Data (ringkas, rinci ada di [ERD](./ERD.md))

| Tabel | Kolom Utama | Catatan |
|---|---|---|
| `users` | id, name, email (unique), password, role, is_temp_password, consent_given, created_at, deleted_at | Seluruh akun: Learner, Admin, Superadmin |
| `modules` | id, title, description, status (draft, published), created_by (FK), created_at | Modul pembelajaran |
| `units` | id, module_id (FK), title, video_url, reading_content, sequence_order, duration | `reading_content` menyimpan materi singkat sebagai HTML terbatas dan tersanitasi; unit berurutan per modul |
| `unit_quizzes` | id, unit_id (FK), question_text, options (JSON), correct_key, explanation | 3–5 soal per unit |
| `test_questions` | id, module_id (FK), question_text, options (JSON), correct_key, explanation | Bank soal pre dan post |
| `test_attempts` | id, user_id (FK), module_id (FK), type (pre, post), score, completed_at | Riwayat dan skor tes |
| `learner_unit_progress` | id, user_id (FK), unit_id (FK), last_video_pos, is_completed, updated_at | Progres dan posisi video |
| `user_badges` | id, user_id (FK), module_id (FK), earned_at | Lencana personal |
| `forum_posts` | id, module_id (FK), unit_id (FK, null), user_id (FK), parent_id (FK, null), content, created_at | Topik dan balasan |
| `forum_reports` | id, post_id (FK), reported_by (FK), reason, status (pending, resolved), created_at | Laporan konten |

### 7.2 Aturan Validasi Field (AB-16)

**Registrasi dan Profil Learner**

| Field | Aturan |
|---|---|
| name | Wajib, string, maksimal 100 karakter (boleh nama panggilan) |
| email | Wajib, format email valid, unik |
| password | Wajib saat registrasi, minimal 8 karakter; opsional saat ubah profil |
| konfirmasi password | Wajib saat registrasi atau ganti password, sama dengan password |
| business_name | Opsional, maksimal 150 karakter |
| consent | Wajib dicentang sebelum lanjut |

**Login dan Reset Password**

| Field | Aturan |
|---|---|
| email | Wajib, format email valid |
| password | Wajib |
| password baru | Wajib, minimal 8 karakter, sama dengan konfirmasi |

**Modul dan Unit (Admin)**

| Field | Aturan |
|---|---|
| modul.title | Wajib, maksimal 150 karakter |
| modul.description | Opsional, satu kalimat, maksimal 255 karakter |
| modul.cover_image | Opsional, gambar jpg atau png, maksimal 2 MB |
| modul.status | Wajib, draft atau terbit; terbit hanya bila modul memiliki unit dan bank soal pre serta post yang cukup |
| pre dan post jumlah soal | Bilangan bulat minimal 1 dan tidak melebihi jumlah soal pada bank |
| unit.title | Wajib, maksimal 150 karakter |
| unit.learning_objective | Wajib, satu kalimat, maksimal 255 karakter |
| unit.video_url | Wajib, URL YouTube yang valid (tautan youtube.com/watch, youtu.be, shorts, atau embed) atau URL file video langsung yang kompatibel dengan pemutar. YouTube diputar melalui embed; video harus mengizinkan embedding. URL YouTube yang tidak didukung atau tidak valid ditolak. |
| unit.duration | Opsional di form Admin. Jika diisi, Admin memasukkan durasi dalam menit (angka positif, maksimal 2 angka desimal); server menyimpan nilainya dalam detik. Jika kosong, nilai database `duration` adalah `null`. |
| unit.reading_content (materi singkat) | Opsional; konten editor disimpan sebagai HTML dengan elemen paragraf, judul, bold, miring, daftar, dan gambar yang diizinkan. Sanitasi server menghapus script, atribut event, dan elemen/atribut lain yang tidak diizinkan. |
| unit.sequence_order | Wajib, bilangan bulat, unik dalam satu modul |
| unit.material_image | JPG/JPEG atau PNG; maksimal 5 MB per gambar dan 10 gambar per materi. File disimpan secara privat dengan nama acak dan disajikan melalui endpoint terkontrol; Learner hanya dapat mengambil gambar dari modul terbit. |
| unit.material_import | Opsional, DOCX atau PDF, maksimal 10 MB per berkas dan 50 halaman PDF. Berkas diproses di browser dan tidak diunggah atau disimpan sebagai materi/arsip. Hasil impor harus dapat ditinjau dan diedit sebelum disimpan. |
| unit.material_import DOCX | Ekstrak teks dan pertahankan paragraf, judul, bold, miring, daftar, serta gambar JPG/JPEG/PNG. Maksimal 10 gambar mengikuti batas materi. Jika terdapat gambar melebihi batas, tampilkan peringatan yang jelas dan jangan menambahkan gambar berlebih. |
| unit.material_import PDF | Ekstrak teks yang dapat dipilih dari PDF digital. PDF hasil scan atau PDF tanpa teks yang dapat diekstrak belum didukung dan harus ditolak dengan pesan bahasa Indonesia; OCR tidak dilakukan. Gambar PDF tidak diekstrak. |
| unit.material_import gagal | Tampilkan pesan ramah berbahasa Indonesia dan biarkan Admin mengisi editor secara manual; konten yang sudah ada tidak boleh terhapus atau terganti akibat kegagalan impor. Berkas DOC lama dan PDF hasil scan harus diberi tahu bahwa formatnya belum didukung. |

**Soal Tes dan Kuis Unit (Admin)**

| Field | Aturan |
|---|---|
| question_text | Wajib |
| pilihan jawaban | Wajib 3 sampai 4 pilihan, semua terisi |
| correct_key | Wajib, salah satu pilihan yang ada |
| explanation | Wajib, pembahasan singkat |
| jenis (pre atau post) | Wajib untuk soal bank tes |
| jumlah kuis per unit | Minimal 3 dan maksimal 5 saat unit diterbitkan |

**Forum dan Laporan**

| Field | Aturan |
|---|---|
| content | Wajib, maksimal 1000 karakter (usulan) |
| reason (laporan) | Wajib; satu Learner hanya sekali melaporkan satu posting |

**Akun oleh Superadmin**

| Field | Aturan |
|---|---|
| name, email | Wajib; email unik |
| role | Wajib, learner atau admin |
| password sementara | Dibuat sistem, bukan diinput manual |

**Tes**

| Field | Aturan |
|---|---|
| pre-test | Hanya sebelum unit dikerjakan pada modul tersebut |
| post-test | Hanya bila semua unit modul selesai |
| jawaban | Satu pilihan per soal; soal kosong boleh dikirim setelah konfirmasi |

Pesan error ditampilkan dalam bahasa Indonesia yang ramah di dekat field yang bermasalah.

### 7.3 Relasi dan Kardinalitas

| Induk | Anak | Kardinalitas | Keterangan |
|---|---|---|---|
| users | modules | 1 : 0..N | Admin membuat modul (created_by) |
| modules | units | 1 : 1..N | Modul terdiri atas satu atau lebih unit berurutan |
| units | unit_quizzes | 1 : 3..5 | Tiap unit wajib memiliki 3 sampai 5 soal kuis |
| modules | test_questions | 1 : 0..N * | Bank soal pre dan post per modul |
| users, modules | test_attempts | 1 : 0..N * | Pengerjaan pre-test dan post-test |
| users, units | learner_unit_progress | 1 : 0..N * | Progres per Learner per unit |
| users, modules | user_badges | 1 : 0..N * | Lencana per Learner per modul |
| modules | forum_posts | 1 : 0..N | Forum dibuka per modul setelah pre-test selesai |
| units, users | forum_posts | 1 : 0..N * | Unit opsional; user sebagai penulis |
| forum_posts | forum_posts | 1 : 0..N * | Balasan melalui parent_id |
| forum_posts | forum_reports | 1 : 0..N | Satu posting dapat dilaporkan beberapa Learner |
| users | forum_reports | 1 : 0..N * | Pelapor (reported_by) |

Tanda `*` menandai relasi yang disimpulkan dari kolom FK; kardinalitas lainnya tertulis pada dokumen ERD. Skor pada `test_attempts` hanya dapat diakses Learner pemilik dan Admin; Superadmin ditolak middleware.

### 7.4 Catatan Kesesuaian ERD dengan Kebutuhan

Saat mencocokkan ERD dengan BRD dan FR ditemukan beberapa kesenjangan yang perlu diputuskan sebelum basis data dikunci.

| No | Temuan | Dampak | Rekomendasi |
|---|---|---|---|
| 1 | `test_questions` tidak memiliki kolom jenis soal | Soal pre dan post tidak dapat dibedakan (FR-A05) | Tambah kolom `type` (pre, post) |
| 2 | Jawaban per soal tidak tersimpan | Hasil tes (benar dan salah, pembahasan) dan melanjutkan tes (FR-L21, L22) tidak dapat dipenuhi | Tambah tabel `test_attempt_answers` (attempt_id, question_id, selected_key, is_correct); `completed_at` kosong berarti tes masih berjalan |
| 3 | `users` tidak memiliki nama usaha | FR-L01 dan FR-L05 meminta nama usaha opsional | Tambah kolom `business_name` (nullable) |
| 4 | `modules` tanpa gambar sampul, tujuan belajar, jumlah soal diambil, dan opsi acak; `units` tanpa tujuan belajar | FR-A02, A03, A05 memintanya | Tambah `cover_image`, `learning_objectives`, `pre_question_count`, `post_question_count`, `shuffle_questions` pada `modules` dan `learning_objective` pada `units` |
| 5 | Hasil kuis unit tidak disimpan | FR-L30 menyebut hasil tersimpan sebagai progres, ERD hanya menyimpan `is_completed` | Putuskan: (a) hasil tidak disimpan dan FR-L30 disesuaikan, atau (b) tambah tabel `unit_quiz_attempts` |
| 6 | `users` memakai `deleted_at` (soft delete) | FR-L06 meminta akun dan datanya dihapus; data pribadi tersisa (UU PDP) | Tentukan hapus permanen atau anonimisasi data Learner dan konten forumnya |
| 7 | `consent_given` hanya boolean | Tidak ada bukti waktu persetujuan | Ganti atau tambah `consent_at` (timestamp) |
| 8 | `forum_reports` tanpa pencatatan penangani | Tidak ada jejak siapa dan kapan laporan diselesaikan; hapus posting dapat menghapus laporan | Tambah `handled_by` dan `handled_at`; tentukan perlakuan laporan saat posting dihapus; hapus topik ikut menghapus balasannya |
| 9 | Pemicu pembukaan forum berbeda antar dokumen | ERD menulis "memulai pre-test", flowchart dan BRD menulis setelah selesai | Seragamkan: forum terbuka setelah pre-test selesai |
| 10 | ENUM role terpotong ("Learner, Admin...") | Role Superadmin harus tercantum | Tetapkan `ENUM(learner, admin, superadmin)` |
| 11 | Batas 3–5 soal kuis tidak ada di basis data | Aturan dapat dilanggar bila hanya divalidasi di UI | Validasi di sisi server saat unit diterbitkan |

## 8. Daftar Halaman (Sitemap)

### 8.1 Aplikasi Mobile (Android)

| Halaman | Learner | Elemen Utama |
|---|---|---|
| Registrasi | Ya | Form daftar, ikon lihat password, petunjuk di bawah kolom, tautan masuk |
| Persetujuan data | Ya | Penjelasan awam, checkbox setuju, tombol Lanjut, tautan kebijakan privasi |
| Login dan lupa password | Ya | Email, password, Ingat saya, tautan lupa password, halaman password baru |
| Onboarding | Ya | 2–3 layar, tombol Lewati dan Lanjut |
| Beranda | Ya | Sapaan, kartu Lanjutkan belajar, daftar modul dengan progres, navigasi bawah (Beranda, Modul, Riwayat, Profil) |
| Daftar dan detail modul | Ya | Kartu modul, tujuan belajar, unit berstatus, status pre dan post-test, akses forum |
| Belajar unit | Ya | Pemutar video, judul dan durasi, materi singkat dengan format dasar dan gambar, tombol selesai dan unit berikutnya, kuis unit, tombol kembali ke materi |
| Pre-test dan post-test | Ya | Layar pembuka, satu soal per layar, indikator, tombol navigasi, konfirmasi soal kosong |
| Hasil tes dan kuis unit | Ya | Skor, apresiasi netral, benar dan salah, pembahasan, Lihat riwayat, Kembali ke modul |
| Forum modul | Ya | Daftar topik, buat topik, balasan, penanda balasan Pengajar, Laporkan Konten |
| Riwayat dan perkembangan | Ya | Pilih modul, skor dan tanggal, grafik pre vs post, ringkasan progres, lencana |
| Profil dan pengaturan | Ya | Ubah nama, nama usaha, password, kualitas video default, kebijakan privasi, hapus akun, keluar |

### 8.2 Panel Web

| Halaman | Admin | Superadmin | Elemen Utama |
|---|---|---|---|
| Login panel, lupa password | Ya | Ya | Email, password, lupa password lewat email |
| Ganti password sementara | Ya | Tidak | Form password baru, wajib pada login pertama |
| Dashboard ringkas | Ya | Tidak | Kartu angka, rata-rata pre vs post per modul, pintasan |
| Kelola modul | Ya | Tidak | Tabel modul, form, terbit atau draft, hapus |
| Kelola unit dan kuis unit | Ya | Tidak | Daftar unit dengan urutan, form unit, pratinjau, kuis 3–5 soal |
| Kelola bank soal | Ya | Tidak | Per modul dibagi pre dan post, form soal, jumlah soal diambil, acak |
| Forum dan laporan | Ya | Tidak | Daftar topik, balas, notifikasi laporan, hapus atau tandai selesai |
| Daftar Learner | Ya (lihat) | Tidak | Tabel, cari, filter |
| Rekap hasil | Ya | Tidak | Tabel skor dan progres, filter modul atau periode, Ekspor CSV |
| Kelola akun Admin | Tidak | Ya | Tabel, tambah (password sementara), edit, hapus, ubah role |
| Kelola akun Learner | Tidak | Ya | Tabel, cari, edit, hapus, ubah role |

## 9. Alur Utama dan Kasus Pengecualian

Diagram alur ada pada dokumen [ERD dan Flowchart](./ERD.md): Gambar 2 (login dan hak akses), Gambar 3 (alur belajar Learner), Gambar 4 (moderasi forum), dan Gambar 5 (pengelolaan konten dan akun).

1. **Login:** pengguna mengisi email dan password; sistem memverifikasi, memeriksa password sementara (wajib ganti), lalu mengarahkan sesuai role (Learner ke aplikasi, Admin dan Superadmin ke panel web); middleware memeriksa setiap akses.
2. **Belajar:** Learner memilih modul, mengerjakan pre-test, forum terbuka, mempelajari unit (video dan bacaan), mengerjakan kuis unit (rollback ke materi bila belum paham), mengulang untuk unit berikutnya, lalu post-test terbuka; sistem menghitung skor, menampilkan pembahasan, memperbarui grafik, dan menerbitkan lencana.
3. **Moderasi forum:** Learner melaporkan konten tidak pantas; sistem mencatat dan memberi notifikasi di panel Admin; Admin meninjau, lalu menghapus konten bila melanggar atau menandai Resolved bila wajar.
4. **Pengelolaan konten:** Admin menyusun modul, unit, bank soal, dan kuis unit, mengatur status terbit, lalu memantau rekap dan mengekspor CSV.
5. **Pengelolaan akun:** Superadmin menambah akun Admin (password sementara), mengelola akun Admin dan Learner, dan mengubah role.

### 9.1 Kasus Pengecualian (Edge Cases)

Kasus berikut harus ditangani sistem dan diuji saat uji pengguna. Kasus bertanda "Asumsi" atau "Perlu keputusan" belum diatur dalam dokumen sumber.

| ID | Kasus | Penanganan Sistem | Terkait |
|---|---|---|---|
| EC-01 | Login dengan email atau password salah | Ditolak dengan pesan umum yang ramah tanpa menyebut mana yang salah | FR-L03, AC-05 |
| EC-02 | Registrasi atau tambah akun dengan email yang sudah ada | Ditolak dengan pesan di dekat field email | FR-L01, AC-03 |
| EC-03 | Learner membuka unit sebelum pre-test | Diarahkan ke pre-test terlebih dahulu | FR-L17, AC-19 |
| EC-04 | Learner membuka post-test sebelum semua unit selesai | Terkunci dengan petunjuk unit yang tersisa | FR-L18, AC-27 |
| EC-05 | Aplikasi tertutup di tengah tes | Progres tersimpan dan tes dilanjutkan | FR-L22, AC-21 |
| EC-06 | Menekan Selesai saat ada soal kosong | Muncul konfirmasi sebelum dikirim | FR-L19, AC-22 |
| EC-07 | Hasil kuis unit kurang memuaskan | Ditawarkan kembali ke materi (rollback); unit berikutnya tidak terkunci | FR-L29, AC-26 |
| EC-08 | Admin menerbitkan unit dengan kuis kurang dari 3 atau lebih dari 5 soal | Ditolak dengan pesan jumlah soal harus 3 sampai 5 | FR-A11, AC-24 |
| EC-09 | Learner membuka forum sebelum pre-test selesai | Terkunci dengan petunjuk mengerjakan pre-test | FR-L31, AC-32 |
| EC-10 | Learner mencoba menghapus konten forum | Tidak tersedia; permintaan hapus ditolak | FR-L35, AC-35 |
| EC-11 | Learner melaporkan posting yang sama dua kali | Laporan kedua ditolak; pelapor diberi tahu laporannya sudah tercatat | Asumsi |
| EC-12 | Admin menghapus topik yang memiliki balasan dan laporan | Balasan ikut terhapus; perlakuan laporan terkait | Perlu keputusan (7.4 no. 8) |
| EC-13 | Admin membuka rute Superadmin langsung | Akses ditolak | FR-SA07, AC-09 |
| EC-14 | Superadmin membuka rute Admin (tambah modul, rekap skor) | Akses ditolak | FR-SA07, AC-10 |
| EC-15 | Learner membuka URL panel web | Akses ditolak | FR-S02, AC-07 |
| EC-16 | Admin login dengan password sementara | Dipaksa membuat password baru | FR-A12, AC-08 |
| EC-17 | Superadmin menghapus akun Admin yang pernah membuat modul | Akun terhapus; modul tetap ada | AB-12, AC-13 |
| EC-18 | Superadmin mengubah role Learner menjadi Admin | Akses panel web terbuka sesuai role; aplikasi mobile dipakai untuk Learner | Asumsi |
| EC-19 | Learner mencoba melihat skor Learner lain | Ditolak | FR-S04, AC-31 |
| EC-20 | Tautan video tidak dapat diputar | Pesan ramah dengan opsi coba lagi; bacaan dan kuis unit tetap dapat dibuka | Asumsi |
| EC-21 | Admin mengembalikan modul ke draft saat Learner sudah belajar | Modul hilang dari daftar Learner; progres tetap tersimpan | Asumsi, AC-41 |
| EC-22 | Admin menghapus modul yang sudah memiliki riwayat tes | Tampil peringatan dan konfirmasi; perlakuan riwayat | Perlu keputusan |
| EC-23 | Learner meminta hapus akun | Konfirmasi lalu penghapusan sesuai kebijakan data | FR-L06, AC-43 |

## 10. Kebutuhan Non-Fungsional

| ID | Kategori | Kebutuhan |
|---|---|---|
| NFR-01 | Keamanan | Password disimpan terenkripsi (hash), bukan teks asli |
| NFR-02 | Keamanan | Seluruh komunikasi aplikasi dan panel web dengan server dienkripsi (HTTPS) |
| NFR-03 | Keamanan | Otorisasi berbasis role di sisi server (middleware) pada setiap rute dan API; menyembunyikan menu di antarmuka bukan satu-satunya pembatas |
| NFR-04 | Keamanan | Role tidak pernah diterima dari klien; Learner tidak dapat mengakses data Learner lain (skor, riwayat, progres) lewat manipulasi URL atau API |
| NFR-05 | Keamanan | Password sementara wajib diganti pada login pertama; tautan reset password berlaku terbatas waktu dan sekali pakai |
| NFR-06 | Privasi dan hukum | Consent sebelum data belajar dicatat, hanya data yang dibutuhkan yang disimpan, dan penghapusan akun beserta data tersedia (UU No. 27 Tahun 2022) |
| NFR-07 | Hak cipta | Materi video dan bacaan berasal dari karya sendiri atau sumber berizin; data mitra dipakai atas izin pemilik (UU No. 28 Tahun 2014) |
| NFR-08 | Integritas data | Foreign key dan constraint unik di basis data, antara lain untuk email, urutan unit per modul, satu progres per pengguna per unit, dan satu lencana per pengguna per modul |
| NFR-09 | Integritas data | Skor dihitung di server, dan penyimpanan hasil tes berjalan dalam satu transaksi |
| NFR-10 | Kegunaan | Mobile-first dengan alur minim langkah, bahasa awam, dan pesan error ramah; kemudahan penggunaan diukur dengan SUS (acuan 68) |
| NFR-11 | Kegunaan | Panel web nyaman dipakai di komputer dan tetap terbaca di layar kecil |
| NFR-12 | Kinerja | Beranda dan daftar modul tampil kurang dari 3 detik pada jaringan seluler normal dengan data demo (usulan) |
| NFR-13 | Hemat kuota | Video pendek (acuan 2–6 menit) dan berukuran ringan, diputar secara streaming tanpa unduh penuh |
| NFR-14 | Keandalan | Posisi video, progres unit, dan progres tes tersimpan otomatis; kehilangan koneksi tidak menghapus progres yang sudah tersimpan |
| NFR-15 | Kompatibilitas | Aplikasi berjalan pada berbagai ponsel Android (versi minimum ditetapkan saat perancangan); panel web berjalan di peramban modern (Chrome, Edge, Firefox) |
| NFR-16 | Pemeliharaan | Kode terstruktur dan terdokumentasi, disertai panduan penggunaan untuk serah terima karena mitra tidak memiliki staf teknologi informasi |
| NFR-17 | Biaya | Komponen tanpa biaya lisensi dan hosting ringan berbiaya rendah |
| NFR-18 | Keamanan | Materi kaya HTML disanitasi di server dengan daftar elemen/atribut yang diizinkan; skrip, event handler, dan URL aktif berbahaya tidak boleh disimpan atau dijalankan |

## 11. Data Seed Awal

| Data | Isi |
|---|---|
| Superadmin | 1 akun disiapkan tim |
| Admin | Akun Pengajar contoh dengan password sementara |
| Learner | Beberapa akun demo dengan consent sudah disetujui |
| Modul | Supply Chain Management dan Product Identity, status terbit |
| Unit | Beberapa unit pendek per modul, masing-masing dengan tautan video, materi singkat, tujuan belajar, dan 3–5 soal kuis (jumlah unit ditetapkan saat produksi materi) |
| Bank soal | Soal pre-test dan post-test per modul, masing-masing dengan kunci dan pembahasan |
| Riwayat contoh (opsional) | Contoh pengerjaan pre-test dan post-test serta progres unit untuk demo grafik dan rekap |
| Forum contoh (opsional) | Beberapa topik dan balasan, serta satu laporan berstatus pending untuk demo moderasi |

## 12. Kriteria Penerimaan (Acceptance Criteria)

| ID | Skenario | Hasil yang Diharapkan | FR |
|---|---|---|---|
| AC-01 | Buka halaman aplikasi atau panel web mana pun tanpa login | Dialihkan ke halaman login | FR-L03, S02 |
| AC-02 | Registrasi dengan data valid | Akun Learner terbentuk dengan role Learner otomatis; tampil persetujuan data | FR-L01, S01 |
| AC-03 | Registrasi atau tambah akun dengan email yang sudah terdaftar | Ditolak dengan pesan ramah di dekat field email | FR-L01 |
| AC-04 | Persetujuan data: tombol Lanjut sebelum checkbox dicentang | Tombol nonaktif; data belajar belum dicatat sampai pengguna setuju | FR-L02 |
| AC-05 | Login dengan email atau password salah | Ditolak dengan pesan umum yang ramah tanpa menyebut mana yang salah | FR-L03 |
| AC-06 | Lupa password dengan email terdaftar | Tautan reset terkirim; password baru berhasil dipakai untuk login | FR-L04 |
| AC-07 | Learner mencoba login atau membuka rute panel web | Akses ditolak | FR-S02 |
| AC-08 | Admin login dengan password sementara | Wajib membuat password baru sebelum panel dapat dipakai | FR-A01, A12, S06 |
| AC-09 | Admin membuka rute Superadmin lewat URL langsung | Akses ditolak; menu Superadmin tidak tampil | FR-SA07, S05 |
| AC-10 | Superadmin membuka rute Admin (tambah modul, bank soal, rekap skor) | Akses ditolak; menu Admin tidak tampil | FR-SA07, S05 |
| AC-11 | Superadmin menambah akun Admin (nama, email) | Akun terbentuk dengan role Admin dan password sementara | FR-SA02 |
| AC-12 | Superadmin mengubah role akun Learner atau Admin | Hak akses akun mengikuti role baru | FR-SA04, SA06 |
| AC-13 | Superadmin menghapus akun Admin yang pernah membuat modul | Akun terhapus; modul dan soal buatannya tetap ada | FR-SA03 |
| AC-14 | Learner membuka daftar modul | Hanya modul berstatus terbit yang tampil | FR-L08, A04 |
| AC-15 | Putar video, jeda, tutup aplikasi, lalu buka unit lagi | Video dilanjutkan dari posisi terakhir | FR-L10, S03 |
| AC-16 | Putar video unit dari URL YouTube atau file langsung | Video yang dapat diakses dan mengizinkan embed diputar di dalam aplikasi tanpa membuka aplikasi atau tab lain | FR-S07 |
| AC-17 | Selesaikan sebuah unit | Persentase progres modul bertambah; beranda menampilkan kartu Lanjutkan belajar pada unit terakhir | FR-L13, L14 |
| AC-18 | Buka kembali unit yang sudah selesai | Unit terbuka tanpa batas jumlah | FR-L11, L12 |
| AC-19 | Learner membuka unit sebelum mengerjakan pre-test | Diarahkan ke pre-test lebih dulu | FR-L17 |
| AC-20 | Buka pre-test | Tampil keterangan bukan penilaian, satu soal per layar dengan indikator, tanpa batas waktu per soal | FR-L17, L19 |
| AC-21 | Tes terhenti lalu dibuka kembali | Pengerjaan dilanjutkan dari kondisi terakhir | FR-L22, S03 |
| AC-22 | Tekan Selesai saat masih ada soal kosong | Muncul konfirmasi sebelum jawaban dikirim | FR-L19 |
| AC-23 | Admin mengatur bank soal pre dan post, jumlah soal diambil, dan opsi acak | Tes Learner mengambil jumlah soal sesuai pengaturan; urutan acak bila diaktifkan | FR-A05 |
| AC-24 | Admin menerbitkan unit dengan 4 soal kuis; lalu dengan 2 atau 6 soal | 4 soal berhasil; 2 atau 6 soal ditolak | FR-A11, L28 |
| AC-25 | Learner menyelesaikan kuis unit | Hasil dan pembahasan tampil; skor tidak masuk pre atau post; unit berikutnya tetap terbuka | FR-L28 – L30 |
| AC-26 | Hasil kuis kurang memuaskan, Learner memilih kembali ke materi | Materi unit terbuka dan kuis dapat diulang | FR-L29 |
| AC-27 | Buka post-test sebelum semua unit selesai; lalu setelah semua unit selesai | Terkunci dengan petunjuk; terbuka setelah semua unit selesai | FR-L18 |
| AC-28 | Selesaikan post-test | Skor benar otomatis, pembahasan, grafik pre vs post diperbarui, lencana modul terbit | FR-L20, L21, L25, L27 |
| AC-29 | Ulangi post-test | Pengerjaan baru tercatat dan riwayat memuat semua pengerjaan | FR-L23, L24 |
| AC-30 | Learner membuka riwayat dan perkembangan | Hanya data milik sendiri yang tampil | FR-L24 – L26, S04 |
| AC-31 | Learner mencoba melihat skor Learner lain lewat URL atau API | Ditolak | FR-S04 |
| AC-32 | Buka forum modul sebelum pre-test selesai; lalu setelah selesai | Terkunci dengan petunjuk; terbuka dan dapat diakses kapan saja setelah pre-test selesai | FR-L31 |
| AC-33 | Learner membuat topik dan membalas; Admin membalas | Tampil dengan nama pengguna; balasan Pengajar diberi penanda | FR-L32 – L34, A13 |
| AC-34 | Learner melaporkan sebuah topik atau balasan | Laporan tercatat; notifikasi muncul di panel Admin; konten tetap tampil | FR-L35, A15 |
| AC-35 | Learner mencari cara menghapus konten forum, termasuk miliknya | Tidak tersedia; permintaan hapus ditolak | FR-L35 |
| AC-36 | Admin menghapus posting yang dilaporkan | Konten hilang dari forum dan laporan ditutup | FR-A14, A15 |
| AC-37 | Admin menilai laporan wajar | Laporan ditandai Resolved; konten tetap tampil | FR-A15 |
| AC-38 | Admin membuka daftar Learner dan mencari nama atau email | Hasil sesuai; tidak ada aksi edit atau hapus | FR-A06 |
| AC-39 | Admin membuka rekap, memfilter modul atau periode, lalu ekspor CSV | File CSV berisi data sama dengan tabel yang ditampilkan | FR-A08, A09 |
| AC-40 | Admin membuka dashboard | Jumlah pengguna, modul, tes selesai, dan rata-rata pre vs post per modul sesuai data | FR-A10 |
| AC-41 | Admin mengembalikan modul ke draft | Modul hilang dari daftar Learner; progres Learner tetap tersimpan | FR-A04 |
| AC-42 | Kirim form dengan field wajib kosong | Pesan validasi dalam bahasa Indonesia tampil di dekat field terkait | FR-A02, A03, L01 |
| AC-43 | Learner meminta hapus akun dan data | Muncul konfirmasi; akun dan data dihapus sesuai kebijakan data | FR-L06 |
| AC-44 | Pengguna baru membuka aplikasi pertama kali | Onboarding 2–3 layar tampil sekali dan dapat dilewati | FR-L07 |
| AC-45 | Admin menulis materi singkat dengan paragraf, bold, miring, daftar, dan gambar; Learner/pratinjau membuka unit | Format dan gambar tampil sesuai konten yang disimpan, sementara elemen aktif atau atribut berbahaya tidak dijalankan | FR-A16, FR-L11, NFR-18 |
| AC-46 | Admin mengimpor DOCX atau PDF digital, lalu meninjau dan mengubah hasil | Teks dapat diedit; format dasar dan gambar DOCX didukung sesuai batas; berkas sumber diproses di browser dan tidak disimpan | FR-A16, NFR-17 |
| AC-47 | Admin memilih DOC lama, PDF scan/tanpa teks, berkas di atas batas ukuran/halaman, atau gambar di atas batas ukuran/jumlah | Format yang belum didukung atau berkas tidak valid ditolak dengan pesan Indonesia; editor tetap dapat digunakan manual dan materi lama tetap utuh | FR-A16, AB-16 |
| AC-48 | Admin menambah beberapa soal pada satu form atau mengimpor CSV ke bank Pre/Post | Soal CSV tampil sebagai pratinjau yang dapat diedit; jenis mengikuti tab aktif; seluruh batch divalidasi dan disimpan bersama atau tidak ada yang disimpan bila ada kesalahan | FR-A05, AB-12, AC-42 |

## 13. Asumsi dan Ketergantungan

### 13.1 Asumsi

- Learner memiliki ponsel Android dan koneksi internet yang cukup untuk memutar video.
- Mitra dan staf bersedia terlibat dalam peninjauan tiap iterasi dan uji pengguna.
- Video, bacaan, dan soal diproduksi tim; data mitra dipakai atas izin pemilik.
- Tautan video berupa URL YouTube yang mendukung embed atau URL file video langsung yang dapat diputar di pemutar; video YouTube privat atau yang melarang embedding tidak dijamin dapat diputar.
- Teknologi pengembangan dan hosting ditentukan pada tahap perancangan arsitektur.
- Akun Superadmin awal disiapkan tim pengembang.

### 13.2 Ketergantungan Antar Fitur dan Urutan Pengerjaan

Urutan berikut menjadi acuan agar pengerjaan oleh tiga orang tidak saling menunggu. Fitur pada baris bawah bergantung pada fitur yang disebut di kolom ketiga.

| Urutan | Komponen | Bergantung pada | Alasan |
|---|---|---|---|
| 1 | Migration dan seeder | Tidak ada | Semua fitur membutuhkan tabel dan data awal, termasuk akun Superadmin |
| 2 | Autentikasi, otorisasi role, dan middleware | 1 | Semua halaman wajib login dan dibatasi per role |
| 3 | Panel Superadmin: kelola akun Admin dan Learner | 2 | Akun Admin harus ada sebelum konten dibuat |
| 3 | Panel Admin: kelola modul dan unit | 2 | Materi harus ada sebelum dapat dipelajari |
| 4 | Bank soal pre dan post, kuis unit | 3 (modul dan unit) | Soal terikat pada modul dan unit |
| 4 | Aplikasi Learner: registrasi, consent, beranda, modul, pemutar video, progres | 2, 3 | Membutuhkan akun dan modul terbit |
| 5 | Pre-test, kuis unit, post-test, hasil tes | 4 | Membutuhkan bank soal dan kuis |
| 6 | Riwayat, grafik, lencana, rekap dan ekspor CSV | 5 | Membutuhkan data hasil tes |
| 6 | Forum dan moderasi | 5 (pre-test selesai) | Forum terbuka setelah pre-test selesai |
| 7 | Uji pengguna (SUS, TAM), dokumentasi, serah terima | Semua fitur selesai | Pengujian memakai fitur yang sudah jadi |

**Ketergantungan teknis dan eksternal:**

- Layanan pengiriman email untuk reset password.
- Sumber video yang dapat diputar langsung dan stabil (ditetapkan saat perancangan).
- Layanan hosting berbiaya rendah untuk backend, basis data, dan panel web.
- Pustaka pemutar video dan grafik untuk aplikasi Android.
- Pustaka open-source yang berjalan di browser untuk impor DOCX dan ekstraksi teks PDF digital; impor dibatasi 10 MB per berkas dan 50 halaman PDF. DOC lama dan PDF scan/OCR berada di luar lingkup saat ini.
- Dokumen BRD dan ERD sebagai rujukan: nama tabel dan kolom pada PRD harus sama dengan ERD setelah catatan 7.4 diputuskan.

### 13.3 Risiko

| No | Risiko | Mitigasi |
|---|---|---|
| 1 | Cakupan bertambah (panel multi-role, kuis unit, forum, moderasi) sementara tim kecil dan waktu terbatas | Kerjakan Must lebih dulu; fitur Should dan Could digeser bila perlu |
| 2 | Tautan video tidak dapat diputar langsung atau kualitas tidak dapat dipilih | Validasi URL YouTube/direct video dan gunakan embed untuk YouTube; kualitas tunggal sebagai cadangan. Video privat atau yang melarang embedding perlu diganti dengan sumber lain. |
| 3 | Literasi digital rendah dan staf takut salah | User-Centered Design, uji tiap iterasi, bahasa dan alur sederhana |
| 4 | Registrasi terbuka tanpa verifikasi memasukkan akun tidak relevan ke rekap | Superadmin dapat menghapus akun; filter rekap per periode |
| 5 | Konten forum tidak pantas atau keliru | Pelaporan oleh Learner, moderasi dan balasan Pengajar |
| 6 | Kesenjangan model data tidak diputuskan sebelum implementasi | Selesaikan catatan 7.4 pada tahap perancangan |
| 7 | Keberlanjutan hosting dan pemeliharaan setelah capstone | Dokumentasi dan panduan serah terima |

## 14. Rencana Pengerjaan

Rencana mengikuti tahap Implementasi Prototype pada proposal (tiga iterasi Agile) dan menyesuaikan fitur yang ditambahkan setelahnya.

| Tahap | Keluaran | Waktu |
|---|---|---|
| Iterasi 1 | Migration, seeder, autentikasi dan middleware, panel Admin untuk modul dan unit, beranda, daftar dan detail modul, pemutar video, bacaan, progres unit | 8 – 21 Nov 2026 |
| Iterasi 2 | Bank soal, pre-test, post-test, kuis unit dengan rollback, hasil dan pembahasan | 22 – 28 Nov 2026 |
| Iterasi 3 | Riwayat dan grafik, lencana, registrasi dan consent, reset password, forum dan moderasi, panel Superadmin, rekap dan ekspor CSV | 29 Nov – 5 Des 2026 |
| Produksi materi | Video, bacaan, bank soal, dan kuis unit untuk dua modul sampel | 8 – 28 Nov 2026 |
| Uji pengguna | Staf memakai sistem di sela jam kerja; kumpulkan SUS, TAM, skor, dan umpan balik | 6 – 12 Des 2026 |
| Analisis dan penyempurnaan | Analisis hasil uji dan perbaikan akhir | 13 – 19 Des 2026 |
| Penggunaan sistem | Deploy, penyiapan akun, dokumentasi dan panduan, sosialisasi, serah terima | 20 Des 2026 – 2 Jan 2027 |

> Catatan: proposal menempatkan autentikasi dan consent pada iterasi 3, padahal login dibutuhkan sejak iterasi 1, sehingga login dasar dimajukan. Cakupan iterasi 3 padat untuk tujuh hari; forum dan moderasi menjadi kandidat pertama yang digeser bila waktu tidak cukup. Pembagian ini usulan dan perlu disepakati tim.

## 15. Deliverable Akhir

- Aplikasi mobile Android Sinau dan panel web (Admin dan Superadmin) yang berjalan di lingkungan operasional.
- Source code beserta migration dan seeder.
- Dua modul sampel (Supply Chain Management dan Product Identity) lengkap dengan video, bacaan, kuis unit, pre-test, dan post-test.
- Dokumen: BRD, PRD, ERD dan Flowchart.
- Hasil uji pengguna: skor SUS, hasil kuesioner TAM, dan rekap skor pre dan post.
- Dokumentasi proyek dan panduan penggunaan untuk Admin, Superadmin, dan Learner.
- Sosialisasi kepada pemilik dan staf serta serah terima sistem.

## 16. Matriks Keterlacakan

Kebutuhan bisnis pada BRD ditelusuri ke kebutuhan fungsional, user story, dan kriteria penerimaan.

| BR (BRD) | FR | User Story | Kriteria Penerimaan |
|---|---|---|---|
| BR-L01 | FR-L01, L03, S01 | US-01 | AC-02, AC-03, AC-05, AC-01, AC-07 |
| BR-L02 | FR-L02 | US-02 | AC-04 |
| BR-L03 | FR-L04, L05 | US-14 | AC-06 |
| BR-L04 | FR-L06 | US-14 | AC-43 |
| BR-L05 | FR-L07 | US-01 | AC-44 |
| BR-L06 | FR-L08, L09, L26 | US-03 | AC-14 |
| BR-L07 | FR-L10, S07 | US-04 | AC-15, AC-16 |
| BR-L08 | FR-L11, L12 | US-05 | AC-18 |
| BR-L09 | FR-L13, L14 | US-03 | AC-17 |
| BR-L10 | FR-L15 | US-04 | - |
| BR-L11 | FR-L17 – L19, L22, L23 | US-06, US-09 | AC-19, AC-20, AC-21, AC-22, AC-27, AC-29 |
| BR-L12 | FR-L20, L21 | US-06, US-09 | AC-28 |
| BR-L13 | FR-L28 – L30 | US-07, US-08 | AC-24, AC-25, AC-26 |
| BR-L14 | FR-L31 – L35 | US-12, US-13 | AC-32, AC-33, AC-34, AC-35 |
| BR-L15 | FR-L24 – L26 | US-10 | AC-30, AC-29 |
| BR-L16 | FR-L27 | US-11 | AC-28 |
| BR-A01 | FR-A01, A12 | US-19 | AC-08 |
| BR-A02 | FR-A02, A04 | US-15, US-16 | AC-14, AC-41 |
| BR-A03 | FR-A03, A04, A16 | US-15 | AC-42, AC-45, AC-46, AC-47 |
| BR-A04 | FR-A05 | US-15 | AC-23 |
| BR-A05 | FR-A11 | US-15 | AC-24 |
| BR-A06 | FR-A06 | US-17 | AC-38 |
| BR-A07 | FR-A08, A09 | US-17 | AC-39 |
| BR-A08 | FR-A10 | US-17 | AC-40 |
| BR-A09 | FR-A13 | US-18 | AC-33 |
| BR-A10 | FR-A14, A15 | US-18 | AC-34, AC-36, AC-37 |
| BR-S01 | FR-SA01 | US-19 | AC-10 |
| BR-S02 | FR-SA02 | US-19 | AC-11 |
| BR-S03 | FR-SA03, SA04 | US-19 | AC-12, AC-13 |
| BR-S04 | FR-SA05, SA06 | US-20 | AC-12 |
| BR-S05 | FR-SA07, S05 | US-21 | AC-09, AC-10 |
| BR-Y01 | FR-S01 | US-21 | AC-02 |
| BR-Y02 | FR-S02, S05 | US-21 | AC-07, AC-09, AC-10 |
| BR-Y03 | FR-S03 | US-03 | AC-15, AC-21 |
| BR-Y04 | FR-S04 | US-10 | AC-31, AC-30 |
| BR-Y05 | FR-S06 | US-19 | AC-08 |

> BR-L10 (pilihan kualitas video) berprioritas Could sehingga belum memiliki skenario penerimaan wajib.

## 17. Pertanyaan Terbuka

| No | Pertanyaan | Asumsi Sementara |
|---|---|---|
| 1 | Makna rollback pada kuis unit | Kembali ke materi unit lalu mengulang kuis; tidak mengunci unit berikutnya |
| 2 | Syarat unit dianggap selesai | Materi dipelajari dan kuis unit dikerjakan, tanpa nilai minimum |
| 3 | Bentuk soal kuis unit dan studi kasus | Pilihan ganda; studi kasus berupa soal berbasis skenario |
| 4 | Sumber tautan video dan dukungan pilihan kualitas | URL YouTube dengan embed atau URL file video langsung; video harus dapat diakses dan mengizinkan pemutaran/embed |
| 5 | Teknologi dan hosting | Ditentukan saat perancangan arsitektur |
| 6 | Bentuk notifikasi laporan forum | Hanya di panel web Admin, tanpa push atau email |
| 7 | Perlakuan data dan konten forum saat akun dihapus | Perlu keputusan (7.4 nomor 6 dan 8) |
| 8 | Penyimpanan hasil kuis unit | Perlu keputusan (7.4 nomor 5) |
| 9 | Perlakuan riwayat tes saat modul dihapus | Perlu keputusan (EC-22) |
| 10 | Angka target usulan (waktu muat, waktu mulai belajar, batas karakter forum) | Disepakati bersama dosen pembimbing dan mitra |

## 18. Persetujuan

| Peran | Nama | Tanda Tangan | Tanggal |
|---|---|---|---|
| Pemilik UMKM mitra (Pasta Nafisa) | Ibu Nur | | |
| Dosen pendamping | Murahartawaty | | |
| Tim pengembang | Artadina Septiani | | |
| Tim pengembang | Kheista Anaya | | |
| Tim pengembang | Jasmine Adinda Wibowo | | |
