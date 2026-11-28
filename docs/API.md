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

Semua endpoint di bagian ini membutuhkan Bearer token Sanctum milik akun ber-role `learner` dan consent yang sudah disetujui. Admin/Superadmin ditolak walaupun token mereka valid. Consent yang belum disetujui menghasilkan `403` dengan `code: "consent_required"`. Semua data progres dibatasi pada pengguna di token; request tidak menerima ID pengguna.

Modul tersedia hanya bila statusnya `published` dan tanggal hari ini berada di antara `active_from` dan `active_until` (inklusif). Unit yang ditampilkan atau diproses harus aktif. Unit berstatus `belum` bila belum memiliki baris progres, `sedang` bila memiliki progres yang belum selesai, dan `selesai` bila `is_completed=true`. Status tes adalah `belum`, `sedang`, atau `selesai`; post-test memakai `terkunci` sebelum semua unit aktif selesai, lalu `belum`/`sedang`/`selesai`. Durasi dan posisi video menggunakan detik.

| Method | URL | Tujuan | FR/AC |
|---|---|---|---|
| `GET` | `/api/modules` | Daftar modul aktif-terbit, jumlah unit aktif, estimasi durasi, dan progres pemilik token | FR-L08; AB-08; AC-14 |
| `GET` | `/api/modules/{module}` | Detail tujuan, unit aktif berurutan, status pre-test/post-test, dan progres | FR-L09; AB-03; AC-14 |
| `GET` | `/api/units/{unit}` | Materi unit aktif, data video aman, posisi terakhir, dan status selesai | FR-L10, FR-L11, FR-L12, FR-S07; AC-15, AC-16, AC-18, AC-19; EC-03 |
| `PUT` | `/api/units/{unit}/progress` | Simpan posisi video terakhir untuk pemilik token | FR-S03, FR-L10; AC-15 |
| `POST` | `/api/units/{unit}/complete` | Tandai unit selesai, hitung ulang progres modul, dan berikan unit aktif berikutnya | FR-L13; AC-17 |
| `GET` | `/api/home` | Ambil unit terakhir dipelajari untuk kartu “Lanjutkan belajar” | FR-L14; AC-17 |
| `GET` | `/api/units/{unit}/material/images/{image}` | Ambil gambar privat yang dirujuk materi unit dari modul aktif-terbit | FR-L11; AB-08; AC-14, AC-45 |

### GET /api/modules

**Auth:** Bearer token Learner + consent.
**FR/AC:** FR-L08, AB-08, AC-14.

**Respons sukses `200 OK`** — hanya modul published dalam periode aktif:

```json
{
  "data": [
    {
      "id": 8,
      "title": "Manajemen Rantai Pasok",
      "description": "Pelajari pengelolaan bahan baku dan persediaan.",
      "cover": "/storage/module-covers/supply-chain.jpg",
      "active_unit_count": 3,
      "estimated_duration": 900,
      "progress_percentage": 33
    }
  ]
}
```

`cover` adalah URL/path gambar sampul atau `null`. `active_unit_count` menghitung unit aktif saja. `estimated_duration` adalah jumlah durasi unit aktif yang memiliki durasi, dalam detik. `progress_percentage` adalah jumlah unit aktif selesai dibagi jumlah unit aktif, dibulatkan ke bilangan bulat; modul tanpa unit aktif bernilai `0`.

**Error:** `401` sesi tidak valid; `403` role bukan Learner atau consent belum diberikan (`consent_required`); `429` terlalu banyak permintaan.

### GET /api/modules/{module}

**Auth:** Bearer token Learner + consent.
**FR/AC:** FR-L09, AB-03, AB-08, AC-14.

**Respons sukses `200 OK`**

