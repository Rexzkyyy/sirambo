# 5. Rute dan Controller Aplikasi

Dokumen ini mendokumentasikan seluruh titik masuk (endpoint) aplikasi melalui berkas `routes/web.php` dan implementasi logic di Controller terkait.

---

## 5.1 Kelompok Autentikasi dan SSO

Mengatur proses autentikasi sistem, baik login lokal maupun integrasi SSO BPS Keycloak.

| Metode | URL Route | Controller Action | Middleware | Deskripsi |
|---|---|---|---|---|
| **GET** | `/login` | `AuthController@showLogin` | `guest` | Menampilkan halaman login lokal. |
| **POST** | `/login` | `AuthController@login` | `guest` | Memproses verifikasi kredensial login lokal. |
| **POST** | `/logout` | `AuthController@logout` | `auth` | Mengakhiri sesi pengguna. |
| **GET** | `/login/sso` | `SsoController@redirect` | - | Mengarahkan pengguna ke server Keycloak SSO BPS. |
| **GET** | `/login/sso/callback` | `SsoController@callback` | - | Callback penerima token SSO dan mencocokkan data NIP/Email dengan database `users`. |

---

## 5.2 Kelompok Lembar Kerja (Worksheet)

Digunakan oleh pengguna tingkat Kabupaten/Kota untuk menginput data mentah komoditas.

| Metode | URL Route | Controller Action | Middleware | Deskripsi |
|---|---|---|---|---|
| **GET** | `/lembar-kerja` | `LembarKerjaController@index` | `auth` | Halaman daftar kategori Lembar Kerja. |
| **GET** | `/lembar-kerja/detail/{id_sub_kategori}` | `LembarKerjaController@detail` | `auth` | Halaman form input data komoditas sektoral dinamis. |
| **POST** | `/lembar-kerja/detail/{id_sub_kategori}` | `LembarKerjaController@saveDetail` | `auth` | Menyimpan/memperbarui data item Lembar Kerja. |
| **DELETE** | `/lembar-kerja/detail/{id}/reset` | `LembarKerjaController@resetDetail` | `auth` | Mereset nilai input form Lembar Kerja. |
| **POST** | `/lembar-kerja/detail/{id}/komoditas` | `LembarKerjaController@saveKomoditas` | `auth` | Menambah komoditas kustom baru untuk wilayah tertentu. |
| **GET** | `/lembar-kerja/rekap` | `RekapLkHasilController@index` | `auth` | Melihat rekapitulasi total output ADH dan NTB dari Lembar Kerja. |

---

## 5.3 Kelompok Rekonsiliasi Lembar Kerja (Rekon LK)

Digunakan untuk koordinasi Lembar Kerja tingkat Provinsi dengan data Kabupaten/Kota.

| Metode | URL Route | Controller Action | Middleware | Deskripsi |
|---|---|---|---|---|
| **GET** | `/rekon-lk` | `RekonLkController@index` | `auth` | Memantau isian Lembar Kerja seluruh kabupaten/kota per sektor. |
| **POST** | `/rekon-lk/update-adjustment` | `RekonLkController@updateAdjustment` | `auth` | Memasukkan nilai koreksi (adjustment) untuk lembar kerja daerah. |
| **POST** | `/rekon-lk/lock` | `RekonLkController@toggleLock` | `auth` | Mengunci atau membuka status edit Lembar Kerja daerah. |
| **POST** | `/rekon-lk/release` | `RekonLkController@releaseData` | `auth` | Provinsi menyetujui (approve) lembar kerja daerah untuk dikunci. |

---

## 5.4 Kelompok Rekonsiliasi P1 (Rekon P1)

Digunakan oleh Provinsi untuk mengevaluasi indikator makro PDRB.

| Metode | URL Route | Controller Action | Middleware | Deskripsi |
|---|---|---|---|---|
| **GET** | `/rekonsiliasi-p1` | `RekonP1Controller@index` | `auth` | Menampilkan tabel master evaluasi data PDRB seluruh wilayah. |
| **GET** | `/rekonsiliasi-p1/detail/{id}` | `RekonP1Controller@detail` | `auth` | Form penyesuaian (adjustment) nilai sub-kategori/kategori langsung. |
| **POST** | `/rekonsiliasi-p1/save-adjustment` | `RekonP1Controller@saveAdjustment` | `auth` | Menyimpan perubahan nilai adjustment Rekon P1. |
| **POST** | `/rekonsiliasi-p1/recalculate` | `RekonP1Controller@recalculate` | `auth` | Memicu kalkulasi ulang metrik pertumbuhan (QoQ, YoY) secara real-time. |
| **GET** | `/rekonsiliasi-p1/poll-updates` | `RekonP1Controller@pollUpdates` | `auth` | AJAX polling untuk sinkronisasi nilai PDRB multi-user secara instan. |
| **POST** | `/rekonsiliasi-p1/lock` | `RekonP1Controller@toggleLock` | `auth` | Mengunci form Rekon P1 agar tidak bisa diubah. |

