# Business Requirements Document (BRD) — SINAU

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
| Nama dokumen | Business Requirements Document (BRD) Sinau |
| Versi / status | 1.2 / Draft untuk ditinjau |
| Tanggal | 31 Oktober 2026 |
| Penyusun | Tim Power Puff (Artadina Septiani, Kheista Anaya, Jasmine Adinda Wibowo) |
| Mitra | Ibu Nur, pemilik Pasta Nafisa (salah satu mitra UMKM) |
| Dosen pendamping | Murahartawaty |
| Dokumen sumber | Proposal Capstone Design & Project Tim Power Puff; Daftar Kebutuhan Fungsional (FR) dan List Fitur; Notulen Rapat Capstone dengan Mitra UMKM |

### Riwayat Revisi

| Versi | Tanggal | Perubahan |
|---|---|---|
| 1.0 | 30 Oktober 2026 | Penyusunan awal. Menambahkan role Superadmin, kuis atau studi kasus per unit, dan penegasan platform Android. |
| 1.1 | 31 Oktober 2026 | Menambahkan forum diskusi; membatasi Superadmin hanya pada pengelolaan akun Admin dan Learner (tidak memiliki fungsi Admin); menghapus glosarium istilah; lencana personal menjadi Must. |
| 1.2 | 31 Oktober 2026 | Menambahkan moderasi forum: Learner dapat melaporkan konten, laporan masuk ke notifikasi Admin, dan hanya Admin yang dapat menghapus konten forum. |

---

## 1. Ringkasan Eksekutif

Pelatihan bagi pelaku UMKM umumnya hanya berlangsung satu atau dua kali, bersifat satu arah, dan tidak disertai pengulangan, sehingga pengetahuan mudah terlupakan dan tidak sampai ke seluruh staf. Sinau adalah platform pembelajaran mandiri (Learning Management System/LMS) custom berbasis mobile yang dirancang agar pelaku UMKM dan stafnya dapat belajar kapan saja, di mana saja, dan dapat mengulang materi tanpa batas.

Sinau terdiri atas dua bagian:

1. Aplikasi mobile Android untuk Learner, berisi modul pembelajaran berbasis video pendek dan bacaan ringkas, kuis atau studi kasus per unit, pre-test dan post-test, forum diskusi, serta riwayat skor.
2. Panel web, tempat Admin (Pengajar) mengelola konten, memantau rekap hasil belajar, dan membalas forum, sedangkan Superadmin khusus mengelola akun Admin dan Learner.

Pasta Nafisa menjadi contoh kasus mitra, sedangkan Sinau dirancang sebagai platform umum yang dapat dipakai UMKM lain. Dua materi sampel yang disiapkan adalah Supply Chain Management (alur bahan baku hingga pencegahan waste) dan Product Identity (keunggulan dan diferensiasi produk untuk pemasaran).

## 2. Latar Belakang dan Masalah Bisnis

### 2.1 Latar Belakang

UMKM adalah pilar perekonomian Indonesia, dengan sekitar 64,2 juta unit usaha, kontribusi 61,07% terhadap PDB, dan penyerapan 97% tenaga kerja (Kemenko Perekonomian, 2021). Besarnya peran tersebut menuntut kapasitas SDM pelaku usaha yang terus berkembang. Pada mitra Pasta Nafisa, hanya dua staf yang pernah mengikuti pelatihan digital, sedangkan staf baru belajar secara informal di sela pekerjaan karena merangkap tugas memasak dan melayani pesanan.

### 2.2 Masalah Bisnis

- Pengetahuan dari pelatihan mudah terlupakan karena tidak ada mekanisme pembelajaran ulang maupun tindak lanjut.
- Pelatihan tidak merata; sering hanya pemilik yang mengikuti, sehingga staf bergantung pada penjelasan ulang pemilik.
- Belum ada sarana belajar mandiri yang sederhana dan dapat diakses lewat ponsel. Sistem digital yang pernah dicoba mitra dinilai rumit (satu pesanan sekitar 15 menit), dan staf cenderung takut salah.
- Waktu belajar hanya tersedia di sela pekerjaan atau pergantian shift.

### 2.3 Akar Masalah dan Solusi Terpilih

Berdasarkan analisis fishbone dan 5 Whys pada proposal, akar masalah utama adalah belum tersedianya platform pembelajaran yang sederhana, mudah diakses lewat ponsel, dan dapat dipelajari berulang bagi pelaku UMKM dan stafnya. Solusi terpilih adalah LMS custom berbasis mobile (Alternatif 4 pada proposal), dengan alasan kesesuaian terhadap akar masalah, kebutuhan mitra dan arahan dosen pembimbing, serta kelayakan bagi UMKM dan tim pengembang.

## 3. Tujuan Bisnis dan Indikator Keberhasilan

### 3.1 Tujuan Bisnis

1. Menyediakan sarana belajar mandiri yang dapat diakses lewat ponsel, diulang kapan saja, dan dipelajari di sela jam kerja.
2. Menyediakan materi sampel yang ringkas dan kontekstual (Supply Chain Management dan Product Identity) dalam bentuk video pendek dan bacaan.
3. Mengukur pemahaman pengguna secara terstruktur melalui kuis per unit, pre-test, dan post-test beserta riwayat skor.
4. Memfasilitasi saling bantu antar Learner dan Pengajar melalui forum diskusi ketika ada kebingungan saat mempelajari unit.
5. Memberikan Pengajar kendali atas konten dan rekap hasil belajar, serta Superadmin kendali atas akun pengguna, melalui panel web dengan pemisahan hak akses yang jelas.
6. Membuktikan bahwa platform mudah digunakan dan meningkatkan pemahaman literasi data dan kesiapan kerja pelaku UMKM.

