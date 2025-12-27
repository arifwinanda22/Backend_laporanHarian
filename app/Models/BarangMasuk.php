<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BarangMasuk extends Model
{
    protected $table = 'barang_masuk';
    
    protected $fillable = [
        'no_transaksi', 
        'tgl_masuk', 
        'barang_id', 
        'kategori', 
        'jumlah_masuk', 
        'user'
    ];

    // Relasi untuk mengambil nama barang
    public function barang()
    {
        return $this->belongsTo(Barang::class, 'barang_id');
    }
}