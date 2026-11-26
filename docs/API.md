# Kontrak API Learner — Laravel Sanctum

Dokumen ini adalah kontrak API untuk alur Learner Android. Sumber kebutuhan: `PRD.md`, `BRD.md`, `ERD.md`, `ARCHITECTURE.md`, dan `DECISIONS.md`.

## Konvensi Umum

- Semua URL diawali `/api`.
- Format data: JSON.
- Header autentikasi: `Authorization: Bearer {token}`.
- Header JSON: `Accept: application/json`; tambahan `Content-Type: application/json` pada body JSON.
- `401` dipakai untuk token tidak ada/tidak valid atau login gagal.
- `403` dipakai untuk role tidak sesuai atau consent belum diberikan.
- `404` dipakai untuk data yang tidak ditemukan atau tidak boleh diakses oleh akun autentikasi.
- `422` dipakai untuk validasi atau kondisi bisnis yang ditolak.
- `429` dipakai untuk throttling.
- Semua URL dan error memakai bahasa Indonesia pada respons.
- Semua data yang terikat Learner dibatasi ke akun terautentikasi. Superadmin dan Admin tidak boleh memakai API Learner.

### Bentuk Error Validasi 422 Seragam

```json
{
  "message": "Data yang dikirim belum lengkap atau belum valid.",
  "errors": {
    "email": [
      "Email sudah digunakan oleh akun lain."
    ]
  }
}
```

Pesan `errors` harus selalu berisi nama field dan daftar pesan Indonesia. Field yang tidak bermasalah tidak boleh dikirim. Untuk satu field, pesan ditulis sebagai satu atau lebih string yang ramah.

### Bentuk Error Umum

```json
{
  "message": "Pesan Indonesia yang ramah."
}
```

Untuk akses tanpa consent pada endpoint belajar:

```json
{
  "message": "Anda perlu menyetujui penggunaan data sebelum mulai belajar.",
  "code": "consent_required"
}
```

---

## Bagian A — Akun Learner

### POST /api/register

**Tujuan:** Registrasi Learner terbuka. Role selalu `learner`; field `role` dari klien diabaikan dan tidak boleh menentukan role.  
**Auth:** Publik.  
**FR/AC:** FR-L01, FR-S01, AB-01, AB-15, AB-16; AC-02, AC-03.

**Body request**

```json
{
  "name": "Rani",
  "email": "rani@example.com",
  "password": "Rahasia123",
  "password_confirmation": "Rahasia123",
  "business_name": "Usaha Rani"
}
```

Aturan validasi:

| Field | Aturan |
|---|---|
| `name` | Wajib, string, maksimal 100 karakter |
| `email` | Wajib, format email valid, unik |
| `password` | Wajib, string, minimal 8 karakter |
| `password_confirmation` | Wajib dan sama dengan `password` |
| `business_name` | Opsional, string, maksimal 150 karakter |
| `role` | Jika dikirim, diabaikan oleh server; tidak boleh mengubah role |

**Respons sukses `201 Created`**

```json
{
  "message": "Pendaftaran berhasil.",
  "data": {
    "user": {
      "id": 12,
      "name": "Rani",
      "email": "rani@example.com",
      "business_name": "Usaha Rani",
      "role": "learner",
      "consent_given": false,
      "consent_at": null
    },
    "token": "1|contoh-token-sanctum",
    "consent_required": true
  }
}
```

**Error**

- `422` email duplikat:

```json
{
  "message": "Data yang dikirim belum lengkap atau belum valid.",
  "errors": {
    "email": [
      "Email sudah digunakan oleh akun lain."
    ]
  }
}
```

- `422` validasi lain menggunakan format field yang sama.
- `429` jika registrasi dibatasi untuk mencegah penyalahgunaan. Pesan: `"Terlalu banyak percobaan. Silakan coba lagi beberapa saat."`

---

### POST /api/consent

**Tujuan:** Menandai bahwa Learner menyetujui penggunaan data. Server mengisi `consent_at` dengan waktu persetujuan. Data belajar tidak boleh dicatat sebelum consent aktif.  
**Auth:** Token Sanctum. Learner dengan role lain ditolak.  
**FR/AC:** FR-L02, AB-11, AC-04.

**Body request**

```json
{
  "consent_given": true
}
```