### 3.2 Indikator Keberhasilan

| No | Indikator | Cara Pengukuran | Target |
|---|---|---|---|
| 1 | Kemudahan penggunaan | System Usability Scale (SUS) pada uji pengguna | Skor rata-rata minimal 68 |
| 2 | Peningkatan pemahaman | Perbandingan skor pre-test dan post-test per modul | Rata-rata skor post-test lebih tinggi daripada pre-test |
| 3 | Penerimaan pengguna | Kuesioner singkat Technology Acceptance Model (TAM): persepsi kebermanfaatan dan kemudahan | Hasil dilaporkan dan dianalisis; menjadi masukan penyempurnaan |
| 4 | Kesesuaian fungsional | Uji coba alur utama pada ponsel staf | Seluruh kebutuhan berprioritas Must berjalan |
| 5 | Keterpakaian di sela jam kerja | Observasi dan umpan balik staf serta pemilik | Unit dapat diselesaikan di sela pekerjaan dan dilanjutkan dari posisi terakhir |
| 6 | Ketersediaan materi sampel | Modul berstatus terbit | Dua modul sampel terbit, masing-masing dengan unit, kuis unit, pre-test, dan post-test |

## 4. Ruang Lingkup

### 4.1 Dalam Lingkup

- Aplikasi mobile Android untuk Learner.
- Panel web untuk Admin (Pengajar) dan Superadmin dalam satu aplikasi web, dengan pemisahan hak akses melalui middleware.
- Modul pembelajaran yang terdiri atas beberapa unit; tiap unit berisi video pendek, bacaan ringkas, dan kuis atau studi kasus 3 sampai 5 soal.
- Pre-test di awal modul dan post-test di akhir modul, skor otomatis, pembahasan, dan riwayat skor.
- Forum diskusi per modul yang terbuka setelah pre-test dan dapat diakses kapan saja; dijawab sesama Learner dan dapat dibalas Pengajar; Learner dapat melaporkan konten, laporan masuk ke notifikasi Admin di panel web, dan hanya Admin yang dapat menghapus konten.
- Grafik perbandingan pre vs post, ringkasan progres, dan lencana personal tanpa peringkat.
- Pengelolaan konten (modul, unit, bank soal, kuis unit), rekap hasil, dan ekspor CSV oleh Admin.
- Pengelolaan akun Admin dan akun Learner oleh Superadmin.
- Dua materi sampel: Supply Chain Management dan Product Identity.
- Pengujian bersama pemilik dan staf UMKM (SUS, TAM, skor pre dan post), dokumentasi, panduan penggunaan, dan serah terima.

### 4.2 Di Luar Lingkup

- Aplikasi kasir atau pencatatan transaksi digital dan perekrutan staf pencatat.
- Pendampingan tatap muka.
- Aplikasi iOS dan versi web untuk Learner.
- Notifikasi pengingat bagi Learner, notifikasi push, akses offline penuh, dan dasbor analitik lanjutan (notifikasi laporan forum hanya tampil di panel web Admin).
- Glosarium istilah.
- Peringkat antar pengguna dan gamification kompetitif.
- Modul di luar dua materi sampel (dapat ditambahkan pada periode berikutnya melalui panel Admin).
- Penentuan teknologi pengembangan: ditetapkan pada tahap perancangan arsitektur.

## 5. Stakeholder dan Role Pengguna

### 5.1 Stakeholder

| Stakeholder | Peran dalam Proyek | Kepentingan |
|---|---|---|
| Pemilik UMKM mitra (Ibu Nur, Pasta Nafisa) | Problem owner dan validator kebutuhan | Staf dapat belajar mandiri; beban menjelaskan ulang berkurang |
| Staf UMKM | Pengguna akhir (Learner) | Belajar singkat, sederhana, dan tidak menakutkan |
| UMKM lain | Calon pengguna platform | Memakai platform umum dan menambah modul sesuai kebutuhan |
| Dosen pembimbing | Pengarah ruang lingkup dan akademik | Penerapan Body of Knowledge Development dan Data |
| Tim pengembang (Tim Power Puff) | Perancang, pembangun, dan penguji | Platform berfungsi, teruji, dan terdokumentasi |
| Lembaga pembina UMKM | Calon pengguna lanjutan | Sarana pelatihan mandiri yang dapat diperluas |

### 5.2 Role Pengguna Sistem

| Role | Platform | Cara Mendapatkan Akun | Tanggung Jawab Utama |
|---|---|---|---|
| Learner | Mobile (Android) | Mendaftar sendiri secara terbuka; role ditetapkan otomatis oleh sistem | Belajar, mengerjakan kuis unit, pre-test, dan post-test, berdiskusi dan melaporkan konten di forum, serta melihat riwayat dan progres milik sendiri |
| Admin (Pengajar) | Web | Dibuat oleh Superadmin dengan password sementara yang wajib diganti saat login pertama | Mengelola modul, unit, bank soal, kuis unit; melihat daftar Learner dan rekap hasil; ekspor CSV; membalas forum, menerima notifikasi laporan, dan menghapus konten forum |
| Superadmin | Web (login yang sama dengan Admin, menu terpisah) | Disiapkan tim pengembang (tidak ada pendaftaran) | Mengelola akun Admin (tambah, edit, lihat, hapus, ubah role) dan akun Learner (edit, lihat, hapus, ubah role); tidak dapat menjalankan fungsi Admin |

