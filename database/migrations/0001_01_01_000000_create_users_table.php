<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   // database/migrations/xxxx_xx_xx_create_users_table.php

public function up(): void
{
    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('name'); // Username
        $table->string('email')->unique();
        $table->string('phone')->nullable(); // Tambahan untuk Contact Number
        $table->string('password');
        $table->string('role')->default('user'); // Default user biasa
        $table->timestamp('email_verified_at')->nullable();
        $table->rememberToken();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
