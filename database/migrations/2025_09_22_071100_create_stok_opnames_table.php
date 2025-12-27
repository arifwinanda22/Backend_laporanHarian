<?php

// database/migrations/2025_10_28_000000_create_stok_opnames_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stok_opnames', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aset_id')->constrained('asets')->onDelete('cascade');
            $table->string('status_fisik'); // 'OK', 'Rusak', 'Hilang', dll
            $table->text('catatan')->nullable();
            $table->timestamp('checked_at');
            $table->foreignId('checked_by')->constrained('users'); // atau admin table kamu
            $table->timestamps();
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('stok_opnames');
    }
};
