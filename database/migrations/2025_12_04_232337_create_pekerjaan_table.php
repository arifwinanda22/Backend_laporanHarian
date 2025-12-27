<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabel Utama (Laporan Pekerjaan)
        Schema::create('laporan_pekerjaans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('id_pekerjaan')->unique()->index();
            $table->date('tanggal');
            $table->string('jenis_pekerjaan');
            $table->string('bagian');
            $table->string('petugas');
            $table->enum('status', ['Dikerjakan', 'Menunggu Persetujuan', 'Selesai', 'Ditolak'])
                  ->default('Dikerjakan');
            $table->text('deskripsi')->nullable();
            
            // HAPUS kolom 'lampiran' dari sini karena akan dibuat tabel terpisah
            $table->json('lampiran')->nullable(); 
            
            $table->timestamps();
            $table->index('tanggal');
            $table->index('status');
            $table->longText('lampiran_foto')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laporan_pekerjaans');
    }
};