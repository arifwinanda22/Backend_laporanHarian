<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // database/migrations/xxxx_xx_xx_create_monitorings_table.php

public function up(): void
{
    Schema::create('monitorings', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        
        // Header Laporan
        $table->string('nomor_form')->unique(); // Wajib unik
        $table->date('tanggal');
        $table->string('tim_pelaksana');
        
        // Pilihan User (Wajib diisi, jadi tidak perlu default)
        $table->string('area'); // Menyimpan 'Gedung Setda A', dll
        $table->string('periode'); // Menyimpan 'Mingguan', 'Bulanan', dll
        
        // Tambahan Status Umum (Kesimpulan)
        $table->string('status_umum')->nullable(); // Bisa nullable jika diisi belakangan, atau required jika wajib
        
        // Tambahan Ringkasan & Rencana Tindak Lanjut (Biasanya teks panjang)
        $table->text('ringkasan')->nullable();
        $table->text('rencana_tindak_lanjut')->nullable();

        $table->timestamps();
    });
}
    public function down(): void
    {
        Schema::dropIfExists('monitorings');
    }
};