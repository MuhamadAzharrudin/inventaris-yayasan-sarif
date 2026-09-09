<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Melengkapi tabel mutasi aset agar mampu menampung data
     * "Barang Keluar" (penggantian barang rusak & peminjaman barang).
     */
    public function up(): void
    {
        Schema::table('asset_mutations', function (Blueprint $table) {
            // pengadaan | penggantian | peminjaman | pengembalian | penghapusan | lainnya
            $table->string('jenis', 30)->default('lainnya')->after('tipe');
            // baik | rusak_ringan | rusak_berat  (kondisi unit yang dikeluarkan)
            $table->string('kondisi_sumber', 20)->nullable()->after('jenis');
            $table->string('peminjam')->nullable()->after('qty');
            $table->string('kontak_peminjam', 60)->nullable()->after('peminjam');
            $table->date('tanggal_keluar')->nullable()->after('kontak_peminjam');
            $table->date('tanggal_kembali_rencana')->nullable()->after('tanggal_keluar');
            $table->date('tanggal_kembali_aktual')->nullable()->after('tanggal_kembali_rencana');
            // null (bukan peminjaman) | dipinjam | dikembalikan
            $table->string('status_pinjam', 20)->nullable()->after('tanggal_kembali_aktual');
            $table->foreignId('report_id')->nullable()->after('user_id')
                ->constrained('reports')->nullOnDelete();

            $table->index(['unit_id', 'tipe']);
            $table->index(['status_pinjam']);
        });
    }

    public function down(): void
    {
        Schema::table('asset_mutations', function (Blueprint $table) {
            $table->dropIndex(['unit_id', 'tipe']);
            $table->dropIndex(['status_pinjam']);
            $table->dropForeign(['report_id']);
            $table->dropColumn([
                'jenis',
                'kondisi_sumber',
                'peminjam',
                'kontak_peminjam',
                'tanggal_keluar',
                'tanggal_kembali_rencana',
                'tanggal_kembali_aktual',
                'status_pinjam',
                'report_id',
            ]);
        });
    }
};
