<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    /** Jenis laporan yang dikenali sistem. */
    public const JENIS = ['kerusakan', 'penggantian', 'peminjaman'];

    /** Status siklus hidup laporan. */
    public const STATUS = ['pending', 'proses', 'selesai'];

    /** Hasil verifikasi Admin Yayasan. */
    public const VERIFIKASI = ['disetujui', 'ditolak'];

    protected $fillable = [
        'unit_id',
        'asset_id',
        'location_id',
        'user_id',
        'jenis',
        'judul',
        'deskripsi',
        'qty',
        'status',
        'verifikasi',
        'verified_by',
        'verified_at',
        'tanggapan',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'qty'         => 'integer',
    ];

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function mutations()
    {
        return $this->hasMany(AssetMutation::class);
    }

    /* ── Helper status ───────────────────────────────────────── */

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isProses(): bool
    {
        return $this->status === 'proses';
    }

    public function isSelesai(): bool
    {
        return $this->status === 'selesai';
    }

    public function isVerified(): bool
    {
        return in_array($this->verifikasi, self::VERIFIKASI, true);
    }

    public function isApproved(): bool
    {
        return $this->verifikasi === 'disetujui';
    }

    public function isRejected(): bool
    {
        return $this->verifikasi === 'ditolak';
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'Menunggu Verifikasi',
            'proses'  => 'Sedang Diproses Yayasan',
            'selesai' => $this->isRejected() ? 'Selesai — Ditolak' : 'Selesai — Disetujui',
            default   => ucfirst((string) $this->status),
        };
    }

    public function jenisLabel(): string
    {
        return match ($this->jenis) {
            'kerusakan'   => 'Laporan Kerusakan',
            'penggantian' => 'Penggantian Barang Rusak',
            'peminjaman'  => 'Peminjaman Barang',
            default       => 'Laporan',
        };
    }
}