```json
{
  "data": {
    "id": 8,
    "title": "Manajemen Rantai Pasok",
    "description": "Pelajari pengelolaan bahan baku dan persediaan.",
    "learning_objectives": "Mampu mengelola bahan baku dan persediaan.",
    "progress_percentage": 33,
    "pre_test": { "status": "completed" },
    "post_test": { "status": "locked" },
    "units": [
      { "id": 21, "title": "Pemetaan pemasok", "sequence_order": 1, "status": "selesai" },
      { "id": 22, "title": "Pengendalian stok", "sequence_order": 2, "status": "sedang" },
      { "id": 23, "title": "Pencegahan waste", "sequence_order": 3, "status": "belum" }
    ]
  }
}
```

`pre_test.status` bernilai `belum`, `sedang`, atau `selesai` berdasarkan attempt pre-test milik Learner. `post_test.status` bernilai `terkunci` sampai seluruh unit aktif selesai; setelah itu `belum`, `sedang`, atau `selesai`. Endpoint ini hanya mencantumkan unit aktif. Modul tidak ditemukan atau tidak tersedia (termasuk draft, belum aktif, dan kedaluwarsa) menghasilkan `404`.

**Error:** `401`; `403` role/consent; `404` modul tidak ditemukan atau tidak tersedia; `429`.

### GET /api/units/{unit}

**Auth:** Bearer token Learner + consent.
**FR/AC:** FR-L10, FR-L11, FR-L12, FR-S07, AB-03, AB-08; AC-14, AC-15, AC-16, AC-18, AC-19; EC-03.

**Respons sukses `200 OK`**

```json
{
  "data": {
    "id": 22,
    "module": { "id": 8, "title": "Manajemen Rantai Pasok" },
    "title": "Pengendalian stok",
    "learning_objective": "Menentukan jumlah stok yang sesuai.",
    "duration": 300,
    "reading_content": "<p>Periksa stok secara berkala.</p>",
    "video": { "type": "youtube", "video_id": "dQw4w9WgXcQ" },
    "last_video_pos": 75,
    "is_completed": false
  }
}
```

`reading_content` adalah HTML tersanitasi; referensi gambar privat memakai URL API `/api/units/{unit}/material/images/{image}` dan memerlukan Bearer token. Video YouTube dikirim sebagai ID 11 karakter tervalidasi server dan `type: "youtube"`; URL sumber tidak dikirim. URL file langsung dikirim sebagai `{ "type": "file", "url": "https://cdn.example.test/video.mp4" }`. `duration`, `learning_objective`, atau `last_video_pos` dapat `null` bila tidak tersedia; `is_completed` default `false`.

Unit yang sudah selesai boleh dibuka kembali tanpa batas. Bila modul unit tidak tersedia/terbit, unit nonaktif, atau pre-test modul belum selesai, request ditolak dengan `403` dan kode:

| `code` | Makna | Pesan ramah |
|---|---|---|
| `module_unavailable` | Modul belum/tidak lagi tersedia | `Materi ini belum dapat diakses saat ini.` |
| `unit_inactive` | Unit dinonaktifkan | `Unit ini sedang tidak tersedia.` |
| `pretest_required` | Pre-test modul belum selesai | `Selesaikan pre-test modul terlebih dahulu untuk membuka materi.` |
| `consent_required` | Consent belum disetujui | `Anda perlu menyetujui penggunaan data sebelum mulai belajar.` |

Unit yang tidak ditemukan menghasilkan `404`. Error `422` dengan `code: "video_unavailable"` berarti URL video tersimpan belum valid/aman untuk dikirim; pesan: `Video unit belum dapat diputar.` Error lainnya: `401`, `403` untuk role/consent/kondisi akses di atas, `429`.

### PUT /api/units/{unit}/progress

**Auth:** Bearer token Learner + consent.
**FR/AC:** FR-S03, FR-L10; AC-15.

**Body request**

```json
{ "last_video_pos": 75 }
```

`last_video_pos` wajib bilangan bulat minimal `0` (detik). Server melakukan upsert dengan kunci unik pengguna-token dan unit; update posisi tidak mengubah `is_completed`. Baris baru dimulai dengan `is_completed=false`.