### 5.3 Matriks Hak Akses

| Fungsi | Learner | Admin | Superadmin |
|---|---|---|---|
| Daftar mandiri dan login di aplikasi mobile | Ya | Tidak | Tidak |
| Login ke panel web | Tidak | Ya | Ya |
| Belajar unit, mengerjakan kuis unit, pre-test, dan post-test | Ya | Tidak | Tidak |
| Melihat riwayat dan skor milik sendiri | Ya | Tidak | Tidak |
| Membuat topik dan membalas di forum diskusi (Learner setelah pre-test) | Ya | Ya (membalas) | Tidak |
| Melaporkan konten forum | Ya | Tidak | Tidak |
| Menerima notifikasi laporan dan menghapus konten forum | Tidak | Ya | Tidak |
| Kelola modul, unit, bank soal, dan kuis unit | Tidak | Ya (bersama) | Tidak |
| Melihat rekap skor dan progres Learner | Tidak | Ya (seluruh Learner) | Tidak |
| Ekspor rekap (CSV) dan dashboard ringkas | Tidak | Ya | Tidak |
| Melihat daftar Learner | Tidak | Ya | Ya (dalam kelola akun) |
| Kelola akun Admin (tambah, edit, lihat, hapus, ubah role) | Tidak | Tidak | Ya |
| Kelola akun Learner (edit, lihat, hapus, ubah role) | Tidak | Tidak | Ya |
| Menu dan rute Admin | Tidak | Ya | Tidak tampil dan ditolak |
| Menu dan rute Superadmin | Tidak | Tidak tampil dan ditolak | Ya |

> Catatan: modul dan bank soal bersifat milik bersama, sehingga semua Admin dapat melihat dan mengubah seluruh konten. Superadmin tidak memiliki akses ke pengelolaan konten maupun rekap hasil.

## 6. Kebutuhan Bisnis

Kebutuhan bisnis berikut diturunkan dari daftar kebutuhan fungsional (FR) dan list fitur, lalu ditambah kebutuhan baru. Prioritas memakai skala MoSCoW: **Must** (wajib), **Should** (penting), **Could** (opsional, dikerjakan bila kapasitas tim memungkinkan). Kolom Ref. FR merujuk ke ID pada dokumen FR; ID baru ditandai (baru).

### 6.1 Alur Bisnis Utama

**Alur Learner:**

1. Daftar, lalu menyetujui penggunaan data (consent), lalu melihat onboarding singkat.
2. Dari beranda, memilih modul dan mengerjakan pre-test sebagai pengukur awal.
3. Setelah pre-test, forum diskusi modul terbuka dan dapat diakses kapan saja. Bila bingung saat membaca unit, Learner bertanya di forum dan dijawab sesama Learner; Pengajar juga dapat membalas. Konten yang tidak pantas dapat dilaporkan.
4. Mempelajari unit secara berurutan: video pendek dan bacaan ringkas, lalu kuis atau studi kasus unit (3 sampai 5 soal).
5. Bila hasil kuis unit belum memuaskan, pengguna dapat kembali (rollback) ke materi unit tersebut, mempelajarinya lagi, dan mengulang kuis.
6. Setelah semua unit selesai, post-test terbuka; hasil dan pembahasan ditampilkan.
7. Melihat riwayat skor, grafik pre vs post, ringkasan progres, dan lencana personal.

**Alur pengelolaan akun dan konten:**

1. Superadmin membuat akun Admin (Pengajar); sistem membuat password sementara.
2. Pengajar login ke panel web dan wajib mengganti password sementara dengan password baru.
3. Pengajar membuat modul, unit, bank soal pre dan post-test, serta kuis tiap unit, lalu menerbitkan modul yang sudah siap.
4. Pengajar memantau rekap skor dan progres seluruh Learner, membalas pertanyaan di forum, menangani laporan konten (menghapus konten yang dilaporkan), serta mengekspor CSV untuk analisis hasil uji.

### 6.2 Kebutuhan Bisnis Learner (Mobile Android)

