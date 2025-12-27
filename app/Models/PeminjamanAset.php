<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PeminjamanAset extends Model
{
    use HasFactory;

    protected $table = 'peminjaman_aset';

    protected $fillable = [
        'aset_id',
        'nama_peminjam', // <-- Sesuaikan nama kolom DB
        'tanggal_pinjam',
        'tanggal_kembali',
        'status',        // Dipinjam / Dikembalikan
        'user_id'        // <-- PENTING: Relasi ke Admin
    ];

    public function aset()
    {
        return $this->belongsTo(Aset::class, 'aset_id');
    }

    // Relasi ke Admin yang input
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}