<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asets', function (Blueprint $table) {
            $table->id();
            
            // "Merk/Kode" - Unique key
            $table->string('kode_aset')->unique(); 
            
            // "Nama Aset"
            $table->string('nama_aset');
            
            // "Kategori"
            $table->string('kategori');
            
            // "Jumlah"
            $table->integer('jumlah')->default(1);
            
            // "Status" -> Menggunakan ENUM sesuai permintaan
            // Nilai hanya boleh 'Aktif' atau 'Tidak Aktif'
            $table->enum('status', ['Aktif', 'Tidak Aktif'])->default('Aktif');
            
            // "Log Pembaruan Barcode"
            $table->date('tanggal_log_barcode')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asets');
    }
};