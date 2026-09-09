<?php

namespace App\Http\Controllers\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Helper isolasi data multi-unit (MI / MTS / SMK).
 *
 * Super Admin Yayasan melihat seluruh unit, Admin Unit hanya unitnya sendiri.
 */
trait HandlesUnitScope
{
    protected function currentUser(): ?User
    {
        /** @var User|null $user */
        $user = auth()->user();

        return $user;
    }

    protected function isSuperAdmin(): bool
    {
        return (bool) $this->currentUser()?->isSuperAdmin();
    }

    /** Unit aktif pengguna (null untuk Yayasan). */
    protected function currentUnitId(): ?int
    {
        $user = $this->currentUser();

        if (! $user || $user->isSuperAdmin()) {
            return null;
        }

        return $user->unit_id;
    }

    /**
     * Membatasi query pada unit milik pengguna.
     */
    protected function scopeUnit(Builder $query, string $column = 'unit_id'): Builder
    {
        $unitId = $this->currentUnitId();

        if ($unitId !== null) {
            $query->where($column, $unitId);
        }

        return $query;
    }

    /**
     * Memastikan pengguna berhak mengakses data milik unit tertentu.
     */
    protected function guardUnit(?int $unitId): void
    {
        $currentUnit = $this->currentUnitId();

        if ($currentUnit === null) {
            return; // Yayasan: akses penuh.
        }

        if ($unitId !== null && (int) $unitId !== (int) $currentUnit) {
            abort(403, 'Data tersebut berada di luar unit sekolah Anda.');
        }
    }
}