---

## 5.5 Kelompok Analisis PDRB

Halaman-halaman visualisasi data dan laporan PDRB regional.

| Metode | URL Route | Controller Action | Middleware | Deskripsi |
|---|---|---|---|---|
| **GET** | `/rekonsiliasi/qtoq` | `RekonsiliasiController@qtoq` | `auth` | Laporan pertumbuhan Quarter-on-Quarter. |
| **GET** | `/rekonsiliasi/yony` | `RekonsiliasiController@yony` | `auth` | Laporan pertumbuhan Year-on-Year. |
| **GET** | `/rekonsiliasi/ctoc` | `RekonsiliasiController@ctoc` | `auth` | Laporan pertumbuhan Cumulative-to-Cumulative. |
| **GET** | `/rekonsiliasi/indeksImplisit` | `RekonsiliasiController@indeksImplisit` | `auth` | Indeks Harga Implisit PDRB (Indikator Inflasi). |
| **GET** | `/rekonsiliasi/lajuImplisit` | `RekonsiliasiController@lajuImplisit` | `auth` | Kecepatan laju indeks implisit. |
| **GET** | `/rekonsiliasi/stukturDalam` | `RekonsiliasiController@stukturDalam` | `auth` | Analisis kontribusi sektoral di dalam wilayah. |
| **GET** | `/rekonsiliasi/stukturAntar` | `RekonsiliasiController@stukturAntar` | `auth` | Analisis andil kontribusi antar wilayah. |
| **GET** | `/rekonsiliasi/cek-selisih` | `RekonsiliasiController@cekSelisih` | `auth` | Mengecek perbedaan antara jumlah nilai kab/kota dengan provinsi. |

---

## 5.6 Kelompok Fenomena Ekonomi

Digunakan untuk pencatatan narasi di balik naik/turunnya angka PDRB.

| Metode | URL Route | Controller Action | Middleware | Deskripsi |
|---|---|---|---|---|
| **GET** | `/fenomena` | `FenomenaController@index` | `auth` | Daftar catatan fenomena ekonomi yang diinput wilayah. |
| **POST** | `/fenomena` | `FenomenaController@store` | `auth` | Membuat catatan fenomena ekonomi baru. |
| **PUT** | `/fenomena/{id}` | `FenomenaController@update` | `auth` | Memperbarui detail catatan fenomena. |
| **DELETE** | `/fenomena/{id}` | `FenomenaController@destroy` | `auth` | Menghapus catatan fenomena. |
| **GET** | `/fenomena/ranking` | `FenomenaController@ranking` | `auth` | Melihat peringkat fenomena berdasarkan besarnya rating dampak. |
| **GET** | `/fenomena/export` | `FenomenaController@export` | `auth` | Mengekspor daftar catatan fenomena ke berkas Excel. |
| **POST** | `/fenomena/toggle-lock` | `FenomenaController@toggleLock` | `auth` | Mengunci menu pengisian fenomena. |

---

## 5.7 Penjelasan Middleware Kustom

### 1. Middleware `akses.wilayah`
* **Implementasi**: `App\Http\Middleware\AksesWilayah`
* **Logika**: Memeriksa apakah pengguna memiliki role selain `provinsi`. Jika iya, middleware akan menambahkan properti `scope_kabupaten` ke request PHP yang berisi ID kabupaten milik user tersebut. Hal ini memastikan user Kabupaten/Kota tidak dapat mengintip atau memodifikasi data milik kabupaten lain dengan cara memanipulasi parameter URL.

### 2. Middleware `role`
* **Implementasi**: `App\Http\Middleware\RoleMiddleware`
* **Logika**: Menerima parameter nama role (contoh: `role:provinsi,provinsi_supervisor`). Jika role dari user yang sedang login tidak terdaftar pada parameter tersebut, sistem otomatis melemparkan response HTTP `403 Forbidden` ("Anda tidak memiliki akses ke halaman ini").
