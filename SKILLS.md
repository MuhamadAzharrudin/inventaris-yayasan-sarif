# SKILLS.md — Developer & AI Agent Skill Reference

> **Project Domain**: Web Inventory & QR Code Management System (Laravel 12 / PHP 8.2)  
> **Client / User**: Yayasan Pendidikan Ponpes Tahfizul Qur'an Husnul Abror  
> **Skripsi Reference**: *Rancang Bangun Sistem Manajemen Aset Inventaris Sarana Prasarana Berbasis Web Dengan Integrasi QR Code* (M. Sarif Rahmatullah, NIM: 220602073)

---

## 🛠️ 1. Asset Management & Inventory CRUD Skills

### Skill: Registering New Asset with File Upload & Auto QR
- **Controller Action**: `AsetController@store`
- **Route**: `POST /aset` (middleware `admin_unit`)
- **Workflow**:
  1. Terima `nama_barang`, `location_id`, `category_id`, `merek_id`, `kondisi_baik`,
     `kondisi_rusak_ringan`, `kondisi_rusak_berat`, `spesifikasi`, `nilai_estimasi`, dan `kode_barang` (opsional).
  2. `total_qty` **dihitung dari rincian kondisi** (bukan diinput bebas). Bila `total_qty` dikirim
     dan tidak sama dengan jumlah kondisi, permintaan ditolak dengan pesan yang menjelaskan selisihnya.
  3. Unggah `foto_barang` (`image|mimes:jpg,jpeg,png,webp|max:5120`) ke `public/uploads/assets/`.
  4. Buat `kode_barang` otomatis bila kosong: `{UNIT}-{RUANG}-{ACAK4}`, dijamin unik.
  5. Buat tautan QR via `QrCodeHelper::generateSvg($kodeBarang)`.
  6. Catat `AssetMutation` `tipe = masuk`, `jenis = pengadaan`, lalu kirim notifikasi ke Admin Yayasan.

### Skill: Updating Asset Condition Breakdown
- **Controller Action**: `RuanganController@updateKondisi`
- **Route**: `PUT /ruangan/aset/{id}/kondisi`
- **Aturan validasi**:
  1. Ketiga kolom kondisi wajib angka `>= 0`, totalnya minimal 1 unit.
  2. `kondisi_baik` tidak boleh kurang dari `Asset::qtyDipinjam()` (unit yang sedang dipinjamkan).
  3. `total_qty` selalu dihitung ulang via `Asset::syncTotalQty()`.

### Skill: Editing / Deleting Asset
- **Actions**: `AsetController@edit|update|destroy` — `GET|PUT|DELETE /aset/{id}`.
- Barang dengan peminjaman aktif **tidak dapat dihapus** dan `kondisi_baik`-nya tidak dapat
  diturunkan di bawah jumlah unit yang sedang dipinjam.

---

## 📷 2. QR Code & Live Camera Scanner Skills

### Skill: Scan QR Code (menu Master Data)
- **View**: `resources/views/aset/scan.blade.php` · **Route**: `GET /scan-qr`
- **Lookup API**: `GET /scan-qr/lookup?kode=...` → `AsetController@lookup` (JSON, hanya unit pengguna).
- **Workflow**:
  1. `startCameraScan()` membuka kamera (`facingMode: environment`) memakai `html5-qrcode` CDN.
  2. Hasil pembacaan dinormalkan oleh `AsetController::normalizeScannedCode()`
     (mendukung QR berisi URL, query `kode`, atau teks polos).
  3. Panel hasil menampilkan foto, kode, kategori/merek, ruangan, rincian kondisi,
     jumlah dipinjam, nilai estimasi, dan tombol aksi (buka ruangan / ubah data / buat laporan).
  4. Tersedia pencarian manual bila kamera tidak dapat digunakan.

### Skill: Autofill Laporan dari Hasil Scan
- **View**: `resources/views/laporan/create.blade.php`
- Scan QR mencocokkan `data-kode` pada dropdown barang lalu mengisi panel informasi aset
  (`#infoNama`, `#infoKode`, `#infoKat`, `#infoMerek`, `#infoLokasi`) beserta batas `qty` terdampak.

### Skill: Generating QR Code Payload
```php
use App\Helpers\QrCodeHelper;
$qrUrl = QrCodeHelper::generateSvg('MI-K1-MJ50');   // atau $asset->qrUrl()
```

---

## 📄 3. Report, Verification & Mutation Skills

