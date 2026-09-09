<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Notifikasi in-app yang tampil pada navbar (lonceng).
 */
class Notification extends Model
{
    use HasFactory;

    protected $table = 'app_notifications';

    protected $fillable = [
        'user_id',
        'unit_id',
        'report_id',
        'asset_id',
        'tipe',
        'judul',
        'pesan',
        'url',
        'icon',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function report()
    {
        return $this->belongsTo(Report::class);
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function isUnread(): bool
    {
        return is_null($this->read_at);
    }

    /**
     * Ikon Lucide default berdasarkan tipe notifikasi.
     */
    public function iconName(): string
    {
        if (! empty($this->icon)) {
            return $this->icon;
        }

        return match ($this->tipe) {
            'laporan_baru'       => 'file-plus',
            'laporan_proses'     => 'loader',
            'laporan_disetujui'  => 'check-circle-2',
            'laporan_ditolak'    => 'x-circle',
            'barang_masuk'       => 'arrow-down-left-square',
            'barang_keluar'      => 'arrow-up-right-square',
            'peminjaman'         => 'handshake',
            'pengembalian'       => 'undo-2',
            default              => 'bell',
        };
    }

    /**
     * Warna aksen badge berdasarkan tipe notifikasi.
     */
    public function accentColor(): string
    {
        return match ($this->tipe) {
            'laporan_baru'      => '#DC2626',
            'laporan_proses'    => '#2563EB',
            'laporan_disetujui' => '#16A34A',
            'laporan_ditolak'   => '#B91C1C',
            'barang_masuk'      => '#16A34A',
            'barang_keluar'     => '#EA580C',
            'peminjaman'        => '#7C3AED',
            'pengembalian'      => '#0891B2',
            default             => '#64748B',
        };
    }
}