**Respons sukses `200 OK`**

```json
{
  "message": "Posisi belajar berhasil disimpan.",
  "data": { "unit_id": 22, "last_video_pos": 75, "is_completed": false }
}
```

**Error:** `401`; `403` untuk role, consent, modul/unit tidak tersedia, atau `pretest_required`; `404` unit tidak ditemukan; `422` validasi field (format error seragam); `429`.

### POST /api/units/{unit}/complete

**Auth:** Bearer token Learner + consent.
**FR/AC:** FR-L13, AB-03; AC-17.

**Body request:** tidak ada.

**Respons sukses `200 OK`** — panggilan berulang tetap sukses dan tidak menggandakan progres:

```json
{
  "message": "Unit berhasil ditandai selesai.",
  "data": {
    "unit_id": 22,
    "is_completed": true,
    "module_progress_percentage": 67,
    "next_unit": { "id": 23, "title": "Pencegahan waste" }
  }
}
```

`next_unit` adalah unit aktif berikutnya menurut `sequence_order`, atau `null` bila tidak ada. Persentase dihitung dari unit aktif saja. Error: `401`; `403` untuk role, consent, modul/unit tidak tersedia, atau `pretest_required`; `404` unit tidak ditemukan; `429`.

### GET /api/home

**Auth:** Bearer token Learner + consent.
**FR/AC:** FR-L14; AC-17.

**Respons sukses `200 OK`**

```json
{
  "data": {
    "continue_learning": {
      "unit_id": 22,
      "unit_title": "Pengendalian stok",
      "module_id": 8,
      "module_title": "Manajemen Rantai Pasok",
      "remaining_duration": 225
    }
  }
}
```

Unit terakhir adalah progres milik pemilik token dengan `updated_at` paling baru. Modul yang tidak published/dalam periode aktif dan unit nonaktif diabaikan. `remaining_duration` adalah sisa durasi dalam detik (minimal `0`), atau `null` bila durasi unit tidak diketahui. Jika tidak ada progres yang dapat ditampilkan, `continue_learning` bernilai `null`.

**Error:** `401`; `403` role atau consent; `429`.

### GET /api/units/{unit}/material/images/{image}

**Auth:** Wajib Bearer token Learner dan consent; tidak publik. Ini mengikuti `auth:sanctum` + role Learner pada arsitektur, ditambah pemeriksaan consent endpoint belajar. Gambar hanya dapat diambil bila modul unit published dalam periode aktif dan nama gambar dirujuk oleh materi unit tersebut; gambar dari modul draft/tidak tersedia tidak dapat diakses.

**Respons sukses `200 OK`:** byte gambar inline (`Content-Type: image/jpeg` atau `image/png`, `X-Content-Type-Options: nosniff`), bukan JSON.

**Error:** `401`; `403` role atau consent; `404` gambar tidak ditemukan, tidak dirujuk materi, atau modul tidak tersedia; `429`.

---

Semua endpoint di Bagian B tidak pernah mengirim skor, kunci jawaban, atau pembahasan soal. Endpoint gambar juga tidak mengizinkan akses lintas ke modul yang tidak tersedia. Respons error validasi `422` mengikuti format seragam di bagian Konvensi Umum.

---

## Bagian C — Evaluasi

Semua endpoint di bagian ini butuh token Learner dan consent. Tanpa consent, respons `403` dengan `code: "consent_required"`.

