# 3. Panduan Fitur dan Modul Aplikasi

Dokumen ini menjelaskan fungsionalitas utama aplikasi **Sirambo (PDRB App)** beserta detail logika bisnis di belakangnya.

---

## 3.1 Lembar Kerja (LK) & Template Dinamis

Modul **Lembar Kerja** digunakan oleh Kabupaten/Kota untuk menginput data mentah sektoral yang menjadi penyusun angka PDRB.

### 1. Template Berbasis JSON
Struktur input dan perhitungan pada Lembar Kerja diatur secara dinamis melalui file konfigurasi JSON di dalam `config/lk_templates/` (contoh: `tanaman_pangan.json`, `hortikultura.json`). Skema JSON ini menentukan:
* **Kolom Input**: Nama kolom, tipe data (`numeric`, `text`), dan pengelompokan (*group*).
* **Kolom Formula**: Rumus matematika berbasis JavaScript yang dieksekusi secara instan saat pengguna mengisi data di browser. 
  * *Contoh Formula*: `((luas_panen || 0) * (produktivitas || 0)) / 10` untuk menghitung kuantum produksi beras secara otomatis.

### 2. Penyimpanan Fleksibel (Dynamic JSON Meta)
* Kolom standar database (seperti kuantum, harga_produsen, nilai_output_utama, nilai_ntb, dll.) disimpan langsung ke kolom tabel `lembar_kerja_item`.
* Kolom dinamis tambahan yang bersifat sektoral (seperti `luas_panen`, `produktivitas`, `populasi_ternak`) akan diserialisasikan ke format JSON dan disimpan di dalam kolom **`data_tambahan`** di tabel `lembar_kerja_item`.

### 3. Sinkronisasi Berlaku dan Konstan
Sistem memiliki mekanisme sinkronisasi otomatis. Ketika pengguna menambahkan komoditas baru di tab ADHB (Berlaku), sistem secara otomatis membuat baris komoditas yang sama untuk tab ADHK (Konstan), memastikan data komoditas tetap konsisten di kedua metode penilaian.

---

## 3.2 Analisis PDRB

Aplikasi Sirambo menyediakan berbagai jenis analisis PDRB untuk mempermudah evaluasi pertumbuhan ekonomi regional:

1. **Q-to-Q (Quarter-on-Quarter)**
   * Mengukur pertumbuhan triwulan berjalan terhadap triwulan sebelumnya di tahun yang sama.
   * *Rumus*: `((Triwulan_N / Triwulan_N-1) - 1) * 100`
2. **Y-on-Y (Year-on-Year)**
   * Mengukur pertumbuhan triwulan berjalan terhadap triwulan yang sama di tahun sebelumnya.
   * *Rumus*: `((Triwulan_N_Tahun_T / Triwulan_N_Tahun_T-1) - 1) * 100`
3. **C-to-C (Cumulative-to-Cumulative)**
   * Mengukur pertumbuhan kumulatif triwulan berjalan terhadap triwulan berjalan di tahun sebelumnya.
4. **Indeks Implisit**
   * Menggambarkan tingkat inflasi sektoral dengan membandingkan nilai ADHB terhadap ADHK.
   * *Rumus*: `(Nilai_ADHB / Nilai_ADHK) * 100`
5. **Struktur Dalam**
   * Menganalisis kontribusi/persentase andil suatu sub-kategori terhadap total PDRB di wilayah yang sama.
6. **Struktur Antar**
   * Menganalisis kontribusi/andil nilai PDRB suatu Kabupaten/Kota terhadap total PDRB Provinsi.

---

## 3.3 Rekonsiliasi P1

Modul **Rekonsiliasi P1** merupakan panel kerja bagi pengguna tingkat Provinsi untuk meninjau, memverifikasi, dan menyelaraskan data PDRB yang dikirimkan oleh seluruh Kabupaten/Kota.

* **Fungsi Adjustment (Penyesuaian)**: Tim Provinsi dapat mengoreksi data dengan memberikan nilai penyesuaian (`adj_berlaku` dan `adj_konstan`). Nilai akhir PDRB yang digunakan adalah `Nilai Awal + Nilai Adjustment`.
* **Kalkulasi Ulang (Recalculate)**: Tombol *Recalculate* memicu penghitungan ulang seluruh metrik pertumbuhan (QoQ, YoY, CtC, Implisit) secara dinamis agar provinsi bisa melihat dampak langsung dari nilai penyesuaian yang baru dimasukkan.
* **Log Perubahan (Audit Trail)**: Setiap perubahan nilai adjustment dicatat secara mendetail di tabel log (`nilai_sub_kategori_log` dan `nilai_kategori_log`) untuk melacak histori perubahan, nilai sebelum-sesudah, serta siapa yang melakukan perubahan.
* **Penguncian Rilis (Release & Lock)**: Tim Provinsi dapat mengunci form input Kabupaten/Kota melalui status `is_locked` di tabel `kunci_jawaban` atau `rekon_p1_locks` untuk mencegah manipulasi data saat proses finalisasi berlangsung.

---

## 3.4 Pencatatan Fenomena Ekonomi

Untuk melengkapi interpretasi data angka PDRB, pengguna wajib mencatat **Fenomena Ekonomi** (peristiwa riil di lapangan yang memengaruhi naik-turunnya produksi/nilai).

* **Kategori Fenomena**: Dikaitkan dengan Kategori, Sub-Kategori, Periode, dan Wilayah tertentu.
* **Penilaian Dampak**: Dilengkapi dengan skala rating dampak (1 = Sangat Rendah, 2 = Rendah, 3 = Sedang, 4 = Tinggi, 5 = Sangat Tinggi).
* **Scheduled Lock (Penguncian Otomatis)**: Administrator dapat menetapkan batas waktu (deadline) pengisian fenomena. Jika batas waktu terlampaui, form input fenomena untuk wilayah tersebut akan terkunci otomatis oleh sistem.
* **Ranking & Export**: Dilengkapi dengan fitur pemeringkatan fenomena dengan dampak terbesar dan fitur export seluruh catatan fenomena ke file Excel untuk kebutuhan laporan.