| ID | Kebutuhan Bisnis | Prioritas | Ref. FR |
|---|---|---|---|
| BR-L01 | Pelaku UMKM dapat mendaftar sendiri secara terbuka dan login ke aplikasi; role Learner ditetapkan otomatis. | Must | FR-L01, L03, S01 |
| BR-L02 | Pengguna memberikan persetujuan penggunaan data dalam bahasa awam sebelum data belajar dicatat. | Must | FR-L02 |
| BR-L03 | Pengguna dapat memulihkan akses (reset password lewat email) serta melihat dan mengubah profil. | Must | FR-L04, L05 |
| BR-L04 | Pengguna dapat meminta penghapusan akun dan datanya. | Must | FR-L06 |
| BR-L05 | Pengguna dikenalkan pada alur aplikasi melalui onboarding singkat 2 sampai 3 layar. | Should | FR-L07 |
| BR-L06 | Pengguna dapat melihat daftar modul beserta progres dan detail modul (tujuan belajar, unit, status pre-test dan post-test). | Must | FR-L08, L09, L26 |
| BR-L07 | Pengguna dapat memutar video unit langsung di dalam aplikasi (tidak berpindah ke aplikasi atau tab lain), menjeda, dan melanjutkan dari posisi terakhir. | Must | FR-L10, S07 (baru) |
| BR-L08 | Pengguna dapat membaca bacaan ringkas per unit dan membuka ulang unit kapan saja tanpa batas. | Must | FR-L11, L12 |
| BR-L09 | Sistem menandai unit selesai, memperbarui progres modul, dan memungkinkan pengguna melanjutkan unit terakhir dari beranda. | Must | FR-L13, L14 |
| BR-L10 | Pengguna dapat memilih kualitas video untuk menghemat kuota. | Could | FR-L15 |
| BR-L11 | Pengguna mengerjakan pre-test sebelum modul dan post-test setelah semua unit selesai; satu soal per layar, progres tersimpan, tanpa batas waktu per soal, dan post-test dapat diulang. | Must | FR-L17, L18, L19, L22, L23 |
| BR-L12 | Sistem menghitung skor otomatis dan menampilkan pembahasan singkat tiap soal. | Must | FR-L20, L21 |
| BR-L13 | Setiap unit memiliki kuis atau studi kasus 3 sampai 5 soal; setelah dikerjakan, pengguna melihat hasil dan pembahasan, dan dapat kembali ke materi unit lalu mengulang kuis kapan saja. | Must | FR-L28, L29, L30 (baru) |
| BR-L14 | Setelah menyelesaikan pre-test, pengguna dapat mengakses forum diskusi modul kapan saja untuk bertanya ketika bingung saat mempelajari unit dan menjawab pertanyaan sesama Learner; Pengajar juga dapat membalas. Learner dapat melaporkan konten forum yang tidak pantas. | Must | FR-L31, L32, L33, L34, L35 (baru) |
| BR-L15 | Pengguna melihat riwayat skor pre dan post per modul (hanya milik sendiri), grafik perbandingan, dan ringkasan progres semua modul. | Must | FR-L24, L25, L26 |
| BR-L16 | Pengguna mendapat lencana personal saat menyelesaikan modul, tanpa peringkat antar pengguna. | Must | FR-L27 |

### 6.3 Kebutuhan Bisnis Admin / Pengajar (Web)

| ID | Kebutuhan Bisnis | Prioritas | Ref. FR |
|---|---|---|---|
| BR-A01 | Admin login ke panel web dengan akun yang dibuat Superadmin (tidak ada pendaftaran) dan wajib mengganti password sementara pada login pertama. | Must | FR-A01, A12 (baru) |
| BR-A02 | Admin dapat menambah, mengubah, dan menghapus modul, serta mengatur status draft atau terbit agar modul yang belum siap tidak terlihat Learner. | Must | FR-A02, A04 |
| BR-A03 | Admin dapat mengelola unit materi (judul, tujuan belajar, tautan video, durasi, bacaan ringkas), mengatur urutan, dan melihat pratinjau seperti tampilan Learner. | Must | FR-A03, A04 |
| BR-A04 | Admin dapat mengelola bank soal pre-test dan post-test per modul (soal, pilihan, kunci, pembahasan), serta menentukan jumlah soal yang diambil dan pengacakan urutan. | Must | FR-A05 |
| BR-A05 | Admin dapat mengelola kuis atau studi kasus tiap unit sebanyak 3 sampai 5 soal, lengkap dengan pilihan, kunci, dan pembahasan. | Must | FR-A11 (baru) |
| BR-A06 | Admin dapat melihat daftar seluruh Learner dengan pencarian dan filter. | Must | FR-A06 |
| BR-A07 | Admin dapat melihat rekap skor dan progres seluruh Learner (pre, post, selisih, unit selesai), memfilter per modul atau periode, dan mengekspor CSV. | Must | FR-A08, A09 |
| BR-A08 | Admin melihat dashboard ringkas (jumlah pengguna, modul, tes selesai, rata-rata skor pre vs post) beserta pintasan ke menu utama. | Should | FR-A10 |
| BR-A09 | Admin dapat melihat topik dan pertanyaan di forum diskusi serta membalasnya. | Must | FR-A13 (baru) |
| BR-A10 | Admin menerima notifikasi di panel web saat Learner melaporkan konten forum, dapat melihat konten yang dilaporkan, dan menghapusnya. Hanya Admin yang dapat menghapus konten forum. | Must | FR-A14, A15 (baru) |

### 6.4 Kebutuhan Bisnis Superadmin (Web)

Superadmin menggunakan aplikasi web dan halaman login yang sama dengan Admin, tetapi hanya mengelola akun: akun Admin (tambah, edit, lihat, hapus, ubah role) dan akun Learner (edit, lihat, hapus, ubah role). Superadmin tidak dapat menjalankan fungsi Admin seperti menambah modul atau soal. Pemisahan hak akses ditegakkan melalui middleware di sisi backend: menu Superadmin tidak ditampilkan kepada Admin, menu Admin tidak ditampilkan kepada Superadmin, dan setiap permintaan ke rute yang bukan haknya ditolak meskipun alamatnya diakses langsung.

