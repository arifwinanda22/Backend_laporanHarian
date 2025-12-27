<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stok_opname_aset_logs', function (Blueprint $table) {
            $table->id();

            // Aset mana yang dicek
            $table->unsignedBigInteger('aset_id');

            // Kondisi fisik yang dipilih saat opname
            $table->string('status_fisik', 50); // contoh: OK / Rusak / Hilang / Dipinjam

            // Catatan tambahan dari petugas
            $table->text('catatan')->nullable();

            // Waktu pengecekan
            $table->timestamp('checked_at')->useCurrent();

            // Siapa yang melakukan opname (id user login)
            $table->unsignedBigInteger('checked_by')->nullable();

            $table->timestamps();

            // Foreign key ke tabel asets
            $table->foreign('aset_id')
                ->references('id')
                ->on('asets')
                ->cascadeOnDelete();

            // Foreign key ke tabel users kalau ada
            // Kalau sistemmu memang pakai tabel 'users'
            $table->foreign('checked_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stok_opname_aset_logs');
    }
};
