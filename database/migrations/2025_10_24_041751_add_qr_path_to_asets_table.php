<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('asets', function (Blueprint $table) {
            if (! Schema::hasColumn('asets', 'qr_path')) {
                // buat kolom
                $col = $table->string('qr_path')->nullable();

                // kalau ada kolom 'barcode', taruh setelah itu.
                if (Schema::hasColumn('asets', 'barcode')) {
                    $col->after('barcode');
                // kalau tidak ada 'barcode' tapi ada 'code', taruh setelah 'code'.
                } elseif (Schema::hasColumn('asets', 'code')) {
                    $col->after('code');
                }
                // kalau dua-duanya tidak ada, biarkan tanpa ->after() (otomatis di akhir).
            }
        });
    }

    public function down(): void
    {
        Schema::table('asets', function (Blueprint $table) {
            if (Schema::hasColumn('asets', 'qr_path')) {
                $table->dropColumn('qr_path');
            }
        });
    }
};
