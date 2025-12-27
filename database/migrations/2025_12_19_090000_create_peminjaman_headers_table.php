<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. DATA PEMINJAM (Sesuai Form Atas)
        Schema::create('peminjaman_headers', function (Blueprint $table) {
            $table->id(); // ID internal database (wajib ada), tapi tidak perlu diinput user
            $table->string('nama_peminjam');
            $table->string('bagian');
            $table->date('tgl_pinjam');
            $table->date('tgl_kembali');
            $table->timestamps();
        });

        // 2. DATA BARANG (Sesuai Form Bawah - Multiple)
        Schema::create('peminjaman_details', function (Blueprint $table) {
            $table->id();
            // Penghubung ke Header
            $table->foreignId('peminjaman_header_id')
                  ->constrained('peminjaman_headers')
                  ->onDelete('cascade');
            
            // Kolom Sesuai View
            $table->string('nama_barang');
            $table->string('merk_kode')->nullable(); // Input Merk/Kode
            $table->integer('jumlah');
            $table->integer('sisa_stok')->nullable(); // Input Sisa Stok (disimpan sebagai catatan)
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('peminjaman_details');
        Schema::dropIfExists('peminjaman_headers');
    }
};