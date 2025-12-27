<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Aset extends Model
{
    use HasFactory;

    protected $table = 'asets';

    // WAJIB ADA: Agar fungsi Aset::create() di controller bisa jalan
    protected $fillable = [
        'kode_aset',           
        'nama_aset',           
        'kategori',            
        'status',              
        'jumlah',
        'tanggal_log_barcode'  
    ];
}