| Method | URL | Tujuan | FR/AC |
|---|---|---|---|
| `GET` | `/api/modules/{module}/tests/{type}` | Informasi pembuka, status pengerjaan, dan status kunci untuk pre/post; `type=pre|post` | FR-L17, FR-L18, FR-L19; AC-19, AC-20, AC-27 |
| `POST` | `/api/modules/{module}/tests/{type}/start` | Memulai atau melanjutkan satu pengerjaan milik Learner; pre hanya satu selesai, post dapat mulai ulang | FR-L22, FR-L23, AB-05; AC-21, AC-29 |
| `PUT` | `/api/test-attempts/{attempt}/answers` | Autosave satu atau beberapa jawaban milik pengerjaan itu | FR-L22, AB-17; AC-21 |
| `POST` | `/api/test-attempts/{attempt}/finish` | Menyelesaikan pengerjaan, menghitung skor di server dalam satu transaksi | FR-L20, FR-L22, NFR-09; AC-22, AC-28 |
| `GET` | `/api/test-attempts/{attempt}/result` | Hasil lengkap beserta kunci dan pembahasan hanya setelah selesai | FR-L20, FR-L21; AC-28 |
| `GET` | `/api/units/{unit}/quiz` | Memuat kuis 3–5 soal milik unit | FR-L28; AC-24, AC-25 |
| `POST` | `/api/units/{unit}/quiz/submit` | Mengirim semua jawaban kuis sekali jalan dan menyimpan satu attempt latihan | FR-L29, FR-L30, AB-04; AC-25, AC-26 |

`type` selain `pre` atau `post` ditolak `404`. Modul draft/tidak aktif menghasilkan `404`. Semua soal sebelum tes selesai atau kuis sebelum submit hanya memuat `id`, `question_text`, `question_format`, `options`, dan posisi soal. Field `correct_key`, `correct_keys`, dan `explanation` tidak boleh ada pada respons. Pembahasan dan kunci hanya ada pada hasil tes yang sudah selesai atau hasil submit kuis. Skor tes dan kuis dihitung dari jawaban yang diberikan; pilihan checkbox mendapat kredit parsial sesuai AB-17.

### GET /api/modules/{module}/tests/{type}

**Auth:** Bearer token Learner + consent.
**FR/AC:** FR-L17, FR-L18, FR-L19; AB-05; AC-19, AC-20, AC-27.

**Respons sukses `200 OK`**

```json
{
  "data": {
    "type": "pre",
    "title": "Pre-test Manajemen Rantai Pasok",
    "question_count": 5,
    "estimated_minutes": 10,
    "status": "belum_mulai",
    "locked": false,
    "pre_test_notice": "Ini hanya untuk mengetahui pemahaman awalmu, bukan penilaian",
    "attempt_id": null
  }
}
```

Untuk post-test yang terkunci:

```json
{
  "data": {
    "type": "post",
    "title": "Post-test Manajemen Rantai Pasok",
    "question_count": 5,
    "estimated_minutes": 10,
    "status": "terkunci",
    "locked": true,
    "remaining_active_units": 2,
    "message": "Selesaikan 2 unit tersisa untuk membuka post-test.",
    "hint": "Kembali ke materi, lalu tandai setiap unit sebagai selesai."
  }
}
```

Status bernilai `belum_mulai`, `sedang_berjalan`, `selesai`, atau `terkunci`. Pre-test yang selesai tidak dapat dimulai lagi. Post-test selesai dapat dipergunakan hanya untuk menampilkan status; memulai akan membuat pengerjaan baru.

**Error:** `401`; `403` consent/role; `404` modul tidak published/dalam periode aktif atau `type` tidak valid; `429`.

### POST /api/modules/{module}/tests/{type}/start

**Auth:** Bearer token Learner + consent.
**FR/AC:** FR-L17, FR-L18, FR-L22, FR-L23; AB-05, NFR-09; AC-21, AC-27, AC-29.

**Body request:** tidak ada.

**Respons sukses `200 OK` atau `201 Created`**

```json
{
  "message": "Pengerjaan tes dilanjutkan.",
  "data": {
    "attempt_id": 41,
    "type": "pre",
    "status": "sedang_berjalan",
    "questions": [
      {
        "id": 301,
        "position": 1,
        "question_text": "Apa langkah pertama?",
        "question_format": "multiple_choice",
        "options": {"A": "Memeriksa kebutuhan", "B": "Membeli stok", "C": "Menunggu laporan"}
      }
    ],
    "answers": {
      "301": ["A"]
    }
  }
}
```