Aturan validasi:

| Field | Aturan |
|---|---|
| `consent_given` | Wajib, boolean, hanya nilai `true` yang diterima |

**Respons sukses `200 OK`**

```json
{
  "message": "Persetujuan penggunaan data berhasil disimpan.",
  "data": {
    "consent_given": true,
    "consent_at": "2026-11-26T14:51:43+07:00",
    "consent_required": false
  }
}
```

**Error**

- `401`: `"Sesi login Anda sudah berakhir. Silakan login kembali."`
- `403`: role bukan Learner atau access dilakukan pada akun Learner yang ditolak middleware.
- `422`: format field sama. Contoh:

```json
{
  "message": "Data yang dikirim belum lengkap atau belum valid.",
  "errors": {
    "consent_given": [
      "Anda perlu mencentang persetujuan untuk melanjutkan."
    ]
  }
}
```

- `429`: `"Terlalu banyak percobaan. Silakan coba lagi beberapa saat."`

---

### POST /api/login

**Tujuan:** Login Learner dan menerbitkan token Sanctum. Akun `admin` dan `superadmin` ditolak dengan pesan umum yang sama dengan kredensial salah.  
**Auth:** Publik.  
**Throttle:** Ya.  
**FR/AC:** FR-L03, FR-S02, AB-02, EC-01; AC-05, AC-07.

**Body request**

```json
{
  "email": "rani@example.com",
  "password": "Rahasia123"
}
```

Aturan validasi:

| Field | Aturan |
|---|---|
| `email` | Wajib, format email valid |
| `password` | Wajib |

**Respons sukses `200 OK`**

```json
{
  "message": "Login berhasil.",
  "data": {
    "user": {
      "id": 12,
      "name": "Rani",
      "email": "rani@example.com",
      "business_name": "Usaha Rani",
      "role": "learner",
      "consent_given": false,
      "consent_at": null
    },
    "token": "2|contoh-token-sanctum",
    "consent_required": true
  }
}
```

`consent_required` bernilai `false` bila `consent_given=true`.

**Error**

- `401` untuk email atau password salah, atau akun bukan Learner:

```json
{
  "message": "Email atau password tidak sesuai."
}
```

Pesan ini tidak boleh membedakan email tidak terdaftar, password salah, atau akun Admin/Superadmin.

- `422` untuk body tidak memenuhi validasi:

```json
{
  "message": "Data yang dikirim belum lengkap atau belum valid.",
  "errors": {
    "email": [
      "Email wajib diisi."
    ],
    "password": [
      "Password wajib diisi."
    ]
  }
}
```

- `429`: `"Terlalu banyak percobaan login. Silakan coba lagi beberapa saat."`

---

### POST /api/logout

**Tujuan:** Membatalkan token Sanctum yang sedang dipakai.  
**Auth:** Token Sanctum.  
**FR/AC:** FR-L03, AC-05.

**Body request:** Tidak ada.

**Respons sukses `200 OK`**

```json
{
  "message": "Anda berhasil keluar."
}
```

**Error**

- `401`: `"Sesi login Anda sudah berakhir. Silakan login kembali."`
- `403`: role selain Learner atau akses yang membatasi API Learner.

---

### GET /api/me

**Tujuan:** Mengambil data pengguna Learner yang terautentikasi beserta status consent.  
**Auth:** Token Sanctum.  
**FR/AC:** FR-L03, FR-L05, AB-11.

**Respons sukses `200 OK`**

```json
{
  "data": {
    "id": 12,
    "name": "Rani",
    "email": "rani@example.com",
    "business_name": "Usaha Rani",
    "role": "learner",
    "consent_given": true,
    "consent_at": "2026-11-26T14:51:43+07:00",
    "consent_required": false
  }
}
```

**Error**

- `401`: `"Sesi login Anda sudah berakhir. Silakan login kembali."`
- `403`: role selain Learner.

---

### PUT /api/profile

**Tujuan:** Mengubah nama dan nama usaha Learner. Email tidak dapat diubah melalui endpoint ini.  
**Auth:** Token Sanctum.  
**FR/AC:** FR-L05, AB-16.

**Body request**

```json
{
  "name": "Rani A.",
  "business_name": "Toko Rani"
}
```

Aturan validasi:

