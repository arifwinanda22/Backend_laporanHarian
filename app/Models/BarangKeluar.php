<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BarangKeluar extends Model
{
    // Nama tabel opsional jika sesuai standar (barang_keluars), tapi boleh tetap ditulis biar jelas
    protected $table = 'barang_keluars';

    // PENGGANTI FILLABLE:
    // Guarded ['id'] artinya: "Lindungi ID, sisanya boleh diisi massal".
    // Ini lebih simpel karena Anda tidak perlu mendaftar nama kolom satu per satu.
    protected $guarded = ['id'];

    // Relasi ke tabel Barang DIHAPUS sesuai permintaan Anda.
    // (Artinya sistem ini sekarang berdiri sendiri / Free Text tanpa memotong stok otomatis via relasi)
}