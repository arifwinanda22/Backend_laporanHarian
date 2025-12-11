<?php

namespace App\Models;

// 1. TAMBAHKAN BARIS INI DI PALING ATAS (Di bawah namespace)
use Laravel\Sanctum\HasApiTokens;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    // 2. TAMBAHKAN 'HasApiTokens' DI DALAM SINI (Di awal koma)
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role', // Pastikan role juga ada di sini
    ];

    // ... sisa kode di bawah biarkan saja ...
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
}