| ID | Kebutuhan Bisnis | Prioritas | Ref. FR |
|---|---|---|---|
| BR-S01 | Superadmin login ke panel web yang sama dengan Admin; akun Superadmin disiapkan tim pengembang dan tidak dapat didaftarkan. | Must | FR-SA01 (baru) |
| BR-S02 | Superadmin dapat menambah akun Admin (nama, email); sistem membuat password sementara yang wajib diganti Admin pada login pertama. | Must | FR-SA02 (baru) |
| BR-S03 | Superadmin dapat melihat, mengedit, menghapus, dan mengubah role akun Admin. | Must | FR-SA03, SA04 (baru) |
| BR-S04 | Superadmin dapat melihat, mengedit, menghapus, dan mengubah role akun Learner. | Must | FR-SA05, SA06 (baru) |
| BR-S05 | Menu dan rute Superadmin hanya tersedia bagi Superadmin, dan Superadmin tidak dapat mengakses fungsi Admin; keduanya ditegakkan melalui middleware. | Must | FR-SA07 (baru), FR-S02 |

### 6.5 Kebutuhan Bisnis Sistem

| ID | Kebutuhan Bisnis | Prioritas | Ref. FR |
|---|---|---|---|
| BR-Y01 | Role selalu ditetapkan di backend; input role dari klien ditolak. | Must | FR-S01 |
| BR-Y02 | Hak akses dibatasi menurut role: Learner tidak dapat mengakses panel web, Admin tidak dapat mengakses fungsi Superadmin, dan Superadmin tidak dapat mengakses fungsi Admin. | Must | FR-S02, S05 (baru) |
| BR-Y03 | Sistem menyimpan posisi video, progres unit, hasil kuis unit, dan skor per pengguna. | Must | FR-S03 |
| BR-Y04 | Skor tiap pengguna hanya dapat dilihat oleh pemiliknya dan oleh Admin. | Must | FR-S04 |
| BR-Y05 | Akun yang dibuat dengan password sementara ditandai dan tidak dapat dipakai untuk fungsi lain sebelum password diganti. | Must | FR-S06 (baru) |

### 6.6 Penambahan Kebutuhan Fungsional terhadap Dokumen FR

| ID | Kebutuhan Fungsional Baru |
|---|---|
| FR-L28 | Setiap unit memiliki kuis atau studi kasus berisi 3 sampai 5 soal pilihan ganda yang dikerjakan setelah materi unit. |
| FR-L29 | Setelah kuis unit selesai, sistem menampilkan hasil dan pembahasan singkat; pengguna dapat kembali (rollback) ke materi unit dan mengulang kuis kapan saja. |
| FR-L30 | Hasil kuis unit tersimpan sebagai bagian progres unit dan tidak dihitung dalam skor pre-test maupun post-test. |
| FR-L31 | Forum diskusi modul terbuka bagi Learner setelah pre-test modul tersebut selesai dikerjakan, dan dapat diakses kapan saja (termasuk saat membaca unit). |
| FR-L32 | Learner dapat membuat topik atau pertanyaan di forum, dengan opsi mengaitkannya ke unit tertentu. |
| FR-L33 | Learner dapat membalas topik atau pertanyaan Learner lain. |
| FR-L34 | Forum menampilkan nama pengguna pada topik dan balasan, serta penanda yang membedakan balasan Pengajar. |
| FR-L35 | Learner dapat melaporkan topik atau balasan di forum yang dianggap tidak pantas; laporan tercatat dan diteruskan ke Admin. Learner tidak dapat menghapus konten, termasuk miliknya sendiri. |
| FR-A11 | Admin dapat menambah, mengubah, dan menghapus soal kuis atau studi kasus per unit (teks soal atau kasus, pilihan jawaban, kunci, pembahasan); sistem menolak penerbitan unit bila jumlah soal kurang dari 3 atau lebih dari 5. |
| FR-A12 | Admin yang login dengan password sementara diwajibkan membuat password baru sebelum dapat menggunakan panel. |
| FR-A13 | Admin dapat melihat topik forum dan membalasnya. |
| FR-A14 | Admin dapat menghapus topik atau balasan di forum; hanya Admin yang memiliki kemampuan ini. |
| FR-A15 | Sistem menampilkan notifikasi laporan konten di panel web Admin, memuat konten yang dilaporkan dan statusnya, sehingga Admin dapat menindaklanjuti. |
| FR-SA01 | Superadmin login melalui halaman login web yang sama dengan Admin; akun Superadmin disiapkan tim dan tidak dapat didaftarkan. |
| FR-SA02 | Superadmin dapat menambah akun Admin (nama, email); sistem membuat password sementara dan menetapkan role Admin. |
| FR-SA03 | Superadmin dapat melihat daftar dan detail, mengedit, serta menghapus akun Admin. |
| FR-SA04 | Superadmin dapat mengubah role akun Admin. |
| FR-SA05 | Superadmin dapat melihat daftar dan detail, mengedit, serta menghapus akun Learner. |
| FR-SA06 | Superadmin dapat mengubah role akun Learner. |
| FR-SA07 | Menu Superadmin tidak dirender untuk Admin dan menu Admin tidak dirender untuk Superadmin; middleware backend menolak setiap permintaan ke rute di luar hak role tersebut. Superadmin tidak memiliki fungsi pengelolaan konten, rekap hasil, dan forum Admin. |
| FR-S05 | Sistem menegakkan pemeriksaan role melalui middleware pada setiap rute dan API panel web. |
| FR-S06 | Sistem menandai akun dengan password sementara dan mewajibkan penggantian pada login pertama. |
| FR-S07 | Video unit diputar langsung di pemutar dalam aplikasi dari tautan video yang diinput Admin, tanpa mengarahkan pengguna ke aplikasi atau tab eksternal. |

