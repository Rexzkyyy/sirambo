# 4. Struktur Database

Dokumen ini mendokumentasikan skema database relasional yang digunakan oleh aplikasi **Sirambo (PDRB App)**.

---

## 4.1 Diagram Hubungan Entitas (ERD Sederhana)

```
[Provinsi/Kabupaten] <--- [Wilayah] <--- [User]
                               |
                               +<--- [Nilai PDRB (Kategori / Sub-Kategori)]
                               |
                               +<--- [Lembar Kerja] <--- [Lembar Kerja Item] <--- [Komoditas]
                               |
                               +<--- [Kunci Jawaban] / [Pdrb Lock]
```

---

## 4.2 Tabel Master Wilayah dan Pengguna

### 1. `provinsi`
Menyimpan data wilayah tingkat provinsi.
* `id_provinsi` (Primary Key, bigint, Auto Increment)
* `nama_provinsi` (varchar)

### 2. `kabupaten`
Menyimpan data wilayah tingkat kabupaten/kota.
* `id_kabupaten` (Primary Key, bigint, Auto Increment)
* `nama_kabupaten` (varchar)
* `tipe` (enum: `'kabupaten'`, `'kota'`)
* `id_provinsi` (Foreign Key -> `provinsi.id_provinsi`)

### 3. `wilayah`
Tabel jembatan/abstraksi untuk merepresentasikan wilayah kerja pengguna.
* `id_wilayah` (Primary Key, bigint, Auto Increment)
* `tipe` (enum: `'provinsi'`, `'kabupaten'`, `'kota'`)
* `id_provinsi` (Foreign Key -> `provinsi.id_provinsi`, Nullable)
* `id_kabupaten` (Foreign Key -> `kabupaten.id_kabupaten`, Nullable)

### 4. `users`
Menyimpan data kredensial dan hak akses pengguna.
* `id` (Primary Key, bigint, Auto Increment)
* `name` (varchar)
* `email` (varchar, Unique)
* `password` (varchar)
* `role` (enum: `'provinsi'`, `'provinsi_supervisor'`, `'kabupaten'`, `'kota'`)
* `id_wilayah` (Foreign Key -> `wilayah.id_wilayah`)
* `nip` (varchar, Nullable)
* `sso_id` (varchar, Nullable, untuk integrasi Keycloak SSO BPS)
* `created_at` / `updated_at` (timestamps)

---

## 4.3 Tabel Klasifikasi PDRB

### 1. `kategori`
Mengelompokkan lapangan usaha atau jenis pengeluaran (misal: A untuk Pertanian, B untuk Pertambangan).
* `id_kategori` (Primary Key, int, Auto Increment)
* `kode_kategori` (varchar, kode huruf seperti 'A', 'B', 'C')
* `nama_kategori` (varchar, deskripsi kategori)
* `pendekatan` (varchar: `'lapangan_usaha'` atau `'pengeluaran'`)

### 2. `sub_kategori`
Pecahan/detail dari kategori (misal: A1a untuk Tanaman Pangan di bawah Kategori A).
* `id_sub_kategori` (Primary Key, int, Auto Increment)
* `kategori_id` (Foreign Key -> `kategori.id_kategori`)
* `kode_sub_kategori` (varchar)
* `nama_sub_kategori` (varchar)

### 3. `tahun` & `periode`
Menyimpan master waktu (tahun dan triwulan) untuk pencatatan PDRB.
* **`tahun`**: `id_tahun` (PK, int), `tahun` (int)
* **`periode`**: `id_periode` (PK, int), `nama_periode` (varchar, contoh: 'Triwulan I', 'Triwulan II', 'TOTAL')

---

## 4.4 Tabel Nilai Hasil Akhir PDRB

Tabel-tabel ini menyimpan nilai total rupiah PDRB hasil perhitungan dari Lembar Kerja atau input langsung.

### 1. `nilai_sub_kategori`
* `id_nilai_sub_kategori` (Primary Key, int, Auto Increment)
* `id_sub_kategori` (Foreign Key -> `sub_kategori.id_sub_kategori`)
* `id_wilayah` (Foreign Key -> `wilayah.id_wilayah`)
* `id_periode` (Foreign Key -> `periode.id_periode`)
* `id_tahun` (Foreign Key -> `tahun.id_tahun`)
* `nilai` (decimal(18,2), nilai PDRB dalam jutaan rupiah)
* `tipe_pdrb` (varchar: `'berlaku'` (ADHB) atau `'konstan'` (ADHK))
* `tahap_data` (varchar: `'awal'` atau `'rekonsiliasi'`)

