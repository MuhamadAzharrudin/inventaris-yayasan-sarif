<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetMutation extends Model
{
    use HasFactory;

    /** Jenis mutasi yang dikenali sistem. */
    public const JENIS_MASUK  = ['pengadaan', 'pengembalian', 'penggantian_baru', 'lainnya'];
    public const JENIS_KELUAR = ['penggantian', 'peminjaman', 'penghapusan', 'lainnya'];

    protected $fillable = [
        'asset_id',
        'unit_id',
        'location_id',
        'tipe',
        'jenis',
        'kondisi_sumber',
        'qty',
        'peminjam',
        'kontak_peminjam',
        'tanggal_keluar',
        'tanggal_kembali_rencana',
        'tanggal_kembali_aktual',
        'status_pinjam',
        'keterangan',
        'user_id',
        'report_id',
    ];

    protected $casts = [
        'qty'                     => 'integer',
        'tanggal_keluar'          => 'date',
        'tanggal_kembali_rencana' => 'date',
        'tanggal_kembali_aktual'  => 'date',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function report()
    {
        return $this->belongsTo(Report::class);
    }

    /* ── Helper ─────────────────────────────────────────────── */

    public function isPeminjaman(): bool
    {
        return $this->jenis === 'peminjaman';
    }

    public function isPinjamAktif(): bool
    {
        return $this->isPeminjaman() && $this->status_pinjam === 'dipinjam';
    }

    public function isTerlambat(): bool
    {
        return $this->isPinjamAktif()
            && $this->tanggal_kembali_rencana !== null
            && $this->tanggal_kembali_rencana->isPast();
    }

    public function jenisLabel(): string
    {
        return match ($this->jenis) {
            'pengadaan'        => 'Pengadaan Barang Baru',
            'pengembalian'     => 'Pengembalian Barang Pinjaman',
            'penggantian_baru' => 'Barang Pengganti Masuk',
            'penggantian'      => 'Barang Rusak untuk Diganti',
            'peminjaman'       => 'Barang Dipinjamkan',
            'penghapusan'      => 'Penghapusan / Afkir Barang',
            default            => 'Mutasi Lainnya',
        };
    }

    public function kondisiSumberLabel(): string
    {
        return match ($this->kondisi_sumber) {
            'baik'         => 'Kondisi Baik',
            'rusak_ringan' => 'Rusak Ringan',
            'rusak_berat'  => 'Rusak Berat',
            default        => '-',
        };
    }
}
