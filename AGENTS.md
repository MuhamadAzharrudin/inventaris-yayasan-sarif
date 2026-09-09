# AGENTS.md — AI Agent Guidance & Codebase Rules

> **Project Name**: Sistem Informasi Manajemen Aset Inventaris Sarana & Prasarana Terintegrasi QR Code  
> **Client / User**: Yayasan Pendidikan Ponpes Tahfizul Qur'an Husnul Abror (Unit MI, SMP/MTs, SMK)  
> **Academic Reference**: Skripsi *Rancang Bangun Sistem Manajemen Aset Inventaris Sarana Prasarana Berbasis Web Dengan Integrasi QR Code Pada Yayasan Pendidikan Ponpes Tahfizul Qur'an Husnul Abror* oleh M. Sarif Rahmatullah (NIM: 220602073), Program Studi Informatika, Universitas Hamzanwadi (2026).

---

## 🏛️ 1. Project Context & Domain Overview

Sistem ini dikembangkan untuk mengomputerisasi tata kelola sarana dan prasarana di lingkungan Yayasan Pendidikan Ponpes Tahfizul Qur'an Husnul Abror yang menaungi 3 unit pendidikan (Madrasah Ibtidaiyah / MI, Sekolah Menengah Pertama / SMP / MTs, dan Sekolah Menengah Kejuruan / SMK). 

### Key Technical Architecture
- **Framework & Runtime**: PHP 8.2.0, Laravel 12.58.0.
- **Database Engine**: MySQL / SQLite.
- **Frontend Stack**: Blade Templates, Vanilla CSS Design System, JavaScript (ES6), Lucide Icons CDN.
- **Hardware Integration**: HTML5 Web Camera API (`html5-qrcode`) untuk live camera scanning dari peramban HP & Laptop.
- **Document Generation**: Printable HTML-to-PDF views & Streaming CSV/Excel Exports.

---

## 👥 2. User Roles & Data Scoping Rules

Sistem menerapkan **Multi-Unit Data Isolation** dengan 2 tingkatan role utama:

1. **Super Admin (Tingkat Yayasan)** — middleware `super_admin`:
   - `role = 'super_admin'`, `unit_id = null`.
   - **Unit Sekolah** (`/unit-sekolah`, `/unit-sekolah/{unit}`): meninjau detail inventaris tiap unit
     dari konteks Yayasan (read-only) — **tidak** masuk ke dashboard sekolah. Rute `/dashboard/{mi,smp,smk}`
     otomatis diarahkan ke halaman detail unit tersebut.
   - **Laporan & Audit Global** (`/laporan`): memverifikasi laporan seluruh unit + filter
     (unit, status, jenis, hasil verifikasi, kata kunci, rentang tanggal) + unduh rekap CSV.
     **Hanya role ini** yang boleh memanggil `PUT /laporan/{id}/status`.
   - **Pengaturan Akun & User** (`/admin/users`): tambah, **edit detail akun & sandi**, dan hapus akun.
   - Master data kategori / merek / lokasi **tidak lagi tersedia** untuk Yayasan (dikelola unit sekolah);
     akses ke rute tersebut ditolak oleh middleware `admin_unit`.

2. **Admin Unit (Tingkat Sekolah: MI, MTS, SMK)** — middleware `admin_unit`:
   - `role = 'admin_unit'`, `unit_id = 1 (MI), 2 (MTS), 3 (SMK)`.
   - Data terisolasi berdasarkan `unit_id`; percobaan mengakses data unit lain menghasilkan `403`.
   - Pendataan barang per ruangan, unggah foto (galeri/kamera), **Scan QR Code** (`/scan-qr`),
     **Cetak Label QR** (`/cetak-label-qr`), master data unit, serta **Barang Masuk / Keluar**.
   - Membuat laporan (kerusakan, penggantian barang rusak, peminjaman) dan **hanya memantau**
     statusnya di `Cek Pelaporan` & `Pelaporan Selesai` — verifikasi adalah wewenang Yayasan.

