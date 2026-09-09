<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('units')->onDelete('cascade');
            $table->foreignId('location_id')->nullable()->constrained('locations')->onDelete('set null');
            $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('set null');
            $table->foreignId('merek_id')->nullable()->constrained('mereks')->onDelete('set null');
            $table->string('kode_barang')->unique();
            $table->string('nama_barang');
            $table->integer('total_qty')->default(1);
            $table->integer('kondisi_baik')->default(0);
            $table->integer('kondisi_rusak_ringan')->default(0);
            $table->integer('kondisi_rusak_berat')->default(0);
            $table->string('foto_barang')->nullable();
            $table->string('qr_code_path')->nullable();
            $table->text('spesifikasi')->nullable();
            $table->decimal('nilai_estimasi', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