### Skill: Damage Report Submission & Verification Lifecycle
- **Actions**: `LaporanController@create|store|index|selesai|updateStatus|cetakPdf|export`
- **Routes**:
  - `GET /laporan/create`, `POST /laporan` (Admin Unit)
  - `GET /laporan` — Cek Pelaporan (unit) / Laporan & Audit Global (yayasan)
  - `GET /laporan/selesai`, `GET /laporan/export`, `GET /laporan/{id}/pdf`
  - `PUT /laporan/{id}/status` — **hanya Admin Yayasan**
- **Siklus status**: `pending` ➔ `proses` ➔ `selesai` + hasil `verifikasi` (`disetujui` / `ditolak`).
- **Aturan aksi verifikasi** (`aksi` = `proses|setujui|tolak|buka`):
  - `tolak` wajib menyertakan `tanggapan` (alasan penolakan).
  - `proses` ditolak bila laporan sudah `selesai` (harus `buka` ulang lebih dulu).
  - `buka` hanya berlaku untuk laporan `selesai`; verifikasi & verifikator dikosongkan.
  - Setiap perubahan mengirim notifikasi ke Admin Unit pelapor.
- **Filter audit**: `status`, `jenis`, `verifikasi`, `unit`, `q`, `dari`, `sampai` (+ ekspor CSV).

### Skill: Barang Keluar — Penggantian Barang Rusak & Peminjaman
- **Actions**: `MutasiController@keluar|storeKeluar|kembalikan`
- **Routes**: `GET /barang-keluar`, `POST /barang-keluar`, `POST /barang-keluar/{id}/kembalikan`
- **Jenis `penggantian`** (barang rusak yang harus diganti):
  - `kondisi_sumber` wajib `rusak_ringan` atau `rusak_berat`; `qty <= stok kondisi tersebut`.
  - Mengurangi kolom kondisi terkait **dan** `total_qty`, lalu membuat `Report` jenis
    `penggantian` berstatus `pending` untuk diverifikasi Yayasan.
- **Jenis `peminjaman`** (barang dipinjamkan):
  - `peminjam` & `tanggal_kembali_rencana` wajib; `tanggal_keluar` tidak boleh di masa depan;
    `tanggal_kembali_rencana >= tanggal_keluar`.
  - `qty <= Asset::qtySiapPinjam()` (kondisi baik dikurangi pinjaman yang masih aktif).
  - Kolom kondisi **tidak berubah**; mutasi bertanda `status_pinjam = dipinjam`.
- **Pengembalian** (`kembalikan`):
  - Hanya untuk mutasi `peminjaman` berstatus `dipinjam`.
  - `tanggal_kembali` tidak boleh mendahului `tanggal_keluar` maupun melewati hari ini.
  - `kondisi_kembali = baik` → stok tidak berubah; `rusak_ringan`/`rusak_berat` → qty dipindahkan
    dari `kondisi_baik` ke kondisi tersebut (total tetap), lalu dicatat mutasi `masuk`
    berjenis `pengembalian`.

### Skill: Barang Masuk (Pengadaan & Barang Pengganti)
- **Actions**: `MutasiController@masuk|storeMasuk` — `GET|POST /barang-masuk`
- `jenis` = `pengadaan` atau `penggantian_baru`; qty 1–10.000; `tanggal_masuk <= hari ini`.
- Unit yang masuk ditambahkan ke `kondisi_baik` sehingga `total_qty` ikut naik.

---

## 🔔 4. Notification Skills (Navbar Real-Time)

### Skill: In-App Notification Feed
- **Controller**: `NotificationController@feed|markRead|markAllRead|index`
- **Routes**: `GET /notifikasi/feed`, `POST /notifikasi/{id}/read`, `POST /notifikasi/read-all`, `GET /notifikasi`
- **Workflow**:
  1. Panel lonceng pada navbar melakukan polling `/notifikasi/feed` setiap 20 detik,
     berhenti saat tab tidak aktif dan menyegarkan begitu tab kembali dibuka.
  2. Badge menampilkan jumlah belum dibaca; item dapat ditandai satu per satu atau sekaligus.
  3. Membuat notifikasi **selalu** melalui `NotificationService`:
     `laporanDibuat`, `laporanDiverifikasi`, `barangMasuk`, `barangKeluar`, `barangDikembalikan`.

---

## 🏷️ 5. Master Data & Account Management Skills

### Skill: Category / Brand / Location CRUD (Admin Unit)
- **Controllers**: `CategoryController`, `MerekController`, `LocationController`
- **Routes**: `/categories`, `/merek`, `/locations` (`GET`, `POST`, `PUT /{id}`, `DELETE /{id}`)
- Data yang masih dipakai barang **tidak dapat dihapus** (kategori/merek/ruangan).
- Nama ruangan unik per unit; slug dibuat berprefiks kode unit (`mi-`, `mts-`, `smk-`).
- Daftar ruangan menjadi sumber menu **Ruang & Fasilitas** pada sidebar.
- **Quick add dari form barang**: `CategoryController@store` dan `MerekController@store`
  membalas **JSON** (`201`) bila permintaan memakai `Accept: application/json`. Halaman
  *Tambah Barang* memakainya untuk menambah kategori/merek lewat modal tanpa meninggalkan
  form — opsi baru langsung ditambahkan ke dropdown dan terpilih otomatis, sementara isian
  form lain tetap utuh. Kegagalan validasi (mis. nama duplikat) dibalas `422` dan pesannya
  ditampilkan di dalam modal.

