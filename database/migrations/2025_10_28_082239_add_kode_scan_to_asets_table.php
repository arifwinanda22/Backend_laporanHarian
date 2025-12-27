<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asets', function (Blueprint $table) {
            // kolom untuk menyimpan kode barcode operasional (id9 kamu)
            // nullable dulu supaya migrate tidak error
            $table->string('kode_scan', 20)->nullable()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('asets', function (Blueprint $table) {
            $table->dropColumn('kode_scan');
        });
    }
};
