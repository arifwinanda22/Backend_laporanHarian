<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('monitoring_akses_jaringans', function (Blueprint $table) {
            $table->id();
            
            // Relasi ke tabel induk (monitorings)
            // Pastikan tabel 'monitorings' sudah dibuat di migration sebelumnya
            $table->foreignId('monitoring_id')
                  ->constrained('monitorings')
                  ->onDelete('cascade');
            
            // Kolom Data
            $table->string('komponen');    // Contoh: AC, Server Rack
            $table->string('pemeriksaan'); // Contoh: Suhu, Kebersihan
           $table->boolean('hasil');// Contoh: OK, Rusak, Kotor
            $table->text('tindakan')->nullable(); // Contoh: Dibersihkan, Diganti (Boleh kosong)
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitoring_akses_jaringans');
    }
};