---

## 📐 3. Core Database Models & Contracts

AI Agent **wajib** mematuhi struktur entitas dan relasi berikut:

- `Unit` (`id`, `nama`, `kode`, `kepala_unit`): Menyimpan data unit sekolah (MI, SMP, SMK).
- `User` (`id`, `unit_id`, `name`, `email`, `password`, `role`): Pengguna sistem (super_admin / admin_unit).
- `Category` (`id`, `unit_id`, `nama`, `kode`, `deskripsi`): Kategori barang sarpras (e.g. Meubeler, Elektronik, Lab, Bengkel).
- `Merek` (`id`, `unit_id`, `nama`, `kode`, `deskripsi`): Merek/brand aset.
- `Location` (`id`, `unit_id`, `nama`, `slug`, `tipe`): Lokasi ruangan/kelas tempat fisik barang diletakkan.
- `Asset` (`id`, `unit_id`, `location_id`, `category_id`, `merek_id`, `kode_barang`, `nama_barang`, `total_qty`, `kondisi_baik`, `kondisi_rusak_ringan`, `kondisi_rusak_berat`, `foto_barang`, `qr_code_path`, `spesifikasi`, `nilai_estimasi`):
  - **Aturan Kondisi**: `total_qty` harus selalu sama dengan `kondisi_baik + kondisi_rusak_ringan + kondisi_rusak_berat`.
- `AssetMutation` (`id`, `asset_id`, `unit_id`, `location_id`, `tipe ['masuk','keluar']`, `jenis`, `kondisi_sumber`, `qty`, `peminjam`, `kontak_peminjam`, `tanggal_keluar`, `tanggal_kembali_rencana`, `tanggal_kembali_aktual`, `status_pinjam`, `keterangan`, `user_id`, `report_id`): Log mutasi aset.
  - `jenis` masuk: `pengadaan | pengembalian | penggantian_baru | lainnya`.
  - `jenis` keluar: `penggantian | peminjaman | penghapusan | lainnya`.
  - `kondisi_sumber`: `baik | rusak_ringan | rusak_berat` (kondisi unit yang bergerak).
  - `status_pinjam`: `null | dipinjam | dikembalikan` (khusus `jenis = peminjaman`).
- `Report` (`id`, `unit_id`, `asset_id`, `location_id`, `user_id`, `jenis`, `judul`, `deskripsi`, `qty`, `status`, `verifikasi`, `verified_by`, `verified_at`, `tanggapan`): Pelaporan & verifikasi.
  - `jenis`: `kerusakan | penggantian | peminjaman`.
  - `status`: `pending | proses | selesai`; `verifikasi`: `null | disetujui | ditolak`.
- `Notification` (tabel `app_notifications`: `id`, `user_id`, `unit_id`, `report_id`, `asset_id`, `tipe`, `judul`, `pesan`, `url`, `icon`, `read_at`): Notifikasi in-app navbar.

### Aturan Konsistensi Stok (wajib dipatuhi)
- `total_qty = kondisi_baik + kondisi_rusak_ringan + kondisi_rusak_berat` — gunakan `Asset::syncTotalQty()`.
- **Penggantian barang rusak** mengurangi `kondisi_rusak_ringan`/`kondisi_rusak_berat` (dan `total_qty`)
  sebanyak qty yang dikeluarkan; qty tidak boleh melebihi stok kondisi tersebut.
- **Peminjaman** tidak mengubah kolom kondisi. Unit yang boleh dipinjamkan dihitung dengan
  `Asset::qtySiapPinjam()` = `kondisi_baik - Asset::qtyDipinjam()`.
- **Pengembalian** dapat memindahkan qty dari `kondisi_baik` ke kondisi rusak bila barang kembali rusak;
  `total_qty` tetap sama.
- `kondisi_baik` tidak boleh lebih kecil dari `Asset::qtyDipinjam()`.