| Field | Aturan |
|---|---|
| `name` | Wajib, string, maksimal 100 karakter |
| `business_name` | Opsional, string, maksimal 150 karakter |
| `email` | Jika dikirim, diabaikan atau ditolak; email tidak dapat diubah |

**Respons sukses `200 OK`**

```json
{
  "message": "Profil berhasil diperbarui.",
  "data": {
    "id": 12,
    "name": "Rani A.",
    "email": "rani@example.com",
    "business_name": "Toko Rani",
    "role": "learner",
    "consent_given": true,
    "consent_at": "2026-11-26T14:51:43+07:00",
    "consent_required": false
  }
}
```

**Error**

- `401`: `"Sesi login Anda sudah berakhir. Silakan login kembali."`
- `403`: role selain Learner.
- `422`: validasi field `name` atau `business_name` dalam format seragam.
- `429`: `"Terlalu banyak percobaan. Silakan coba lagi beberapa saat."`

---

### PUT /api/profile/password

**Tujuan:** Mengubah password dan mencabut semua token lain, sehingga perangkat lain yang terlogin dipaksa login kembali.  
**Auth:** Token Sanctum.  
**FR/AC:** FR-L05, AB-16.

**Body request**

```json
{
  "current_password": "Rahasia123",
  "password": "RahasiaBaru123",
  "password_confirmation": "RahasiaBaru123"
}
```

Aturan validasi:

| Field | Aturan |
|---|---|
| `current_password` | Wajib, harus cocok dengan password akun |
| `password` | Wajib, string, minimal 8 karakter, berbeda dari password lama |
| `password_confirmation` | Wajib dan sama dengan `password` |

**Respons sukses `200 OK`**

```json
{
  "message": "Password berhasil diubah. Perangkat lain sudah keluar dari akun."
}
```

**Error**

- `401`: `"Sesi login Anda sudah berakhir. Silakan login kembali."`
- `403`: role selain Learner.
- `422` password lama salah:

```json
{
  "message": "Data yang dikirim belum lengkap atau belum valid.",
  "errors": {
    "current_password": [
      "Password saat ini tidak sesuai."
    ]
  }
}
```

- `422` untuk validasi `password` atau `password_confirmation`.
- `429`: `"Terlalu banyak percobaan ubah password. Silakan coba lagi beberapa saat."`

---

### POST /api/forgot-password

**Tujuan:** Meminta tautan reset password. Respons selalu sama untuk email terdaftar maupun tidak terdaftar.  
**Auth:** Publik.  
**Throttle:** Ya.  
**FR/AC:** FR-L04, BR-L03, NFR-05; AC-06.

**Body request**

```json
{
  "email": "rani@example.com"
}
```

Aturan validasi:

| Field | Aturan |
|---|---|
| `email` | Wajib, format email valid |

**Respons sukses `200 OK`, untuk semua email**

```json
{
  "message": "Jika email terdaftar pada Sinau, tautan reset password telah dikirim."
}
```

Respons ini tidak boleh membocorkan apakah email terdaftar.

**Cara pemakaian Learner**

Learner menerima tautan reset pada email. Tautan membuka halaman web reset password yang sudah tersedia di aplikasi Sinau, bukan API mobile. Learner memasukkan password baru dan konfirmasi password di halaman tersebut. Tautan berlaku terbatas waktu dan hanya dapat dipakai sekali; setelah dipakai atau kedaluwarsa, tautan tidak dapat dipakai lagi.

Setelah reset berhasil, halaman web menampilkan:

```text
Password berhasil diubah, silakan kembali ke aplikasi Sinau
```

Learner tidak diarahkan ke panel Admin maupun panel Superadmin.

**Error**

- `422`:

```json
{
  "message": "Data yang dikirim belum lengkap atau belum valid.",
  "errors": {
    "email": [
      "Masukkan format email yang benar."
    ]
  }
}
```

- `429`: `"Terlalu banyak permintaan reset password. Silakan coba lagi beberapa saat."`

---

### DELETE /api/account

**Tujuan:** Learner mengonfirmasi password lalu meminta penghapusan akun. Pelaksanaan harus atomik: soft delete akun, anonimisasi identitas, anonimisasi nama dan isi konten forum milik Learner, cabut semua token, dan pertahankan catatan belajar tanpa identitas personal.  
**Auth:** Token Sanctum.  
**FR/AC:** FR-L06, AB-11, NFR-06, keputusan 6 pada `DECISIONS.md`; AC-43, EC-23.

