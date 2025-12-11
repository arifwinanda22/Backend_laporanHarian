<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Cek apakah admin sudah ada agar tidak duplikat
        if (!User::where('email', 'admin@sanditel.com')->exists()) {
            User::create([
                'name' => 'Super Admin',
                'email' => 'admin@sanditel.com', // Email untuk Login
                'password' => Hash::make('password123'), // Password untuk Login
                'role' => 'admin', // Role dikunci sebagai admin
                'email_verified_at' => now(),
            ]);
        }
    }
}