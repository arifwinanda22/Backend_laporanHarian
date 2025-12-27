<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Barang extends Model
{
    use HasFactory;

    protected $table = 'barangs';

    // Daftarkan kolom agar bisa diisi (Mass Assignment)
    protected $fillable = [
        'kode_barang',
        'nama_barang',
        'jenis_barang',
        'satuan',
        'stok',
    ];
}