<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    protected $fillable = ['nama', 'kode', 'kepala_unit'];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function locations()
    {
        return $this->hasMany(Location::class);
    }

    public function assets()
    {
        return $this->hasMany(Asset::class);
    }

    public function reports()
    {
        return $this->hasMany(Report::class);
    }

    /** Label pendek unit, mis. "MI", "MTS", "SMK". */
    public function label(): string
    {
        return strtoupper($this->kode === 'smp' ? 'mts' : (string) $this->kode);
    }

    /** Nama jenjang panjang untuk kebutuhan tampilan. */
    public function jenjang(): string
    {
        return match (strtolower((string) $this->kode)) {
            'mi'  => 'Madrasah Ibtidaiyah',
            'smp' => 'Madrasah Tsanawiyah',
            'smk' => 'Sekolah Menengah Kejuruan',
            default => $this->nama,
        };
    }

    public function icon(): string
    {
        return match (strtolower((string) $this->kode)) {
            'mi'  => 'school',
            'smp' => 'graduation-cap',
            'smk' => 'cpu',
            default => 'building-2',
        };
    }
}