---

## 🤖 4. Guidelines for AI Agents Working on This Repository

### 1. Code Style & Conventions
- **Controllers**: Tempatkan di `app/Http/Controllers/`. Gunakan Eloquent ORM secara eksplisit dengan perlakuan relasi `with(['category', 'merek', 'location'])`.
- **Isolasi Unit**: gunakan trait `App\Http\Controllers\Concerns\HandlesUnitScope`
  (`scopeUnit()`, `currentUnitId()`, `guardUnit()`) alih-alih menulis filter `unit_id` manual.
- **Views**: Gunakan layout master `@extends('layouts.admin')` beserta kelas design system yang sudah ada
  (`.page-head`, `.card`, `.grid-stats`, `.stat`, `.table-wrap` + `table.table`, `.btn`, `.badge`,
  `.field/.input/.select/.textarea`, `.modal`, `.empty-state`). Jangan membuat layout/sidebar baru.
- **Judul halaman**: definisikan `@section('page-title', ...)` (opsional `@section('page-icon', ...)`);
  bila tidak diisi, judul navbar & `<title>` diambil otomatis dari `App\Helpers\PageTitleHelper`.
- **Sidebar**: menu ruangan dibangun dari master data `Location` milik unit (`components/sidebar-unit`),
  sedangkan Yayasan memakai `components/sidebar-yayasan`. Jangan menuliskan daftar ruangan secara hardcode.
- **Tabel**: selalu bungkus dengan `<div class="table-wrap">` agar tetap responsif di layar kecil.
- **Notifikasi**: buat lewat `App\Services\NotificationService` (jangan `Notification::create()` langsung
  di controller) supaya penerima & format pesan konsisten.
- **Form Submissions**: Selalu sertakan `@csrf`, dan gunakan `enctype="multipart/form-data"` pada form yang mengunggah berkas foto (`foto_barang`).
- **Validasi**: pesan berbahasa Indonesia berada di `lang/id/validation.php`; tambahkan nama atribut baru di sana.

### 2. File Upload Handling
- Selalu periksa unggahan foto barang menggunakan `$request->hasFile('foto_barang')`.
- Simpan berkas yang diunggah ke `public/uploads/assets/` dengan nama unik (`time() . '_' . Str::random(8) . '.' . $extension`).
- Tetapkan `foto_barang` dengan URL publik yang valid menggunakan helper `asset('uploads/assets/' . $filename)`.

### 3. QR Code & Camera Scanning Standards
- Gunakan `App\Helpers\QrCodeHelper::generateSvg($kodeBarang)` untuk menghasilkan tautan QR Code.
- Pada halaman pelaporan, gunakan library `html5-qrcode` CDN untuk mengakses kamera HP/Laptop pengguna dan memicu autofill otomatis saat QR Code fisik terdeteksi.

### 4. Verification Directives
- Setiap kali mengubah skema atau controller, jalankan perintah verifikasi:
  ```bash
  php artisan migrate:fresh --seed
  php artisan route:list
  ```
- Pastikan tidak ada rute yang *broken* atau rujukan variabel Blade yang *undefined*.
- Uji minimal satu alur per role: Admin Unit (pendataan barang -> barang keluar -> laporan) dan
  Admin Yayasan (detail unit -> verifikasi laporan -> kelola akun).
- Periksa kembali kontrak stok setelah mengubah mutasi aset:
  `total_qty` wajib sama dengan jumlah ketiga kolom kondisi.

---

## 🔑 5. Test Credentials

- **Super Admin (Yayasan)**: `adminyayasan@husnulabror.sch.id` | Password: `password`
- **Admin MI**: `admin.mi@husnulabror.sch.id` | Password: `password`
- **Admin MTS**: `admin.smp@husnulabror.sch.id` | Password: `password`
- **Admin SMK**: `admin.smk@husnulabror.sch.id` | Password: `password`