**Body request**

```json
{
  "password": "Rahasia123"
}
```

Aturan validasi:

| Field | Aturan |
|---|---|
| `password` | Wajib, harus cocok dengan password akun saat ini |

**Respons sukses `200 OK`**

```json
{
  "message": "Akun dan data personal Anda berhasil dihapus."
}
```

**Error**

- `401`: `"Sesi login Anda sudah berakhir. Silakan login kembali."`
- `403`: role selain Learner.
- `422` password salah atau tidak dikirim:

```json
{
  "message": "Data yang dikirim belum lengkap atau belum valid.",
  "errors": {
    "password": [
      "Password tidak sesuai."
    ]
  }
}
```

- `429`: `"Terlalu banyak percobaan. Silakan coba lagi beberapa saat."`

---

## Bagian B — Materi Pembelajaran

Semua endpoint di bagian ini butuh token Learner dan consent. Tanpa consent, respons `403` dengan `code: "consent_required"`.

| Method | URL | Tujuan | FR/AC |
|---|---|---|---|
| `GET` | `/api/modules` | Daftar modul published dalam periode aktif, beserta progres ringkas | FR-L08, FR-L26; AC-14, AC-30 |
| `GET` | `/api/modules/{module}` | Detail modul, tujuan, unit berurutan, status pre/post, dan progres | FR-L09, FR-L26; AB-03 |
| `GET` | `/api/modules/{module}/continue` | Mengambil unit terakhir untuk kartu lanjutkan belajar | FR-L14; AC-17 |
| `GET` | `/api/units/{unit}` | Membuka unit aktif dan mengambil materi serta status | FR-L11, FR-L12; AC-18, AC-19 |
| `GET` | `/api/units/{unit}/video` | Data pemutar video dan posisi terakhir | FR-L10, FR-S07; AC-15, AC-16 |
| `PUT` | `/api/units/{unit}/video-position` | Menyimpan posisi video | FR-L10, FR-S03; AC-15 |
| `POST` | `/api/units/{unit}/complete` | Menandai unit selesai dan memperbarui progres | FR-L13; AC-17 |

Materi yang ditampilkan tidak boleh memuat `correct_key` atau pembahasan soal. Soal baru dirinci pada tahap implementasi evaluasi.

---

## Bagian C — Evaluasi

Semua endpoint di bagian ini butuh token Learner dan consent. Tanpa consent, respons `403` dengan `code: "consent_required"`.

| Method | URL | Tujuan | FR/AC |
|---|---|---|---|
| `GET` | `/api/modules/{module}/pre-test` | Membuka pembuka pre-test sebelum unit dimulai | FR-L17, FR-L19; AC-19 |
| `POST` | `/api/modules/{module}/pre-test/attempts` | Memulai atau melanjutkan attempt pre-test milik Learner | FR-L22; AC-21 |
| `PUT` | `/api/test-attempts/{attempt}/answers/{question}` | Menyimpan jawaban dan progres tes | FR-L19, FR-L22; AC-21 |
| `POST` | `/api/test-attempts/{attempt}/submit` | Mengirim tes, menghitung skor di server dalam satu transaksi | FR-L20, NFR-09; AC-28 |
| `GET` | `/api/modules/{module}/post-test` | Membuka post-test setelah semua unit selesai | FR-L18; AC-27 |
| `POST` | `/api/modules/{module}/post-test/attempts` | Memulai pengerjaan post-test baru | FR-L23; AC-29 |
| `GET` | `/api/units/{unit}/quiz` | Memuat kuis 3–5 soal milik unit | FR-L28; AC-24, AC-25 |
| `POST` | `/api/units/{unit}/quiz/attempts` | Menyimpan hasil kuis unit sebagai latihan | FR-L29, FR-L30; AC-25, AC-26 |

Soal tes atau kuis hanya boleh mengirim teks soal, pilihan, dan format soal. Field `correct_key`, `correct_keys`, dan `explanation` tidak boleh dikirim saat soal ditampilkan. Pembahasan hanya boleh dikirim setelah submit atau hasil kuis selesai. Skor checkbox dihitung di server sesuai AB-17.

---

