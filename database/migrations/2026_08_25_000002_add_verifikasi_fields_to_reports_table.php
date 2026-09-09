<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan atribut jenis laporan & jejak verifikasi Admin Yayasan.
     */
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            // kerusakan | penggantian | peminjaman
            $table->string('jenis', 30)->default('kerusakan')->after('user_id');
            $table->unsignedInteger('qty')->nullable()->after('deskripsi');
            // null (belum diverifikasi) | disetujui | ditolak
            $table->string('verifikasi', 20)->nullable()->after('status');
            $table->foreignId('verified_by')->nullable()->after('verifikasi')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable()->after('verified_by');

            $table->index(['unit_id', 'status']);
            $table->index(['jenis']);
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropIndex(['unit_id', 'status']);
            $table->dropIndex(['jenis']);
            $table->dropForeign(['verified_by']);
            $table->dropColumn(['jenis', 'qty', 'verifikasi', 'verified_by', 'verified_at']);
        });
    }
};
