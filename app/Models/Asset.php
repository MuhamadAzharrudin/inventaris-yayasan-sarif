<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    use HasFactory;

    protected $fillable = [
        'unit_id',
        'location_id',
        'category_id',
        'merek_id',
        'kode_barang',
        'nama_barang',
        'total_qty',
        'kondisi_baik',
        'kondisi_rusak_ringan',
        'kondisi_rusak_berat',
        'foto_barang',
        'qr_code_path',
        'spesifikasi',
        'nilai_estimasi',
    ];

    protected $casts = [
        'total_qty'            => 'integer',
        'kondisi_baik'         => 'integer',
        'kondisi_rusak_ringan' => 'integer',
        'kondisi_rusak_berat'  => 'integer',
        'nilai_estimasi'       => 'decimal:2',
    ];

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function merek()
    {
        return $this->belongsTo(Merek::class);
    }

    public function mutations()
    {
        return $this->hasMany(AssetMutation::class);
    }

    public function reports()
    {
        return $this->hasMany(Report::class);
    }

    /** Peminjaman yang masih berjalan (belum dikembalikan). */
    public function pinjamanAktif()
    {
        return $this->hasMany(AssetMutation::class)
            ->where('jenis', 'peminjaman')
            ->where('status_pinjam', 'dipinjam');
    }

    /* ── Aturan kondisi & ketersediaan ──────────────────────── */

    /** Jumlah unit yang sedang berada di luar ruangan karena dipinjam. */
    public function qtyDipinjam(): int
    {
        return (int) $this->mutations()
            ->where('jenis', 'peminjaman')
            ->where('status_pinjam', 'dipinjam')
            ->sum('qty');
    }

    /** Unit kondisi baik yang benar-benar bisa dipinjamkan saat ini. */
    public function qtySiapPinjam(): int
    {
        return max(0, (int) $this->kondisi_baik - $this->qtyDipinjam());
    }

    /** Jumlah unit rusak (ringan + berat) yang dapat diajukan penggantian. */
    public function qtyRusak(): int
    {
        return (int) $this->kondisi_rusak_ringan + (int) $this->kondisi_rusak_berat;
    }

    /** Jumlah unit pada satu kategori kondisi. */
    public function qtyKondisi(string $kondisi): int
    {
        return match ($kondisi) {
            'baik'         => (int) $this->kondisi_baik,
            'rusak_ringan' => (int) $this->kondisi_rusak_ringan,
            'rusak_berat'  => (int) $this->kondisi_rusak_berat,
            default        => 0,
        };
    }

    /** Menjaga kontrak total_qty = baik + rusak ringan + rusak berat. */
    public function syncTotalQty(): void
    {
        $this->total_qty = (int) $this->kondisi_baik
            + (int) $this->kondisi_rusak_ringan
            + (int) $this->kondisi_rusak_berat;
    }

    public function fotoUrl(): string
    {
        return $this->foto_barang ?: asset('image/sekolah.jpeg');
    }

    public function qrUrl(): string
    {
        return $this->qr_code_path ?: \App\Helpers\QrCodeHelper::generateSvg($this->kode_barang);
    }
}