## Bagian D — Riwayat dan Forum

Semua endpoint di bagian ini butuh token Learner dan consent. Tanpa consent, respons `403` dengan `code: "consent_required"`. Riwayat dan forum selalu dibatasi ke akun Learner yang sesuai.

| Method | URL | Tujuan | FR/AC |
|---|---|---|---|
| `GET` | `/api/history/modules/{module}` | Riwayat skor pre/post milik Learner beserta tanggal | FR-L24; AC-30 |
| `GET` | `/api/history/progress` | Ringkasan progres seluruh modul dan lencana personal | FR-L26, FR-L27; AC-17, AC-30 |
| `GET` | `/api/history/modules/{module}/comparison` | Perbandingan skor pre dan post | FR-L25; AC-28 |
| `GET` | `/api/modules/{module}/forum/posts` | Daftar forum setelah pre-test selesai | FR-L31, FR-L34; AC-32 |
| `POST` | `/api/modules/{module}/forum/posts` | Membuat topik, opsional dikaitkan ke unit | FR-L32; AC-33 |
| `POST` | `/api/forum/posts/{post}/replies` | Membalas topik atau balasan | FR-L33; AC-33 |
| `POST` | `/api/forum/posts/{post}/reports` | Melaporkan topik atau balasan | FR-L35; AC-34 |

Learner tidak mempunyai endpoint DELETE untuk konten forum. Permintaan hapus ditolak `403` atau `404`, dan tidak pernah mengizinkan penghapusan milik sendiri. Pelaporan yang sama dua kali ditolak `422` atau dianggap sudah tercatat dengan pesan ramah. Konten tetap tampil sampai Admin bertindak.

---

## Perilaku Error Umum pada Endpoint B–D

- `401`: token tidak ada, tidak valid, atau dicabut.
- `403`: role bukan Learner, consent belum disetujui, atau pengguna mencoba akses di luar wewenangnya. Untuk consent belum disetujui, gunakan `code: "consent_required"`.
- `404`: modul, unit, attempt, kuis, atau posting tidak tersedia, bukan milik Learner, atau tidak boleh diakses pada state saat ini.
- `422`: state alur tidak valid, soal kosong, kuis belum memenuhi batas, atau validasi body gagal.
- `429`: untuk endpoint submit, update posisi, posting forum, balasan, atau pelaporan yang diberi throttle.

Contoh `404` ramah:

```json
{
  "message": "Data yang diminta tidak ditemukan atau belum dapat diakses."
}
```

Contoh `422` state bisnis:

```json
{
  "message": "Data yang dikirim belum lengkap atau belum valid.",
  "errors": {
    "post_test": [
      "Post-test baru dapat dibuka setelah semua unit selesai."
    ]
  }
}
```

---

## Asumsi

1. Format token adalah Bearer token Laravel Sanctum.
2. Nama field respons utama memakai struktur `data` dan pesan sukses terpisah pada akun.
3. Nama dan bentuk URL endpoint Bagian B–D di atas masih kontrak ringkas dan akan disempurnakan saat fase implementasi masing-masing.
4. Batas konten forum maksimum 1000 karakter pada PRD masih ditandai usulan sehingga belum dipastikan sebagai aturan final.
5. Tautan reset password mengarah ke halaman web reset yang sudah ada untuk akun panel; halaman tersebut tetap menampilkan pesan yang diarahkan kembali ke aplikasi Sinau.
6. Hapus akun bersifat soft delete, dengan anonimisasi identitas dan isi forum dalam satu transaksi; data riwayat belajar dipertahankan tanpa identitas personal menurut keputusan 6.

## Pertanyaan Terbuka

1. Konfirmasi apakah akun Admin atau Superadmin yang memanggil login API harus mendapat `401` dengan pesan umum, atau boleh `403` terpisah. Kontrak ini memakai `401` agar sama dengan kredensial salah.
2. Konfirmasi lamanya throttle untuk login, forgot-password, dan ubah password.
3. Konfirmasi format payload `PUT /api/profile/password` untuk respons token saat ini: apakah token aktif dipertahankan atau juga dicabut.
4. Konfirmasi apakah reset password Learner memakai route web yang sama dengan Admin tetapi dibatasi berdasarkan role dan tetap menampilkan pesan sukses khusus aplikasi.