### 2. `nilai_kategori`
Strukturnya mirip dengan `nilai_sub_kategori`, tetapi menyimpan aggregasi langsung di tingkat Kategori (huruf).

---

## 4.5 Tabel Transaksi Lembar Kerja (Worksheet)

### 1. `lembar_kerja`
Menyimpan data header lembar kerja wilayah per periode.
* `id` (Primary Key, bigint, Auto Increment)
* `wilayah_id` (Foreign Key -> `wilayah.id_wilayah`)
* `id_tahun` (Foreign Key -> `tahun.id_tahun`)
* `id_periode` (Foreign Key -> `periode.id_periode`)
* `jenis` (varchar: `'lapangan_usaha'` atau `'pengeluaran'`)
* `id_kategori` (Foreign Key -> `kategori.id_kategori`)
* `id_sub_kategori` (Foreign Key -> `sub_kategori.id_sub_kategori`)
* `created_by` / `updated_by` (Foreign Key -> `users.id`)

### 2. `lembar_kerja_item`
Menyimpan data detail baris komoditas di dalam Lembar Kerja.
* `id` (Primary Key, bigint, Auto Increment)
* `lembar_kerja_id` (Foreign Key -> `lembar_kerja.id` ON DELETE CASCADE)
* `komoditas_id` (Foreign Key -> `komoditas.id`)
* `tipe_pdrb` (enum: `'berlaku'`, `'konstan'`)
* `kuantum` (double)
* `harga_produsen` (double)
* `nilai_output_utama` (double)
* `rasio_output_ikut` (double)
* `nilai_output_ikut` (double)
* `biaya_perawatan` (double)
* `biaya_sebelumnya` (double)
* `wip` (double, Work in Progress)
* `output_adh` (double, Output Atas Dasar Harga)
* `konsumsi_antara` (double)
* `nilai_ntb` (double, Nilai Tambah Bruto)
* `rasio_konsumsi_antara` (double)
* `deflator` (double)
* `wip_berlaku` (double)
* **`data_tambahan`** (longtext/JSON, menyimpan nilai dinamis seperti `luas_panen` atau `produktivitas` untuk format sektoral dinamis)

### 3. `komoditas`
Menyimpan daftar komoditas yang dihitung per wilayah.
* `id` (Primary Key, bigint, Auto Increment)
* `nama` (varchar)
* `id_wilayah` (Foreign Key -> `wilayah.id_wilayah`)
* `satuan` (varchar, contoh: 'Ton', 'Ekor')
* `wujud` (varchar, contoh: 'Kering Giling', 'Basah')
* `aktif` (tinyint)

---

## 4.6 Tabel Kontrol Kunci dan Log

### 1. `kunci_jawaban` / `pdrb_locks`
Menyimpan status penguncian data wilayah (mencegah Kabupaten/Kota mengedit data).
* `id_kunci` (Primary Key, int, Auto Increment)
* `id_wilayah` (Foreign Key -> `wilayah.id_wilayah`)
* `tahun` (int)
* `pendekatan` (varchar: `'lapangan_usaha'` atau `'pengeluaran'`)
* `mode` (varchar: `'tahunan'` atau `'triwulanan'`)
* `is_locked` (boolean)
* `locked_by` (Foreign Key -> `users.id`)
* `locked_at` (datetime)
* `lock_deadline` (datetime, batas waktu penguncian otomatis)

### 2. `nilai_sub_kategori_log` & `nilai_kategori_log`
Log audit trail untuk mencatat riwayat perubahan adjustment yang diinput oleh Provinsi.
* `id` (Primary Key, bigint, Auto Increment)
* `id_nilai_sub_kategori` (Foreign Key)
* `nilai_lama` (decimal(18,2))
* `nilai_baru` (decimal(18,2))
* `updated_by` (Foreign Key -> `users.id`)
* `created_at` (timestamp)
