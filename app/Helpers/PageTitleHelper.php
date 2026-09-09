<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * Menentukan judul halaman (navbar & <title>) secara dinamis
 * berdasarkan route yang sedang diakses.
 */
class PageTitleHelper
{
    /**
     * Peta route name -> [judul, ikon lucide].
     *
     * @var array<string, array{0:string,1:string}>
     */
    private const MAP = [
        'dashboard'            => ['Dashboard', 'layout-dashboard'],
        'dashboard.yayasan'    => ['Dashboard Yayasan', 'landmark'],
        'dashboard.mi'         => ['Dashboard Unit MI', 'school'],
        'dashboard.smp'        => ['Dashboard Unit MTS', 'graduation-cap'],
        'dashboard.smk'        => ['Dashboard Unit SMK', 'cpu'],
        'smp.dashboard'        => ['Dashboard Unit MTS', 'graduation-cap'],

        'unit.show'            => ['Detail Inventaris Unit Sekolah', 'building-2'],
        'unit.index'           => ['Inventaris Seluruh Unit Sekolah', 'building-2'],

        'ruangan.show'         => ['Daftar Barang Ruangan', 'door-open'],
        'smp.ruangan.show'     => ['Daftar Barang Ruangan', 'door-open'],

        'aset.create'          => ['Tambah Barang Baru', 'plus-circle'],
        'smp.aset.create'      => ['Tambah Barang Baru', 'plus-circle'],
        'aset.edit'            => ['Ubah Data Barang', 'pencil'],
        'aset.scan'            => ['Scan QR Code', 'scan-line'],
        'aset.cetak-qr'        => ['Cetak Label QR Ruangan', 'printer'],
        'aset.cetak-label'     => ['Cetak Label QR', 'printer'],
        'aset.cetak-label.preview' => ['Pratinjau Label QR', 'printer'],
        'aset.export-pdf'      => ['Cetak Label PDF', 'file-text'],

        'merek.index'          => ['Master Data Merek / Brand', 'bookmark'],
        'smp.merek.index'      => ['Master Data Merek / Brand', 'bookmark'],
        'categories.index'     => ['Master Data Kategori Barang', 'tag'],
        'smp.categories.index' => ['Master Data Kategori Barang', 'tag'],
        'locations.index'      => ['Master Data Lokasi Ruangan', 'map-pin'],
        'smp.locations.index'  => ['Master Data Lokasi Ruangan', 'map-pin'],

        'laporan.index'        => ['Cek Pelaporan', 'file-search'],
        'smp.laporan.index'    => ['Cek Pelaporan', 'file-search'],
        'laporan.create'       => ['Buat Laporan Kerusakan', 'file-plus'],
        'smp.laporan.create'   => ['Buat Laporan Kerusakan', 'file-plus'],
        'laporan.selesai'      => ['Pelaporan Selesai', 'check-circle-2'],
        'smp.laporan.selesai'  => ['Pelaporan Selesai', 'check-circle-2'],
        'laporan.masuk'        => ['Barang Masuk', 'arrow-down-left-square'],
        'laporan.keluar'       => ['Barang Keluar', 'arrow-up-right-square'],
        'laporan.pdf'          => ['Cetak Laporan', 'printer'],

        'notifikasi.index'     => ['Notifikasi', 'bell'],

        'users.index'          => ['Pengaturan Akun & User', 'users'],
        'users.edit'           => ['Ubah Akun Pengguna', 'user-cog'],
        'profile.edit'         => ['Pengaturan Akun Saya', 'user-circle'],
    ];

    /**
     * Judul halaman aktif. Untuk laporan & audit, judul menyesuaikan role.
     */
    public static function current(): string
    {
        [$title] = self::resolve();

        return $title;
    }

    /** Ikon lucide untuk halaman aktif. */
    public static function icon(): string
    {
        [, $icon] = self::resolve();

        return $icon;
    }

    /**
     * @return array{0:string,1:string}
     */
    public static function resolve(): array
    {
        $name = Route::currentRouteName();
        $user = auth()->user();

        if ($name === 'laporan.index' || $name === 'smp.laporan.index') {
            return $user && $user->isSuperAdmin()
                ? ['Laporan & Audit Global', 'file-bar-chart']
                : ['Cek Pelaporan', 'file-search'];
        }

        if ($name === 'dashboard') {
            if ($user && $user->isSuperAdmin()) {
                return ['Dashboard Yayasan', 'landmark'];
            }

            return match ($user?->unitKode()) {
                'mi'  => ['Dashboard Unit MI', 'school'],
                'mts' => ['Dashboard Unit MTS', 'graduation-cap'],
                'smk' => ['Dashboard Unit SMK', 'cpu'],
                default => ['Dashboard', 'layout-dashboard'],
            };
        }

        if ($name !== null && isset(self::MAP[$name])) {
            return self::MAP[$name];
        }

        // Fallback: bangun judul dari segmen URL agar tetap informatif.
        $segments = request()->segments();
        if (empty($segments)) {
            return ['Dashboard', 'layout-dashboard'];
        }

        $readable = collect($segments)
            ->reject(fn ($s) => is_numeric($s))
            ->map(fn ($s) => Str::title(str_replace('-', ' ', $s)))
            ->implode(' / ');

        return [$readable !== '' ? $readable : 'Dashboard', 'layout-dashboard'];
    }
}
