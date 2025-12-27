<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
 public function up()
{
    Schema::create('barang_keluars', function (Blueprint $table) {
        $table->id();
        $table->string('no_transaksi')->unique();
        $table->date('tgl_keluar');
        
        // --- PERUBAHAN DISINI ---
        // Hapus foreignId / barang_id
        // Ganti jadi String biasa agar bisa ketik bebas
        $table->string('nama_barang'); 
        // ------------------------

        $table->string('nama_penerima');
        $table->string('bagian');
        $table->integer('jumlah_keluar');
        $table->string('petugas');
        $table->timestamps();
    });
}
    public function down(): void
    {
        Schema::dropIfExists('barang_keluars');
    }
};
