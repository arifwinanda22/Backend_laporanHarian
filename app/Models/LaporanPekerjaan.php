<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaporanPekerjaan extends Model
{
    use HasFactory;

    protected $table = 'laporan_pekerjaans';

    protected $fillable = [
        'user_id',
        'id_pekerjaan',  // Tambahkan ini
        'tanggal',
        'jenis_pekerjaan',
        'bagian',
        'petugas',
        'status',
        'deskripsi',
        'lampiran',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'lampiran' => 'array', // Mengubah JSON di Database menjadi Array di PHP
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}