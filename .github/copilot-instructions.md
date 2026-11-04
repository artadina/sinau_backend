# Aturan Proyek Sinau

## Sumber kebenaran
- Satu-satunya sumber kebutuhan: `docs/BRD.md`, `docs/PRD.md`, `docs/ERD.md`.
- Jika dokumen bertentangan atau tidak menjelaskan sesuatu: JANGAN mengarang. Berhenti, tulis pertanyaan, dan tunggu jawabanku.
- Urutan prioritas bila konflik: PRD > BRD > ERD (kecuali catatan PRD 7.4 yang sudah kuputuskan di `docs/DECISIONS.md`).
- Setiap file/fungsi/test penting diberi komentar singkat berisi ID kebutuhan yang dipenuhi (contoh: `// FR-L03, AC-05`).

## Referensi desain
- `docs/design/sinau_admin_ui_design.html` HANYA referensi visual panel web (warna, tipografi, layout, gaya komponen).
- Jangan menyalin isi, data dummy, fitur, atau logika dari file itu. Semua fitur, field, menu, dan alur harus dari PRD.
- Jangan menyalin fitur "Switch role" (itu simulasi di mockup). Role hanya ditentukan backend dari akun login.

## Aturan keras (tidak boleh dilanggar)
- Role ditetapkan di backend; input role dari klien ditolak (FR-S01). Registrasi publik selalu role `learner`.
- Middleware role di setiap rute/API panel web (FR-S05, FR-SA07). Menyembunyikan menu bukan satu-satunya pembatas.
- Superadmin TIDAK boleh mengakses konten, bank soal, rekap skor, atau forum Admin. Admin hanya melihat daftar Learner (AB-13).
- Skor hanya bisa dilihat pemilik dan Admin (AB-09). Learner tidak bisa melihat data Learner lain lewat URL/API.
- Skor dihitung di server, penyimpanan hasil tes dalam satu transaksi (NFR-09).
- Learner tidak bisa menghapus konten forum, termasuk miliknya sendiri (AB-07).
- Validasi 3–5 soal kuis per unit dilakukan di server saat unit diterbitkan (AB-04).
- Password di-hash. Password sementara dibuat sistem, bukan diinput manual, dan wajib diganti saat login pertama (AB-14).
- Pesan error berbahasa Indonesia, ramah, tampil dekat field (AB-16). Login gagal memakai pesan umum.
- Tidak ada peringkat antar pengguna.

## Gaya kode
- Kode terstruktur, nama file/kelas dalam bahasa Inggris, teks antarmuka dalam bahasa Indonesia.
- Setiap fase menyertakan migration, seeder, dan test untuk AC yang relevan.
- Jangan menambah fitur di luar PRD (lihat PRD bagian 2.2 "Di Luar Lingkup").
- Jangan install dependency berbayar (NFR-17).

## Cara kerja
- Kerjakan hanya fase yang kuminta. Sebelum menulis kode, tampilkan rencana file yang akan dibuat/diubah.
- Setelah selesai, buat daftar: FR/AC yang sudah dipenuhi, yang belum, dan asumsi yang kamu ambil.