# 2. Panduan Instalasi dan Konfigurasi

Dokumen ini berisi panduan langkah demi langkah untuk melakukan instalasi, setup database, dan konfigurasi lingkungan (*environment*) aplikasi **Sirambo**.

---

## 2.1 Kebutuhan Sistem (System Requirements)

Sebelum memulai instalasi, pastikan server atau mesin lokal Anda telah memenuhi prasyarat berikut:

* **PHP**: Versi `>= 8.2` (dengan ekstensi: `pdo_mysql`, `openssl`, `mbstring`, `xml`, `curl`, `zip`, `gd`)
* **Database**: MySQL `>= 8.0` atau MariaDB `>= 10.4`
* **Package Manager**:
  * [Composer](https://getcomposer.org/) (untuk dependensi PHP/Laravel)
  * [Node.js](https://nodejs.org/) & NPM (untuk kompilasi aset Frontend/Vite)
* **Web Server**: Laragon (Sangat direkomendasikan untuk Windows), XAMPP, atau Apache/Nginx

---

## 2.2 Langkah Instalasi (Installation Steps)

Ikuti langkah-langkah di bawah ini untuk memasang aplikasi di lingkungan lokal:

### Langkah 1: Clone Repositori
Clone proyek ke dalam folder web server Anda (misal `C:\laragon\www\pdrb-app`):
```bash
git clone <url-repository> pdrb-app
cd pdrb-app
```

### Langkah 2: Instal Dependensi PHP
Jalankan Composer untuk mengunduh library PHP yang dideklarasikan di `composer.json`:
```bash
composer install
```

### Langkah 3: Instal Dependensi Frontend & Jalankan Vite
Unduh dependensi npm dan jalankan server pengembangan Vite untuk mendeteksi perubahan asset CSS/JS secara dinamis:
```bash
npm install
npm run dev
```

### Langkah 4: Setup Environment File (`.env`)
Salin file `.env.example` menjadi `.env`:
```bash
cp .env.example .env
```
Buka file `.env` yang baru dibuat dan sesuaikan konfigurasi database Anda:
```env
APP_NAME=Sirambo
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://pdrb-app.test  # Ubah sesuai domain Laragon / virtualhost Anda

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sirambo           # Buat database kosong terlebih dahulu di MySQL
DB_USERNAME=root
DB_PASSWORD=
```
Generate kunci enkripsi aplikasi Laravel:
```bash
php artisan key:generate
```

---

## 2.3 Setup Database (Import SQL)

Karena basis data utama diimpor dari dump SQL bawaan:

1. Buat database baru bernama `sirambo` menggunakan tools database Anda (misal: phpMyAdmin, DBeaver, HeidiSQL, atau MySQL CLI).
2. Impor berkas SQL dump utama yang berada di root proyek atau folder database:
   * File dump utama: `sirambow_sirambo.sql` (atau file dump terbaru seperti `sirambow_sirambo01052026.sql`).
3. Jalankan migrasi tambahan jika ada perubahan struktur baru yang belum masuk ke dump:
   ```bash
   php artisan migrate
   ```

---

## 2.4 Konfigurasi SSO BPS (Keycloak / Socialite)

Aplikasi Sirambo menggunakan SSO BPS berbasis Keycloak (OpenID Connect). Tambahkan konfigurasi berikut pada file `.env` Anda agar tombol login SSO berfungsi:

```env
# Konfigurasi Keycloak SSO BPS
BPS_SSO_BASE_URL=https://sso.bps.go.id  # Ganti dengan URL Sandbox/Production SSO BPS
BPS_SSO_REALM=pegawai-bps
BPS_SSO_CLIENT_ID=nama-client-aplikasi-anda
BPS_SSO_CLIENT_SECRET=kunci-rahasia-client-sso
BPS_SSO_REDIRECT_URI=http://pdrb-app.test/login/sso/callback
```

* *Catatan*: Redirect URI di console developer Keycloak harus didaftarkan persis sama dengan nilai `BPS_SSO_REDIRECT_URI` di atas agar tidak memicu error `invalid redirect_uri`.

---

## 2.5 Menjalankan Aplikasi

Jalankan perintah berikut untuk mengaktifkan web server bawaan Laravel:
```bash
php artisan serve
```
Akses aplikasi melalui browser di [http://127.0.0.1:8000](http://127.0.0.1:8000) atau sesuai domain lokal yang Anda gunakan di web server.
