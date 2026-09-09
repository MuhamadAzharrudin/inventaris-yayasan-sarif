<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'unit_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    /** Notifikasi in-app milik pengguna (bukan tabel bawaan Laravel). */
    public function appNotifications()
    {
        return $this->hasMany(Notification::class)->latest();
    }

    public function reports()
    {
        return $this->hasMany(Report::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    /* ── Role helpers ───────────────────────────────────────── */

    /** Admin tingkat Yayasan (akses penuh lintas unit). */
    public function isSuperAdmin(): bool
    {
        if (in_array($this->role, ['super_admin', 'admin_yayasan'], true)) {
            return true;
        }

        // Akun tanpa unit dan tanpa role unit tetap diperlakukan sebagai Yayasan.
        return $this->role !== 'admin_unit' && is_null($this->unit_id);
    }

    /** Admin tingkat unit sekolah (data terisolasi per unit). */
    public function isAdminUnit(): bool
    {
        return ! $this->isSuperAdmin();
    }

    public function roleLabel(): string
    {
        if ($this->isSuperAdmin()) {
            return 'Super Admin Yayasan';
        }

        return 'Admin Unit ' . ($this->unit?->label() ?? 'Sekolah');
    }

    /** Kode unit ternormalisasi: mi | mts | smk | null (yayasan). */
    public function unitKode(): ?string
    {
        if ($this->isSuperAdmin()) {
            return null;
        }

        $kode = strtolower((string) $this->unit?->kode);

        return $kode === 'smp' ? 'mts' : ($kode ?: null);
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim((string) $this->name)) ?: [];
        $parts = array_values(array_filter($parts));

        if (count($parts) >= 2) {
            return strtoupper(mb_substr($parts[0], 0, 1) . mb_substr($parts[1], 0, 1));
        }

        return strtoupper(mb_substr((string) $this->name, 0, 2)) ?: 'AD';
    }
}
