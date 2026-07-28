<div align="center">

# 🏛️ SIRAMBO
### Sistem Rekonsiliasi Angka PDRB

**Aplikasi web internal berbasis Laravel untuk perhitungan, rekonsiliasi, dan analisis data**
**Produk Domestik Regional Bruto (PDRB) tingkat Kabupaten/Kota dan Provinsi.**

[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://mysql.com)
[![Vite](https://img.shields.io/badge/Vite-7.x-646CFF?style=for-the-badge&logo=vite&logoColor=white)](https://vitejs.dev)
[![Status](https://img.shields.io/badge/Status-Production_(Tahap_2)-28a745?style=for-the-badge)]()
[![License](https://img.shields.io/badge/Lisensi-Internal_BPS-0d6efd?style=for-the-badge)]()

</div>

---

## 🏢 Tentang Instansi

Aplikasi ini dikembangkan oleh dan untuk:

> **Badan Pusat Statistik (BPS)**
> **Provinsi Sulawesi Tenggara**
>
> Jl. Haluoleo No. 1, Kendari, Sulawesi Tenggara 93117
> 🌐 [sultra.bps.go.id](https://sultra.bps.go.id) | 📧 bps7400@bps.go.id | ☎️ (0401) 323-116

SIRAMBO mengelola data PDRB seluruh **17 Kabupaten/Kota** di Provinsi Sulawesi Tenggara, yaitu:
Kota Kendari, Kota Baubau, Kab. Konawe, Kab. Konawe Selatan, Kab. Konawe Utara, Kab. Konawe Kepulauan, Kab. Kolaka, Kab. Kolaka Utara, Kab. Kolaka Timur, Kab. Bombana, Kab. Buton, Kab. Buton Selatan, Kab. Buton Tengah, Kab. Buton Utara, Kab. Muna, Kab. Muna Barat, dan Kab. Wakatobi.

---

## 📌 Deskripsi Proyek

**SIRAMBO** *(Sistem Rekonsiliasi Angka PDRB)* adalah platform web kolaboratif yang dikembangkan khusus untuk **BPS Provinsi Sulawesi Tenggara**. Aplikasi ini menggantikan proses perhitungan PDRB yang sebelumnya dilakukan secara manual menggunakan file Excel yang terpisah-pisah antardaerah — yang rentan terhadap *human error*, tidak tersinkronisasi secara *real-time*, dan sulit diaudit.

SIRAMBO menghadirkan satu sistem terpusat di mana petugas Kabupaten/Kota dan Tim Provinsi dapat berkolaborasi secara bersamaan dalam satu platform untuk menghasilkan data PDRB yang akurat, transparan, dan siap rilis.

### ✨ Fitur Unggulan

| Fitur | Deskripsi |
|---|---|
| 📥 **Import Data PDRB** | Import data agregat Lapangan Usaha & Pengeluaran (Kab/Kota & Provinsi) via template Excel |
| 📝 **Fenomena Ekonomi** | Input & penilaian fenomena sesuai ketentuan yang memengaruhi naik-turunnya angka PDRB |
| 🤝 **Rekonsiliasi** | Rekonsiliasi data Kab/Kota dan Provinsi (lapangan usaha & pengeluaran) agar sesuai dan selaras |
| 📈 **Analisis Makroekonomi** | Penghitungan otomatis QoQ, YoY, CtC, Indeks Implisit, Struktur Dalam & Antar Wilayah |
| 📊 **Tabel Dinamis & Export** | Tampilan tabel dinamis dan ekspor data hasil rekonsiliasi final ke Excel |
| 🔔 **Real-time Sinkronisasi** | Data terupdate otomatis tanpa *refresh* halaman menggunakan Laravel Reverb (WebSocket) |
| 🔐 **Penguncian & Rilis Data** | Mekanisme *lock/unlock* dan rilis data final wilayah |
| 🔑 **SSO BPS (Keycloak)** | Terintegrasi dengan Single Sign-On BPS berbasis Keycloak melalui Laravel Socialite |
| 📋 **Lembar Kerja Dinamis** | Form input komoditas sektoral berbasis JSON dengan formula otomatis *(dalam pengembangan)* |

---

## 👥 Persyaratan Pengguna (User Roles)

Aplikasi SIRAMBO menerapkan sistem hak akses berbasis peran (*Role-Based Access Control*) untuk menjaga keamanan dan integritas data:

| Role | Deskripsi Hak Akses |
|---|---|
| 🏛️ **`provinsi`** | Administrator Provinsi. Dapat mengakses seluruh data Kab/Kota, memasukkan *adjustment* pada Rekonsiliasi, merilis data, serta mengunci/membuka kunci form input wilayah. |
| 👁️ **`provinsi_supervisor`** | Setara dengan `provinsi`, fokus pada verifikasi, supervisi, dan persetujuan data akhir sebelum dirilis. |
| 🏢 **`kabupaten` / `kota`** | Operator daerah. Hanya dapat melihat dan mengedit data **wilayah mereka sendiri** (dilindungi oleh middleware `akses.wilayah`). Meliputi import data, input fenomena, dan rekonsiliasi daerah. |

---

## ⚙️ Persyaratan Sistem (System Requirements)

Sebelum melakukan instalasi, pastikan server atau mesin lokal Anda memenuhi prasyarat berikut:

| Komponen | Versi Minimum | Keterangan |
|---|---|---|
| **PHP** | `>= 8.2` | Ekstensi wajib: `pdo_mysql`, `openssl`, `mbstring`, `xml`, `curl`, `zip`, `gd` |
| **MySQL** | `>= 8.0` | Alternatif: MariaDB `>= 10.4` |
| **Composer** | `>= 2.x` | Manajer paket PHP |
| **Node.js & NPM** | Node `>= 18.x` | Untuk kompilasi aset frontend dengan Vite |
| **Web Server** | - | **Laragon** (direkomendasikan untuk Windows), XAMPP, atau Apache/Nginx |

---

## 🚀 Instalasi

### Langkah 1 — Clone Repositori

```bash
git clone <url-repository> pdrb-app
cd pdrb-app
```

### Langkah 2 — Instal Dependensi PHP

```bash
composer install
```

### Langkah 3 — Konfigurasi Environment

```bash
# Salin file .env.example
cp .env.example .env

# Generate kunci enkripsi aplikasi
php artisan key:generate
```

Buka file `.env` dan sesuaikan konfigurasi database Anda:

```env
APP_NAME=Sirambo
APP_ENV=local
APP_DEBUG=true
APP_URL=http://pdrb-app.test

# Konfigurasi Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sirambo          # Buat database kosong terlebih dahulu di MySQL
DB_USERNAME=root
DB_PASSWORD=

# Konfigurasi SSO BPS (Keycloak) — Opsional untuk login lokal
BPS_SSO_BASE_URL=https://sso.bps.go.id
BPS_SSO_REALM=pegawai-bps
BPS_SSO_CLIENT_ID=nama-client-aplikasi-anda
BPS_SSO_CLIENT_SECRET=kunci-rahasia-client-sso
BPS_SSO_REDIRECT_URI=http://pdrb-app.test/login/sso/callback
```

### Langkah 4 — Import Database

1. Buat database baru bernama `sirambo` di MySQL (melalui phpMyAdmin, DBeaver, atau MySQL CLI).
2. Import file SQL dump utama yang tersedia di root proyek:

```bash
# Melalui MySQL CLI
mysql -u root -p sirambo < sirambow_sirambo.sql

# Atau gunakan phpMyAdmin / HeidiSQL untuk import file:
# sirambow_sirambo.sql  (atau sirambow_sirambo01052026.sql untuk dump terbaru)
```

3. Jalankan migrasi jika ada perubahan skema terbaru:

```bash
php artisan migrate
```

### Langkah 5 — Instal Dependensi Frontend

```bash
npm install
npm run dev    # Mode development (dengan hot-reload)
# atau
npm run build  # Mode production (untuk deployment)
```

### Langkah 6 — Jalankan Aplikasi

```bash
# Menjalankan semua layanan sekaligus (server, queue, log, vite)
composer run dev

# Atau menjalankan hanya web server
php artisan serve
```

Akses aplikasi di browser: **[http://127.0.0.1:8000](http://127.0.0.1:8000)**

> **Catatan SSO**: `BPS_SSO_REDIRECT_URI` di file `.env` harus terdaftar persis sama di console developer Keycloak BPS agar login SSO tidak menghasilkan error `invalid_redirect_uri`.

---

## 📁 Struktur Direktori

```
pdrb-app/
│
├── app/                          # Kode inti aplikasi Laravel
│   ├── Console/                  # Artisan commands kustom (scheduled tasks)
│   ├── Events/                   # Event classes (RekonP1LockUpdated, dll.)
│   ├── Exports/                  # Kelas export Excel (phpspreadsheet)
│   ├── Helpers/                  # Fungsi pembantu global
│   │   ├── format.php            # Helper format angka & tanggal
│   │   └── PdrbHelper.php        # Helper kalkulasi khusus PDRB
│   ├── Http/
│   │   ├── Controllers/          # Controller utama
│   │   │   ├── AuthController.php
│   │   │   ├── DashboardController.php
│   │   │   ├── FenomenaController.php
│   │   │   ├── HasilPdrbController.php
│   │   │   ├── LembarKerjaController.php
│   │   │   ├── RekonP1Controller.php
│   │   │   ├── RekonsiliasiController.php
│   │   │   ├── SsoController.php
│   │   │   └── ...
│   │   └── Middleware/           # Middleware kustom
│   │       ├── AksesWilayah.php  # Guard akses data per wilayah
│   │       └── RoleMiddleware.php # Guard hak akses per role
│   ├── Models/                   # Eloquent ORM Models
│   ├── Providers/                # Service Provider & SSO Driver
│   └── Socialite/                # Driver SSO Keycloak kustom (BpsProvider)
│
├── config/
│   └── lk_templates/             # Template JSON Lembar Kerja sektoral
│       ├── tanaman_pangan.json
│       ├── hortikultura.json
│       └── ...
│
├── database/
│   ├── migrations/               # File migrasi skema database
│   └── seeders/                  # Data awal (seeder)
│
├── docs/                         # Dokumentasi teknis lengkap
│   ├── 1-arsitektur-dan-alur.md
│   ├── 2-instalasi-dan-konfigurasi.md
│   ├── 3-fitur-dan-modul.md
│   ├── 4-struktur-database.md
│   └── 5-rute-dan-controller.md
│
├── public/
│   └── assets/
│       ├── js/                   # File JavaScript yang dikompilasi
│       └── css/                  # File CSS yang dikompilasi
│
├── resources/
│   ├── views/                    # Template Blade (UI halaman)
│   │   ├── auth/                 # Halaman login
│   │   ├── dashboard/            # Halaman dashboard
│   │   ├── lembar_kerja/         # Modul Lembar Kerja (dalam pengembangan)
│   │   ├── rekon_p1/             # Modul Rekonsiliasi P1
│   │   ├── rekonsiliasi/         # Modul Analisis PDRB
│   │   └── fenomena/             # Modul Fenomena Ekonomi
│   └── js/                       # Sumber JavaScript (dikompilasi Vite)
│
├── routes/
│   └── web.php                   # Definisi seluruh rute aplikasi
│
├── .env.example                  # Template konfigurasi environment
├── composer.json                 # Dependensi PHP
├── package.json                  # Dependensi Node.js/frontend
├── vite.config.js                # Konfigurasi bundler Vite
└── sirambow_sirambo.sql          # Dump database utama
```

---

## 📖 Cara Penggunaan & Alur Kerja Sistem

SIRAMBO dirancang dalam **dua tahap pengembangan** yang saling berkesinambungan. Versi produksi saat ini menjalankan **Tahap 2 secara langsung**, sementara **Tahap 1** (Lembar Kerja) masih dalam pengembangan aktif dan akan menjadi pelengkap otomatis Tahap 2 setelah selesai.

---

### 📜 Konteks: Sistem Lama (Sebelum SIRAMBO)

Sebelum SIRAMBO, seluruh proses pengumpulan data PDRB dilakukan menggunakan **Google Spreadsheet**. Setiap Kabupaten/Kota memiliki **spreadsheet tersendiri** yang memuat:

- Lembar input komoditas sektoral (Tahap 1)
- Tabel agregat PDRB Lapangan Usaha (ADHB & ADHK)
- Tabel agregat PDRB Pengeluaran (ADHB & ADHK)
- Rekonsiliasi dan catatan fenomena (Tahap 2)

Semua proses sudah ada di Google Spreadsheet tersebut — petugas Kab/Kota mengisi data komoditas langsung di dalamnya dan hasilnya otomatis muncul di tabel-tabel yang sudah disiapkan, **tanpa perlu mengirim file ke Provinsi**.

**Namun sistem ini menimbulkan masalah yang semakin besar seiring waktu:**

| Masalah | Dampak |
|---|---|
| Setiap Kab/Kota punya link Google Spreadsheet sendiri | Provinsi harus membuka 17+ link berbeda untuk memantau data |
| Setiap spreadsheet memiliki terlalu banyak sheet/tab | Sulit dinavigasi, lambat, dan rawan salah sheet |
| Tidak ada kontrol akses terpusat | Data bisa diubah siapa saja yang pegang link |
| Tidak ada audit trail | Tidak bisa melacak siapa yang mengubah angka |
| Tidak ada sinkronisasi real-time | Provinsi tidak tahu ada perubahan sampai dicek manual |

**SIRAMBO hadir untuk menggantikan semua ini dalam satu platform terpusat.**

---

### 🗺️ Gambaran Besar: Dua Tahap SIRAMBO

<div align="center">

| | 🚧 TAHAP 1 | | ✅ TAHAP 2 |
|:---:|:---:|:---:|:---:|
| **Status** | ![Dalam Pengembangan](https://img.shields.io/badge/Status-Dalam%20Pengembangan-orange?style=flat-square) | | ![Produksi](https://img.shields.io/badge/Status-Sudah%20Produksi-success?style=flat-square) |
| **Proses** | 📋 Lembar Kerja Sektoral | ➜ | 📥 Import Data PDRB |
| | 🔍 Rekonsiliasi LK | ⚙️ *agregasi otomatis* | 📝 Fenomena & Penilaian |
| | | | 🤝 Rekonsiliasi Kab/Kota & Provinsi |
| | | | 🔒 Rilis & Export Tabel Dinamis |
| **Tujuan** | Menggantikan Google Spreadsheet lama | | Rekonsiliasi & finalisasi PDRB |

</div>

---

### ✅ Alur Produksi Saat Ini (Tahap 2)

Modul **Lembar Kerja (Tahap 1)** masih dalam pengembangan dan belum diaktifkan. Petugas Kab/Kota saat ini masih mengisi data di **Google Spreadsheet lama**, lalu mengimpornya ke SIRAMBO menggunakan template yang tersedia.

#### 🔶 Fase A — Di Luar SIRAMBO *(Sistem Lama)*

| Langkah | Aktor | Aktivitas |
|:---:|:---:|---|
| ![1](https://img.shields.io/badge/1-Google%20Spreadsheet-34a853?style=flat-square&logo=google-sheets&logoColor=white) | **Kab/Kota** | Input data komoditas sektoral di Google Spreadsheet → hasil tersedia sebagai tabel **PDRB Lapangan Usaha** (ADHB & ADHK) dan **PDRB Pengeluaran** (ADHB & ADHK) |
| ![2](https://img.shields.io/badge/2-Export%20Template%20Excel-217346?style=flat-square&logo=microsoft-excel&logoColor=white) | **Kab/Kota** | Salin/export hasil tabel ke file Excel sesuai format template SIRAMBO yang tersedia |

#### 🔷 Fase B — Di Dalam SIRAMBO *(Kab/Kota)*

| Langkah | Aktivitas | Keterangan |
|:---:|---|---|
| ![3](https://img.shields.io/badge/3-Import%20PDRB%20Lapangan%20Usaha-0ea5e9?style=flat-square) | Upload template Excel PDRB **Lapangan Usaha** | ADHB & ADHK per Kab/Kota |
| ![4](https://img.shields.io/badge/4-Import%20PDRB%20Pengeluaran-0ea5e9?style=flat-square) | Upload template Excel PDRB **Pengeluaran** | ADHB & ADHK per Kab/Kota |
| ![5](https://img.shields.io/badge/5-Input%20Fenomena%20Ekonomi-8b5cf6?style=flat-square) | Catat peristiwa riil lapangan sesuai ketentuan | Kategori · Wilayah · Periode · Dampak |
| ![6](https://img.shields.io/badge/6-Penilaian%20Fenomena-8b5cf6?style=flat-square) | Beri skala dampak pada tiap fenomena | 1 = Sangat Rendah · · · 5 = Sangat Tinggi |

#### 🔷 Fase C — Di Dalam SIRAMBO *(Provinsi)*

| Langkah | Aktivitas | Keterangan |
|:---:|---|---|
| ![7](https://img.shields.io/badge/7-Import%20PDRB%20Provinsi-6366f1?style=flat-square) | Upload PDRB **Lapangan Usaha & Pengeluaran** tingkat Provinsi | ADHB & ADHK |
| ![8](https://img.shields.io/badge/8-Rekonsiliasi%20Kab/Kota-f59e0b?style=flat-square) | Penyelarasan angka Kab/Kota dengan Provinsi | Agar angka konsisten & sesuai |
| ![9](https://img.shields.io/badge/9-Rekonsiliasi%20Provinsi-f59e0b?style=flat-square) | Review & *adjustment* final seluruh wilayah | Harga ADHB & ADHK diselaraskan |
| ![10](https://img.shields.io/badge/10-Rilis%20%26%20Export-22c55e?style=flat-square) | Data dikunci → ekspor tabel dinamis ke Excel | Laporan PDRB siap dipublikasikan |

> **✅ PDRB FINAL — Siap Dipublikasikan**

---

### 🔮 Alur Target: Setelah Tahap 1 Selesai (Terintegrasi Penuh)

Setelah modul **Lembar Kerja** selesai, **import Excel (langkah 3, 4, dan 7) tidak lagi diperlukan**. Data dari Lembar Kerja otomatis teragregasi ke tabel Lapangan Usaha & Pengeluaran.

#### 🔷 Fase A — Di Dalam SIRAMBO *(Kab/Kota · Tahap 1)*

| Langkah | Aktivitas | Keterangan |
|:---:|---|---|
| ![1](https://img.shields.io/badge/1-Lembar%20Kerja%20Sektoral-0ea5e9?style=flat-square) | Input data komoditas per sektor **langsung di sistem** | Formula kalkulasi otomatis real-time (ADHB & ADHK) |
| ![2](https://img.shields.io/badge/2-Rekonsiliasi%20LK-6366f1?style=flat-square) | Provinsi pantau, verifikasi & kunci pengisian LK | Jika sudah final, data dikunci |

#### ⚙️ Agregasi Otomatis *(tanpa import Excel)*

> ![Otomatis](https://img.shields.io/badge/⚙️%20Otomatis-Data%20LK%20final%20→%20Tabel%20Lapangan%20Usaha%20%26%20Pengeluaran-22c55e?style=flat-square)
> Data dari Lembar Kerja yang sudah dikunci **otomatis masuk** ke tabel PDRB Lapangan Usaha & Pengeluaran. Import Excel manual tidak diperlukan lagi.

#### 🔷 Fase B — Di Dalam SIRAMBO *(Lanjut ke Tahap 2)*

| Langkah | Aktivitas | Keterangan |
|:---:|---|---|
| ![3](https://img.shields.io/badge/3-Input%20Fenomena%20%26%20Penilaian-8b5cf6?style=flat-square) | Sama seperti alur produksi saat ini | — |
| ![4](https://img.shields.io/badge/4-Rekonsiliasi%20Kab/Kota%20%26%20Provinsi-f59e0b?style=flat-square) | Sama seperti alur produksi saat ini | — |
| ![5](https://img.shields.io/badge/5-Rilis%20%26%20Export-22c55e?style=flat-square) | Data dikunci → export tabel dinamis | Laporan PDRB siap dipublikasikan |

> **✅ PDRB FINAL — Terintegrasi Penuh** *(Google Spreadsheet tidak diperlukan lagi)*

---

### 📋 Menu Utama Aplikasi

| Menu | Status | Akses | Fungsi |
|---|---|---|---|
| **Dashboard** | ✅ Aktif | Semua role | Ringkasan status pengisian data seluruh wilayah |
| **Import Data PDRB** | ✅ Aktif | Kab/Kota & Provinsi | Import data Lapangan Usaha & Pengeluaran via template Excel |
| **Fenomena Ekonomi** | ✅ Aktif | Kab/Kota | Input & penilaian fenomena sesuai ketentuan |
| **Rekonsiliasi Kab/Kota** | ✅ Aktif | Kab/Kota & Provinsi | Penyelarasan data antar wilayah |
| **Rekonsiliasi Provinsi (P1)** | ✅ Aktif | Provinsi | Evaluasi, *adjustment* final & penguncian data |
| **Analisis PDRB** | ✅ Aktif | Semua role | QoQ, YoY, CtC, Indeks Implisit, Struktur Dalam/Antar |
| **Tabel Dinamis & Export** | ✅ Aktif | Semua role | Lihat & ekspor data hasil rekonsiliasi final |
| **Lembar Kerja** | 🚧 *Hidden* | Kab/Kota | Dalam pengembangan — Tahap 1, menggantikan Google Spreadsheet lama |
| **Rekonsiliasi LK** | 🚧 *Hidden* | Provinsi | Dalam pengembangan — bagian dari alur Tahap 1 |
| **Manajemen User** | ✅ Aktif | Admin | Kelola akun pengguna dan hak akses wilayah |

> **ℹ️ Catatan**: Menu **Lembar Kerja** dan **Rekonsiliasi LK** sementara disembunyikan dari sidebar karena masih dalam pengembangan aktif. Setelah selesai, kedua menu ini akan **menggantikan proses Google Spreadsheet lama** sekaligus menghilangkan kebutuhan import Excel manual.

---

## 💻 Cuplikan Kode

### 1. Template JSON Lembar Kerja Sektoral

Struktur input dan rumus kalkulasi Lembar Kerja dikonfigurasi melalui file JSON dinamis:

```json
// config/lk_templates/tanaman_pangan.json (contoh ilustratif)
{
  "nama_sektor": "Tanaman Pangan",
  "kolom": [
    { "key": "luas_panen",       "label": "Luas Panen (Ha)",           "tipe": "numeric" },
    { "key": "produktivitas",    "label": "Produktivitas (Kw/Ha)",      "tipe": "numeric" },
    {
      "key": "kuantum_produksi",
      "label": "Kuantum Produksi (Ton)",
      "tipe": "formula",
      "rumus": "((luas_panen || 0) * (produktivitas || 0)) / 10"
    },
    { "key": "harga_produsen",   "label": "Harga Produsen (Rp/Ton)",   "tipe": "numeric" },
    {
      "key": "nilai_output",
      "label": "Nilai Output ADH Berlaku (Juta Rp)",
      "tipe": "formula",
      "rumus": "((kuantum_produksi || 0) * (harga_produsen || 0)) / 1000000"
    }
  ]
}
```

### 2. Kalkulasi Indikator Makroekonomi

Penghitungan indikator pertumbuhan dilakukan secara otomatis di backend:

```php
// Kalkulasi Quarter-on-Quarter (QoQ)
// app/Http/Controllers/RekonsiliasiController.php

$qoq = ($nilaiSekarang > 0 && $nilaiSebelumnya > 0)
    ? (($nilaiSekarang / $nilaiSebelumnya) - 1) * 100
    : null;

// Kalkulasi Indeks Implisit (perbandingan ADHB vs ADHK)
$indeksImplisit = $nilaiAdhk > 0
    ? ($nilaiAdhb / $nilaiAdhk) * 100
    : null;
```

### 3. Real-time Lock Broadcasting (WebSocket)

Sinkronisasi status kunci data antar pengguna menggunakan Laravel Reverb:

```php
// app/Events/RekonP1LockUpdated.php
class RekonP1LockUpdated implements ShouldBroadcast
{
    public function broadcastOn(): array
    {
        return [new Channel('rekon-p1')];
    }

    public function broadcastWith(): array
    {
        return [
            'wilayah_id' => $this->wilayahId,
            'is_locked'  => $this->isLocked,
            'locked_by'  => $this->lockedBy,
        ];
    }
}
```

```javascript
// Mendengarkan event lock di browser
window.Echo.channel('rekon-p1')
    .listen('RekonP1LockUpdated', (data) => {
        updateLockStatus(data.wilayah_id, data.is_locked);
    });
```

### 4. Middleware Pengamanan Akses Wilayah

```php
// app/Http/Middleware/AksesWilayah.php
public function handle(Request $request, Closure $next): Response
{
    $user = Auth::user();

    // Pengguna non-provinsi hanya bisa mengakses data wilayahnya sendiri
    if (!in_array($user->role, ['provinsi', 'provinsi_supervisor'])) {
        $request->merge(['scope_kabupaten' => $user->id_kabupaten]);
    }

    return $next($request);
}
```

---

## 🗺️ Roadmap Pengembangan

### ✅ Selesai & Produksi (Tahap 2)

- [x] Import Data PDRB Lapangan Usaha & Pengeluaran via template Excel (Kab/Kota & Provinsi)
- [x] Input Fenomena Ekonomi dengan ketentuan (kategori, wilayah, periode, skala dampak 1–5)
- [x] Penilaian dan ranking fenomena berdasarkan dampak
- [x] Rekonsiliasi Kab/Kota dan Provinsi untuk penyelarasan angka
- [x] Analisis makroekonomi otomatis (QoQ, YoY, CtC, Indeks Implisit, Struktur Dalam/Antar)
- [x] Tabel dinamis dan export data hasil rekonsiliasi ke Excel
- [x] Sinkronisasi real-time multi-user via Laravel Reverb (WebSocket)
- [x] Sistem penguncian & rilis data (manual & terjadwal otomatis)
- [x] Integrasi SSO BPS berbasis Keycloak
- [x] Dashboard ringkasan status pengisian seluruh wilayah

### 🚧 Dalam Pengembangan Aktif — Target Utama (Tahap 1)

- [ ] **Lembar Kerja Sektoral** *(prioritas utama — menggantikan import Excel manual)*
  - [ ] Input data komoditas per sektor langsung di sistem (ADHB & ADHK)
  - [ ] Formula kalkulasi otomatis real-time berbasis konfigurasi JSON
  - [ ] Sinkronisasi otomatis baris ADHB ↔ ADHK saat tambah komoditas baru
  - [ ] Import dari template Excel sektoral per sub-kategori
  - [ ] Rekap LK: ringkasan total Output ADH & NTB seluruh sektor
- [ ] **Rekonsiliasi LK** — Pemantauan & penguncian pengisian Lembar Kerja oleh Provinsi
- [ ] **Agregasi Otomatis** — Setelah LK final, data otomatis masuk tabel Lapangan Usaha & Pengeluaran *(menghapus kebutuhan import Excel)*

### 🔄 Rencana Berikutnya (v2.x)

- [ ] **Notifikasi Push** — Pemberitahuan otomatis saat data dikunci atau ada pembaruan *adjustment*
- [ ] **Visualisasi Grafik** — Grafik interaktif tren PDRB per kategori dan wilayah
- [ ] **Laporan PDF** — Ekspor laporan analisis PDRB lengkap ke format PDF

### 🔮 Rencana Jangka Panjang (v3.x)

- [ ] **API Publik** — Endpoint RESTful API untuk integrasi dengan sistem BPS lainnya
- [ ] **Multi-Tenancy** — Dukungan pengelolaan beberapa provinsi dalam satu instansi
- [ ] **Prediksi AI/ML** — Model prediktif pertumbuhan ekonomi berbasis data historis

---

## 📚 Dokumentasi Lengkap

Dokumentasi teknis tersedia di folder [`docs/`](docs/):

| # | Dokumen | Deskripsi |
|---|---|---|
| 1 | [Arsitektur dan Alur Sistem](docs/1-arsitektur-dan-alur.md) | Tech stack, diagram alir, dan penjelasan workflow |
| 2 | [Panduan Instalasi dan Konfigurasi](docs/2-instalasi-dan-konfigurasi.md) | Setup lokal, konfigurasi `.env`, dan SSO BPS |
| 3 | [Panduan Fitur dan Modul](docs/3-fitur-dan-modul.md) | Penjelasan mendalam setiap modul aplikasi |
| 4 | [Struktur Database](docs/4-struktur-database.md) | Kamus data lengkap seluruh tabel dan kolom |
| 5 | [Rute dan Controller](docs/5-rute-dan-controller.md) | Pemetaan endpoint URL dengan class Controller |

---

## ⚖️ Lisensi

Aplikasi ini adalah **perangkat lunak internal** yang dikembangkan untuk kebutuhan operasional **BPS Provinsi Sulawesi Tenggara**. Seluruh hak cipta dilindungi dan penggunaan di luar lingkungan BPS memerlukan izin resmi dari pihak yang berwenang.

Framework Laravel yang digunakan dilisensikan di bawah [MIT License](https://opensource.org/licenses/MIT).

---

## 🙏 Kredit

### 👨‍💻 Tim Pengembang

Aplikasi SIRAMBO dikembangkan oleh:

| Nama | Peran | Program / Organisasi |
|---|---|---|
| **Fani Dewi Astuti, S.S.T., M.E.** | Mentor & Supervisor | Tim Kerja Nerwilis BPS Provinsi Sulawesi Tenggara |
| **Statistisi Ahli Madya & Seluruh Staf** | Tim Pembina & Pendukung | Tim Kerja Nerwilis BPS Provinsi Sulawesi Tenggara |
| **Ikhsanuddin Rezki, S.Kom.** | Full-stack Developer | Magang Hub 2025 — Batch 2 & 3 |
| **Annisa Azzahra Tarimana, S.T.** | Full-stack Developer | Magang Hub 2025 — Batch 2 & 3 |

> Dikembangkan dalam program **Nerwilis** dan **Magang Hub 2025 Batch 2 & 3** di **BPS Provinsi Sulawesi Tenggara**.

---

### 🛠️ Teknologi & Pustaka yang Digunakan

Aplikasi SIRAMBO dibangun di atas teknologi dan pustaka *open-source* berikut:

| Teknologi | Keterangan | Lisensi |
|---|---|---|
| [Laravel 12](https://laravel.com) | Framework PHP backend (MVC) | MIT |
| [Laravel Reverb](https://reverb.laravel.com) | Server WebSocket untuk fitur real-time | MIT |
| [Laravel Socialite](https://socialiteproviders.com) | Driver autentikasi OAuth (SSO Keycloak) | MIT |
| [phpspreadsheet](https://phpspreadsheet.readthedocs.io) | Baca/tulis file Excel (.xlsx) | LGPL |
| [Alpine.js](https://alpinejs.dev) | Framework JavaScript ringan untuk UI reaktif | MIT |
| [Vite](https://vitejs.dev) | Build tool & bundler aset frontend modern | MIT |
| [jspreadsheet-ce](https://bossanova.uk/jspreadsheet) | Komponen spreadsheet interaktif di browser | MIT |
| [Lucide Icons](https://lucide.dev) | Library ikon SVG modern | ISC |
| [Tailwind CSS](https://tailwindcss.com) | Framework CSS utility-first | MIT |

---

<div align="center">

Dikembangkan dengan ❤️ oleh **Tim Kerja Nerwilis & Magang Hub 2025**
untuk **BPS Provinsi Sulawesi Tenggara**

*Mengubah kerja manual Excel menjadi platform kolaborasi data PDRB yang cepat, akurat, dan real-time.*

</div>
