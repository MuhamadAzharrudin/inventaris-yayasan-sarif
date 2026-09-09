<?php

use App\Http\Controllers\AsetController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\MerekController;
use App\Http\Controllers\MutasiController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RuanganController;
use App\Http\Controllers\UnitInventoryController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// ── Landing Page ───────────────────────────────────────────────────────
Route::get('/', [LandingController::class, 'index'])->name('home');

// ── Auth: Login ────────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.submit');
});

// ── Auth: Logout ───────────────────────────────────────────────────────
Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// ── Area Terproteksi ──────────────────────────────────────────────────
Route::middleware('auth')->group(function () {

    /* Dashboard */
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/yayasan', [DashboardController::class, 'yayasan'])
        ->middleware('super_admin')->name('dashboard.yayasan');
    Route::get('/dashboard/mi', [DashboardController::class, 'mi'])->name('dashboard.mi');
    Route::get('/dashboard/smp', [DashboardController::class, 'smp'])->name('dashboard.smp');
    Route::get('/dashboard/smk', [DashboardController::class, 'smk'])->name('dashboard.smk');

    /* Pengaturan akun sendiri */
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    /* Notifikasi navbar */
    Route::get('/notifikasi', [NotificationController::class, 'index'])->name('notifikasi.index');
    Route::get('/notifikasi/feed', [NotificationController::class, 'feed'])->name('notifikasi.feed');
    Route::post('/notifikasi/{id}/read', [NotificationController::class, 'markRead'])
        ->whereNumber('id')->name('notifikasi.read');
    Route::post('/notifikasi/read-all', [NotificationController::class, 'markAllRead'])->name('notifikasi.readAll');

    /* ── Admin Yayasan: Unit Sekolah & Audit ──────────────────────── */
    Route::middleware('super_admin')->group(function () {
        Route::get('/unit-sekolah', [UnitInventoryController::class, 'index'])->name('unit.index');
        Route::get('/unit-sekolah/{unit}', [UnitInventoryController::class, 'show'])
            ->whereNumber('unit')->name('unit.show');
        Route::get('/unit-sekolah/{unit}/export', [UnitInventoryController::class, 'export'])
            ->whereNumber('unit')->name('unit.export');

        Route::put('/laporan/{id}/status', [LaporanController::class, 'updateStatus'])
            ->whereNumber('id')->name('laporan.updateStatus');
    });

    /* ── Admin Unit Sekolah: pendataan & master data ──────────────── */
    Route::middleware('admin_unit')->group(function () {
        // Daftar barang per ruangan
        Route::put('/ruangan/aset/{id}/kondisi', [RuanganController::class, 'updateKondisi'])
            ->whereNumber('id')->name('ruangan.updateKondisi');

        // CRUD aset
        Route::get('/aset/{ruangan}/create', [AsetController::class, 'create'])->name('aset.create');
        Route::post('/aset', [AsetController::class, 'store'])->name('aset.store');
        Route::get('/aset/{id}/edit', [AsetController::class, 'edit'])->whereNumber('id')->name('aset.edit');
        Route::put('/aset/{id}', [AsetController::class, 'update'])->whereNumber('id')->name('aset.update');
        Route::delete('/aset/{id}', [AsetController::class, 'destroy'])->whereNumber('id')->name('aset.destroy');

        // Master Data: Scan QR & Cetak Label QR
        Route::get('/scan-qr', [AsetController::class, 'scan'])->name('aset.scan');
        Route::get('/scan-qr/lookup', [AsetController::class, 'lookup'])->name('aset.lookup');
        Route::get('/cetak-label-qr', [AsetController::class, 'cetakLabel'])->name('aset.cetak-label');
        Route::get('/cetak-label-qr/preview', [AsetController::class, 'cetakLabelPreview'])
            ->name('aset.cetak-label.preview');

        // Master Data: Merek
        Route::get('/merek', [MerekController::class, 'index'])->name('merek.index');
        Route::post('/merek', [MerekController::class, 'store'])->name('merek.store');
        Route::put('/merek/{id}', [MerekController::class, 'update'])->whereNumber('id')->name('merek.update');
        Route::delete('/merek/{id}', [MerekController::class, 'destroy'])->whereNumber('id')->name('merek.destroy');

        // Master Data: Kategori
        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::put('/categories/{id}', [CategoryController::class, 'update'])->whereNumber('id')->name('categories.update');
        Route::delete('/categories/{id}', [CategoryController::class, 'destroy'])->whereNumber('id')->name('categories.destroy');

        // Master Data: Lokasi / Ruangan
        Route::get('/locations', [LocationController::class, 'index'])->name('locations.index');
        Route::post('/locations', [LocationController::class, 'store'])->name('locations.store');
        Route::put('/locations/{id}', [LocationController::class, 'update'])->whereNumber('id')->name('locations.update');
        Route::delete('/locations/{id}', [LocationController::class, 'destroy'])->whereNumber('id')->name('locations.destroy');

        // Pelaporan kerusakan (dibuat oleh Admin Unit)
        Route::get('/laporan/create', [LaporanController::class, 'create'])->name('laporan.create');
        Route::post('/laporan', [LaporanController::class, 'store'])->name('laporan.store');

        // Barang masuk & keluar
        Route::post('/barang-masuk', [MutasiController::class, 'storeMasuk'])->name('mutasi.masuk.store');
        Route::post('/barang-keluar', [MutasiController::class, 'storeKeluar'])->name('mutasi.keluar.store');
        Route::post('/barang-keluar/{id}/kembalikan', [MutasiController::class, 'kembalikan'])
            ->whereNumber('id')->name('mutasi.keluar.kembalikan');
    });

    /* ── Dapat diakses kedua role (data tetap terisolasi) ─────────── */
    Route::get('/ruangan/{ruangan}', [RuanganController::class, 'show'])->name('ruangan.show');
    Route::get('/aset/{ruangan}/cetak-qr', [AsetController::class, 'cetakQr'])->name('aset.cetak-qr');
    Route::get('/aset/{ruangan}/export-pdf', [AsetController::class, 'exportPdf'])->name('aset.export-pdf');
    Route::get('/aset/{ruangan}/export-excel', [AsetController::class, 'exportExcel'])->name('aset.export-excel');

    Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan.index');
    Route::get('/laporan/selesai', [LaporanController::class, 'selesai'])->name('laporan.selesai');
    Route::get('/laporan/export', [LaporanController::class, 'export'])->name('laporan.export');
    Route::get('/laporan/{id}/pdf', [LaporanController::class, 'cetakPdf'])->whereNumber('id')->name('laporan.pdf');

    Route::get('/barang-masuk', [MutasiController::class, 'masuk'])->name('laporan.masuk');
    Route::get('/barang-keluar', [MutasiController::class, 'keluar'])->name('laporan.keluar');

    /* ── Pengaturan Akun & User (Yayasan) ─────────────────────────── */
    Route::middleware('super_admin')->prefix('admin')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{id}/edit', [UserController::class, 'edit'])->whereNumber('id')->name('users.edit');
        Route::put('/users/{id}', [UserController::class, 'update'])->whereNumber('id')->name('users.update');
        Route::delete('/users/{id}', [UserController::class, 'destroy'])->whereNumber('id')->name('users.destroy');
    });
});