### 6.7 Perubahan terhadap Kebutuhan Fungsional yang Ada

| ID | Perubahan |
|---|---|
| FR-A01 | Akun Admin tidak disediakan langsung oleh tim, melainkan dibuat oleh Superadmin (akun Superadmin yang disiapkan tim). |
| FR-A06, A07 | Admin tetap dapat melihat daftar Learner; fungsi menonaktifkan atau menghapus akun Learner tidak lagi dimiliki Admin dan dipindahkan ke Superadmin dalam bentuk edit, lihat, hapus, dan ubah role (FR-SA05, SA06). |
| FR-L02 | Teks persetujuan menyebutkan bahwa nama pengguna dan isi diskusi di forum dapat dilihat Learner lain dan Pengajar, sedangkan skor tetap hanya dapat dilihat sendiri. |
| FR-L16 | Glosarium istilah dihapus dari lingkup. |
| FR-L27 | Lencana personal ditetapkan sebagai kebutuhan Must (wajib). |
| FR-S04 | Skor hanya dapat dilihat pemiliknya dan Admin (Superadmin tidak melihat skor). |
| FR-L03 (Ingat saya, logout) | Berlaku untuk aplikasi mobile Android; panel web memiliki alur login terpisah untuk Admin dan Superadmin. |
| FR-L09, L13 | Detail unit mencakup kuis unit; unit dianggap selesai mengikuti aturan yang dikonfirmasi pada bagian 11. |
| FR-L15 | Pilihan kualitas video bersifat opsional (Could) dan bergantung pada kemampuan sumber tautan video. |

## 7. Kebutuhan Non-Fungsional

| Aspek | Kebutuhan |
|---|---|
| Usability | Antarmuka sederhana, mobile-first, alur minim langkah, dan bahasa awam dengan contoh dari kasus usaha nyata. Pesan error ramah dan tidak menyalahkan; pre-test diposisikan sebagai pengukur awal, bukan penilaian. Kemudahan penggunaan diukur dengan SUS (acuan skor 68). |
| Kinerja dan hemat kuota | Video pendek (acuan 2 sampai 6 menit) dengan ukuran ringan agar tidak membebani kuota. Pemutaran dapat dijeda dan dilanjutkan dari posisi terakhir. |
| Keamanan | Autentikasi akun, penyimpanan password terenkripsi (hash), dan komunikasi terenkripsi. Pembatasan akses berbasis role di backend dengan middleware; role tidak pernah diterima dari klien. Password sementara wajib diganti pada login pertama. |
| Privasi dan kepatuhan hukum | Persetujuan pengguna sebelum data belajar dicatat, hanya data yang dibutuhkan yang disimpan (UU No. 27 Tahun 2022 tentang Pelindungan Data Pribadi). Skor hanya dapat dilihat pemiliknya dan Admin; pengguna dapat meminta penghapusan akun dan datanya. Nama pengguna dan isi diskusi di forum terlihat oleh Learner lain dan Pengajar; hal ini dicantumkan pada persetujuan data. Materi video dan bacaan berasal dari karya sendiri atau sumber berizin (UU No. 28 Tahun 2014 tentang Hak Cipta); data dan contoh kasus mitra dipakai atas izin pemiliknya. |
| Keandalan | Progres unit dan tes tersimpan otomatis sehingga dapat dilanjutkan bila terhenti. Fitur materi, kuis, tes, dan riwayat skor berjalan sesuai kebutuhan (kesesuaian fungsional ISO/IEC 25010). |
| Kompatibilitas | Aplikasi berjalan pada berbagai ponsel Android pengguna. Panel web dapat diakses melalui peramban modern pada komputer. |
| Kemudahan pemeliharaan dan biaya | Komponen tanpa biaya lisensi dan hosting ringan berbiaya rendah. Dokumentasi dan panduan penggunaan disiapkan saat serah terima karena mitra tidak memiliki staf teknologi informasi. |

## 8. Asumsi, Batasan, dan Risiko

### 8.1 Asumsi

- Learner memiliki ponsel Android dan koneksi internet yang cukup untuk memutar video.
- Mitra dan staf bersedia terlibat dalam peninjauan tiap iterasi dan uji pengguna.
- Materi video dan bacaan diproduksi oleh tim; data mitra digunakan atas izin pemilik.
- Teknologi pengembangan dan layanan hosting ditentukan pada tahap perancangan arsitektur.
- Pendaftaran Learner terbuka bagi siapa pun yang memiliki aplikasi.

### 8.2 Batasan

