<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laporan_pekerjaans', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            $table->string('id_pekerjaan')->unique()->index();
            $table->date('tanggal');
            $table->string('jenis_pekerjaan');
            $table->string('bagian');
            $table->string('petugas');
            
            // PERUBAHAN DISINI: Status disesuaikan flow
            // Default 'Dikerjakan' sesuai permintaan (otomatis dikerjakan saat input)
            $table->enum('status', ['Dikerjakan', 'Menunggu Persetujuan', 'Selesai', 'Ditolak'])
                  ->default('Dikerjakan');
                  
            $table->text('deskripsi')->nullable();
            
            // Gunakan json untuk kompatibilitas MySQL/Postgres
            $table->json('lampiran')->nullable(); 
            
            $table->timestamps();
            
            $table->index('tanggal');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laporan_pekerjaans');
    }
};