### Skill: Yayasan — Detail Inventaris per Unit Sekolah
- **Controller**: `UnitInventoryController@index|show|export`
- **Routes**: `GET /unit-sekolah`, `GET /unit-sekolah/{unit}`, `GET /unit-sekolah/{unit}/export`
- Menampilkan rekap jumlah barang seluruh unit, rekap per ruangan, dan daftar aset
  (filter kata kunci / ruangan / kondisi) tanpa masuk ke dashboard sekolah.

### Skill: User Account Management (Super Admin Yayasan)
- **Controller**: `UserController@index|store|edit|update|destroy`
- **Routes**: `GET /admin/users`, `POST /admin/users`, `GET /admin/users/{id}/edit`,
  `PUT /admin/users/{id}`, `DELETE /admin/users/{id}`
- **Aturan**: email unik; password minimal 8 karakter + konfirmasi (opsional saat edit);
  `admin_unit` wajib punya unit; sistem selalu menyisakan minimal satu Super Admin;
  akun sendiri tidak bisa diturunkan role-nya maupun dihapus.

### Skill: Pengaturan Akun Sendiri
- **Controller**: `ProfileController@edit|update|updatePassword`
- **Routes**: `GET /profile`, `PUT /profile`, `PUT /profile/password`
- Ganti sandi mewajibkan `current_password` yang benar dan sandi baru berbeda dari sandi lama.

---

## 🖨️ 6. Exporting & Label Printing Skills

### Skill: Cetak Label QR Terpilih (menu Master Data)
- **Actions**: `AsetController@cetakLabel|cetakLabelPreview`
- **Routes**: `GET /cetak-label-qr`, `GET /cetak-label-qr/preview`
- **Workflow**: pilih ruangan ➔ centang barang ➔ tentukan jumlah label per baris (2/3/4)
  ➔ halaman `aset/pdf-labels` siap `window.print()` / simpan sebagai PDF.

### Skill: Cetak Label Seluruh Ruangan
- **Actions**: `AsetController@cetakQr|exportPdf` — `GET /aset/{ruangan}/cetak-qr`, `GET /aset/{ruangan}/export-pdf`.

### Skill: CSV Data Export
- `AsetController@exportExcel` — `GET /aset/{ruangan}/export-excel` (daftar aset ruangan).
- `LaporanController@export` — `GET /laporan/export` (rekap laporan sesuai filter audit).
- `UnitInventoryController@export` — `GET /unit-sekolah/{unit}/export` (inventaris satu unit).

---

## 🎨 7. UI & Responsiveness Skills

- **Layout**: `resources/views/layouts/admin.blade.php` memuat design system (variabel CSS + kelas
  `.card`, `.btn`, `.badge`, `.stat`, `.table-wrap`, `.modal`, `.filter-bar`, `.empty-state`).
- **Sidebar**: off-canvas < 900px (tombol hamburger + overlay), rail ikon 900–1100px,
  dan dapat dilipat di desktop (status disimpan pada `localStorage`).
- **Judul navbar dinamis**: `App\Helpers\PageTitleHelper` memetakan route aktif ➔ judul + ikon.
- **Tabel lebar**: bungkus dengan `.table-wrap` (scroll horizontal + petunjuk `.scroll-hint`).
- **Pagination**: memakai view kustom `resources/views/vendor/pagination/siv.blade.php`.
- **Landing page** (`welcome.blade.php`): hero memakai `public/image/sekolah.jpeg`, kartu statistik
  memuat jumlah aset nyata per unit, dan footer berisi profil yayasan + navigasi + ikon sosial media.
- **Login page** (`auth/login.blade.php`): panel kiri identitas yayasan dengan latar
  `public/image/sekolah1on1.jpeg`, panel kanan kartu "Login Admin".

---

## ⚡ 8. Essential Artisan & CLI Commands

```bash
# Reset database dan isi data awal
php artisan migrate:fresh --seed

# Verifikasi seluruh rute terdaftar
php artisan route:list

# Jalankan server pengembangan
php artisan serve

# Bersihkan cache tampilan/konfigurasi setelah mengubah blade atau .env
php artisan view:clear && php artisan config:clear
```