| Batasan | Dampak pada Rancangan |
|---|---|
| Ekonomi | Tanpa anggaran khusus; komponen gratis atau berbiaya rendah; fitur berbayar tidak disertakan. |
| Sosial-budaya | Staf cenderung takut salah dan berliterasi digital terbatas; antarmuka sangat sederhana, tanpa peringkat atau gamification kompetitif. |
| Hukum | Wajib persetujuan data, minimalisasi data, autentikasi, dan hak akses; pelacakan perilaku belajar rinci tidak dilakukan. |
| Kemampuan produksi | Tim 3 orang dengan waktu efektif sekitar 4 bulan; prioritas pada fitur inti dengan iterasi Agile. |
| Kesehatan dan keselamatan | Unit singkat yang dapat dijeda; kuis dan tes dikerjakan saat jeda kerja, bukan bersamaan dengan aktivitas operasional. |

### 8.3 Risiko

| No | Risiko | Dampak | Mitigasi |
|---|---|---|---|
| 1 | Cakupan bertambah (panel web multi-role, Superadmin, kuis unit, forum diskusi) sementara tim kecil dan waktu terbatas | Jadwal mundur atau kualitas turun | Kerjakan kebutuhan Must lebih dulu; fitur Could ditunda; tinjau ulang jadwal pada tahap perancangan |
| 2 | Pemutaran video langsung di aplikasi dan pilihan kualitas bergantung pada sumber tautan | Video tidak dapat diputar inline atau kualitas tidak dapat dipilih | Tetapkan sumber dan format tautan yang kompatibel pada perancangan; sediakan kualitas tunggal sebagai cadangan |
| 3 | Literasi digital rendah dan staf takut salah | Aplikasi tidak dipakai | Pendekatan User-Centered Design, uji pada tiap iterasi, bahasa dan alur sederhana |
| 4 | Pendaftaran Learner terbuka tanpa verifikasi | Akun tidak relevan masuk ke rekap hasil | Superadmin dapat menghapus akun; filter rekap per periode |
| 5 | Ketersediaan mitra dan staf untuk uji terbatas oleh jam operasional | Data uji kurang | Jadwalkan uji pada pergantian shift dan jeda kerja |
| 6 | Keberlanjutan hosting dan pemeliharaan setelah capstone | Layanan berhenti | Dokumentasi dan panduan serah terima; komponen mudah dipelihara |
| 7 | Isi forum tidak pantas atau tidak akurat karena dijawab sesama Learner | Informasi keliru atau suasana tidak nyaman | Learner dapat melaporkan konten; Admin menerima notifikasi dan menghapus konten tidak pantas, serta membalas untuk meluruskan jawaban keliru |
| 8 | Pengguna iOS tidak terlayani | Sebagian calon pengguna tidak dapat memakai aplikasi | Dicatat sebagai pengembangan lanjutan di luar lingkup |

## 9. Timeline dan Milestone

Timeline mengacu pada proposal dan notulen rapat (identifikasi kebutuhan di Oktober, prototipe 50 sampai 75% di November, finalisasi di Desember sampai Januari).

| Tahap | Kegiatan Utama | Periode |
|---|---|---|
| Analisis kebutuhan | Wawancara, konfirmasi poin yang belum pasti, daftar kebutuhan, dan prioritas fitur | 30 Sep – 25 Okt 2026 |
| Perancangan arsitektur sistem | Arsitektur dan pemilihan teknologi, UI/UX (wireframe dan prototipe), kerangka materi, pre-test dan post-test | 26 Okt – 14 Nov 2026 |
| Implementasi iterasi 1 | Platform dan fitur akses materi dan video | 8 – 21 Nov 2026 |
| Implementasi iterasi 2 | Pre-test, post-test, dan kuis unit | 22 – 28 Nov 2026 |
| Implementasi iterasi 3 | Riwayat skor, autentikasi, dan persetujuan data | 29 Nov – 5 Des 2026 |
| Produksi materi | Video, bacaan, bank soal, dan kuis unit untuk dua modul sampel | 8 – 28 Nov 2026 |
| Evaluasi dan penyempurnaan | Peninjauan tiap iterasi bersama pemilik dan staf | 15 Nov – 5 Des 2026 |
| Uji pengguna | Staf memakai sistem di sela jam kerja; pengumpulan SUS, TAM, skor pre dan post, dan umpan balik | 6 – 12 Des 2026 |
| Analisis dan penyempurnaan akhir | Analisis hasil uji dan perbaikan sistem | 13 – 19 Des 2026 |
| Penggunaan sistem | Deploy, penyiapan akun, dokumentasi dan panduan, sosialisasi, serta serah terima | 13 Des 2026 – 2 Jan 2027 |

> Catatan: pada proposal, jadwal belum mencantumkan panel web Admin dan Superadmin secara eksplisit, serta kuis unit dan forum diskusi baru ditambahkan. Penjadwalan rinci keduanya perlu ditetapkan pada tahap perancangan arsitektur (misalnya kuis unit pada iterasi 2, forum diskusi pada iterasi 3, dan panel web paralel pada iterasi 1 sampai 3).

## 10. Kriteria Penerimaan dan Pengukuran

