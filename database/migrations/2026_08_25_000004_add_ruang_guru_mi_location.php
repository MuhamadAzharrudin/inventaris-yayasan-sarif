<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Menambahkan ruangan "Ruang Guru MI" pada unit MI.
     *
     * Bersifat idempoten: pada database kosong (migrate:fresh) belum ada unit
     * sehingga tidak ada data yang ditambahkan — ruangan tersebut dibuat oleh
     * DatabaseSeeder. Pada database berjalan, ruangan langsung ditambahkan
     * bila belum tersedia.
     */
    public function up(): void
    {
        $unitId = DB::table('units')->where('kode', 'mi')->value('id');

        if (! $unitId) {
            return;
        }

        $sudahAda = DB::table('locations')
            ->where('unit_id', $unitId)
            ->where(function ($q) {
                $q->where('slug', 'mi-ruang-guru')
                    ->orWhereRaw('LOWER(nama) = ?', ['ruang guru mi']);
            })
            ->exists();

        if ($sudahAda) {
            return;
        }

        DB::table('locations')->insert([
            'unit_id'    => $unitId,
            'nama'       => 'Ruang Guru MI',
            'slug'       => 'mi-ruang-guru',
            'tipe'       => 'kantor',
            'deskripsi'  => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        $unitId = DB::table('units')->where('kode', 'mi')->value('id');

        if (! $unitId) {
            return;
        }

        // Hanya dihapus bila belum memuat data barang.
        $location = DB::table('locations')
            ->where('unit_id', $unitId)
            ->where('slug', 'mi-ruang-guru')
            ->first();

        if (! $location) {
            return;
        }

        if (DB::table('assets')->where('location_id', $location->id)->exists()) {
            return;
        }

        DB::table('locations')->where('id', $location->id)->delete();
    }
};
