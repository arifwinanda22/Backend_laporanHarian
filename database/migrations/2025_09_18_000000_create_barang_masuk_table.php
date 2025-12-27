<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up()
{
    Schema::create('barang_masuk', function (Blueprint $table) {
        $table->id();
        $table->string('no_transaksi')->unique(); // T-BM-xxxx
        $table->date('tgl_masuk');
        
        // Relasi ke tabel master barang
        $table->foreignId('barang_id')->constrained('barangs')->onDelete('cascade');
        
        // Kategori bisa diambil dari master barang, tapi jika di form input manual, kita simpan stringnya
        $table->string('kategori')->nullable(); 
        
        $table->integer('jumlah_masuk');
        $table->string('user'); // Input manual nama user (Kevin, dll)
        $table->timestamps();
    });
}
    public function down(): void
    {
        Schema::dropIfExists('barang_masuk');
    }
};