Server memilih tepat jumlah soal yang dikonfigurasi. Bila `shuffle_questions=true`, urutan acak ditentukan saat attempt dibuat dan dipertahankan saat dilanjutkan. Jawaban awal kosong memakai `[]`; satu baris `test_attempt_answers` dibuat untuk setiap soal yang dipilih. Respons tidak memuat `correct_key`, `correct_keys`, atau `explanation`.

**Error:** `403` post-test terkunci atau role/consent dengan kode `post_test_locked`; `404` modul/tipe tidak ditemukan; `422` `pretest_already_done` atau `insufficient_questions`; `429`.

Contoh pre-test sudah selesai:

```json
{
  "message": "Pre-test modul ini sudah pernah diselesaikan dan tidak dapat diulang.",
  "code": "pretest_already_done"
}
```

Contoh bank soal kurang:

```json
{
  "message": "Bank soal modul belum memiliki cukup soal untuk tes ini.",
  "code": "insufficient_questions",
  "required": 5,
  "available": 3
}
```

### PUT /api/test-attempts/{attempt}/answers

**Auth:** Bearer token Learner + consent.
**FR/AC:** FR-L22, AB-17, AB-05; AC-21.

**Body request**

```json
{
  "answers": [
    {"question_id": 301, "selected_keys": ["A"]},
    {"question_id": 302, "selected_keys": ["B", "C"]}
  ]
}
```

Klien dapat mengirim satu atau beberapa jawaban. Untuk pilihan tunggal atau benar/salah, `selected_keys` dapat berisi nol atau satu kunci. Untuk soal pilihan ganda/benar-salah, gunakan `selected_key` tunggal sebagai alternatif kompatibel:

```json
{"question_id": 301, "selected_key": "A"}
```

Jawaban kosong boleh disimpan sebagai array kosong. `question_id` harus termasuk dalam kumpulan soal attempt; kunci harus valid untuk format soal.

**Respons sukses `200 OK`**

```json
{
  "message": "Jawaban berhasil disimpan.",
  "data": {
    "attempt_id": 41,
    "saved_question_ids": [301, 302],
    "status": "sedang_berjalan"
  }
}
```

**Error:** `401`; `403` role/consent atau attempt bukan milik pemilik token; `404` attempt tidak ditemukan untuk pemilik token; `422` validasi/soal diluar pengerjaan, format error seragam atau kode `question_outside_attempt`; `429`.

### POST /api/test-attempts/{attempt}/finish

**Auth:** Bearer token Learner + consent.
**FR/AC:** FR-L20, FR-L21, NFR-09, AB-05; AC-22, AC-28.

**Body request:** tidak ada. Klien harus mengonfirmasi soal kosong sebelum memanggil endpoint.

**Respons sukses `200 OK`**

```json
{
  "message": "Pengerjaan tes selesai.",
  "data": {
    "attempt_id": 41,
    "type": "pre",
    "status": "selesai",
    "score": 75.00,
    "appreciation": "Terima kasih sudah menyelesaikan tes.",
    "results": [
      {
        "question_id": 301,
        "position": 1,
        "question_text": "Apa langkah pertama?",
        "options": {"A": "Memeriksa kebutuhan", "B": "Membeli stok", "C": "Menunggu laporan"},
        "learner_answers": ["A"],
        "correct_keys": ["A"],
        "is_correct": true,
        "explanation": "Memeriksa kebutuhan adalah langkah awal."
      }
    ]
  }
}
```

Skor = jumlah nilai jawaban benar dibagi jumlah soal diberikan × 100, dibulatkan 2 desimal. Jawaban kosong bernilai salah. Untuk checkbox, nilai jawaban adalah jumlah kunci benar dipilih dibagi seluruh kunci benar, tanpa poin dari pilihan salah. Penyimpanan score, is_correct, dan completed_at dilakukan satu transaksi. Panggilan ulang attempt yang sudah selesai bersifat idempotent dan mengembalikan hasil yang sama.