| No | Kriteria Penerimaan |
|---|---|
| 1 | Learner dapat mendaftar, menyetujui consent, login, dan menyelesaikan alur modul sampai post-test dan riwayat skor tanpa bantuan pengembang. |
| 2 | Video unit diputar langsung di dalam aplikasi dan dapat dilanjutkan dari posisi terakhir. |
| 3 | Setiap unit pada dua modul sampel memiliki kuis atau studi kasus 3 sampai 5 soal; Learner dapat kembali ke materi dan mengulang kuis. |
| 4 | Post-test baru terbuka setelah seluruh unit modul selesai; skor dan pembahasan tampil otomatis. |
| 5 | Skor Learner hanya terlihat oleh pemiliknya dan Admin. |
| 6 | Superadmin dapat menambah, melihat, mengedit, menghapus, dan mengubah role akun Admin, serta melihat, mengedit, menghapus, dan mengubah role akun Learner; Admin baru wajib mengganti password sementara pada login pertama. |
| 7 | Menu Superadmin tidak tampil bagi Admin, Superadmin tidak dapat mengakses fungsi Admin (misalnya menambah modul atau soal), dan akses langsung ke rute di luar hak role ditolak. |
| 8 | Admin dapat mengelola modul, unit, bank soal, dan kuis unit, menerbitkan modul, melihat rekap, dan mengekspor CSV. |
| 9 | Skor rata-rata SUS minimal 68 dan rata-rata skor post-test lebih tinggi daripada pre-test pada uji pengguna. |
| 10 | Forum diskusi modul terbuka setelah Learner menyelesaikan pre-test dan dapat diakses kapan saja; Learner dapat saling menjawab dan Pengajar dapat membalas; Learner dapat melaporkan konten, Admin menerima notifikasi dan dapat menghapus konten, sedangkan Learner tidak dapat menghapus. |
| 11 | Dokumentasi dan panduan penggunaan diserahkan kepada mitra bersama sosialisasi. |

## 11. Poin yang Masih Perlu Dikonfirmasi

| No | Poin | Asumsi Sementara dalam BRD |
|---|---|---|
| 1 | Makna "rollback" pada kuis unit | Pengguna dapat kembali ke materi unit saat hasil kuis kurang baik, lalu mengulang kuis; kuis tidak mengunci akses ke unit berikutnya secara otomatis |
| 2 | Syarat unit dianggap selesai | Unit selesai setelah materi dipelajari dan kuis unit dikerjakan, tanpa nilai minimum kelulusan |
| 3 | Bentuk soal kuis unit dan studi kasus | Pilihan ganda, dengan studi kasus berupa soal berbasis skenario |
| 4 | Pengelolaan akun Learner oleh Admin | Admin hanya melihat daftar Learner; edit, hapus, dan ubah role hanya oleh Superadmin |
| 5 | Sumber tautan video | Dipilih pada perancangan agar dapat diputar langsung di aplikasi |
| 6 | Teknologi pengembangan dan hosting | Ditentukan pada tahap perancangan arsitektur |
| 7 | Bentuk notifikasi laporan konten | Muncul di panel web Admin (tanpa push atau email) |
| 8 | Penanganan laporan | Konten yang dilaporkan tetap tampil sampai Admin menghapusnya; Admin juga dapat menandai laporan selesai tanpa menghapus bila konten dinilai wajar |

## 12. Persetujuan

Dengan menandatangani dokumen ini, pihak-pihak berikut menyatakan bahwa kebutuhan bisnis yang diuraikan telah ditinjau dan disetujui sebagai dasar tahap perancangan.

| Peran | Nama | Tanda Tangan | Tanggal |
|---|---|---|---|
| Pemilik UMKM mitra (Pasta Nafisa) | Ibu Nur | | |
| Dosen pendamping | Murahartawaty | | |
| Tim pengembang | Artadina Septiani | | |
| Tim pengembang | Kheista Anaya | | |
| Tim pengembang | Jasmine Adinda Wibowo | | |

## Lampiran A. Glosarium

| Istilah | Pengertian |
|---|---|
| Sinau | Nama aplikasi LMS yang dikembangkan. |
| LMS | Learning Management System: perangkat lunak untuk mengelola dan menyampaikan pembelajaran. |
| Learner | Pengguna aplikasi mobile yang belajar (pemilik, pengelola, atau staf UMKM). |
| Admin (Pengajar) | Pengguna panel web yang mengelola konten dan memantau hasil belajar. |
| Superadmin | Pengguna panel web yang khusus mengelola akun Admin dan Learner; tidak menjalankan fungsi Admin. |
| Modul | Satu topik pembelajaran yang terdiri atas beberapa unit, pre-test, dan post-test. |
| Unit | Bagian pendek dari modul berisi video, bacaan ringkas, dan kuis atau studi kasus. |
| Kuis unit | Kuis atau studi kasus 3 sampai 5 soal pada tiap unit sebagai latihan; tidak dihitung dalam skor pre dan post-test. |
| Forum diskusi | Ruang tanya jawab per modul yang terbuka setelah pre-test; dijawab sesama Learner dan dapat dibalas Pengajar; konten dapat dilaporkan Learner dan hanya dihapus oleh Admin. |
| Pre-test / Post-test | Tes sebelum modul (pengukur awal) dan setelah seluruh unit selesai (pengukur hasil). |
| Rollback (dalam konteks kuis unit) | Kembali ke materi unit untuk mempelajari ulang sebelum mengulang kuis. |
| Middleware | Lapisan di backend yang memeriksa hak akses (role) sebelum permintaan diproses. |
| SUS | System Usability Scale: kuesioner 10 butir untuk mengukur usabilitas. |
| TAM | Technology Acceptance Model: kerangka penerimaan teknologi (kebermanfaatan dan kemudahan). |