**Error:** `401`; `403` role/consent atau attempt bukan milik pemilik token; `404` attempt tidak ditemukan; `429`.

### GET /api/test-attempts/{attempt}/result

**Auth:** Bearer token Learner + consent.
**FR/AC:** FR-L20, FR-L21; AB-05; AC-28.

**Respons sukses `200 OK`:** bentuk data sama dengan respons finish. Jika attempt belum selesai menghasilkan `403` dengan `code: "attempt_not_finished"` dan pesan `"Hasil tes baru tersedia setelah pengerjaan diselesaikan."`

**Error:** `401`; `403` role/consent, attempt bukan milik pemilik, atau belum selesai; `404` attempt tidak ditemukan; `429`.

### GET /api/units/{unit}/quiz

**Auth:** Bearer token Learner + consent.
**FR/AC:** FR-L28, AB-04; AC-24, AC-25.

**Respons sukses `200 OK`**

```json
{
  "data": {
    "unit_id": 22,
    "title": "Kuis: Pengendalian stok",
    "question_count": 4,
    "questions": [
      {
        "id": 501,
        "position": 1,
        "question_text": "Stok aman ditentukan dengan...",
        "question_format": "multiple_choice",
        "options": {"A": "Memperhatikan kebutuhan", "B": "Menebak"}
      }
    ]
  }
}
```

Unit harus aktif dan modulnya published; pre-test modul harus sudah selesai seperti Bagian B. Unit dikembalikan dengan 3–5 soal. Respons tidak memuat kunci atau pembahasan.

**Error:** `401`; `403` role/consent atau unit tidak tersedia/pre-test belum selesai; `404` unit tidak ditemukan; `422` kuis tidak memiliki 3–5 soal dengan `code: "invalid_quiz_question_count"`; `429`.

### POST /api/units/{unit}/quiz/submit

**Auth:** Bearer token Learner + consent.
**FR/AC:** FR-L29, FR-L30, AB-04, AB-17; AC-25, AC-26.

**Body request**

```json
{
  "answers": [
    {"question_id": 501, "selected_keys": ["A"]},
    {"question_id": 502, "selected_keys": ["B", "C"]}
  ]
}
```

Semua soal kuis harus dikirim. Format dan validasi sama dengan autosave tes; jawaban yang kosong dapat dikirim sebagai array kosong. Untuk kuis yang kurang memuaskan, respons tetap menyimpan attempt dan menawarkan rollback.

**Respons sukses `200 OK`**

```json
{
  "message": "Kuis unit selesai.",
  "data": {
    "attempt_id": 77,
    "unit_id": 22,
    "score": 50.00,
    "results": [
      {
        "question_id": 501,
        "question_text": "Stok aman ditentukan dengan...",
        "options": {"A": "Memperhatikan kebutuhan", "B": "Menebak"},
        "learner_answers": ["A"],
        "correct_keys": ["A"],
        "is_correct": true,
        "explanation": "Menentukan kebutuhan adalah dasar pengendalian stok."
      }
    ],
    "satisfactory": false,
    "return_to_material": true,
    "hint": "Lihat kembali materi unit, lalu ulangi kuis kapan saja."
  }
}
```

Skor kuis memakai rumus yang sama dengan tes; kuis unit tidak mengubah `test_attempts`, status selesai unit, atau kunci pembuka unit berikutnya. Setiap submit disimpan sebagai baris baru `unit_quiz_attempts`.

**Error:** `401`; `403` role/consent atau unit tidak tersedia/pre-test belum selesai; `404` unit tidak ditemukan; `422` validasi atau soal diluar unit/kunci tidak valid/soal kuis tidak lengkap; `429`.

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
