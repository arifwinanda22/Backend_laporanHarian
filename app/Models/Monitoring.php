<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\MonitoringFisik;
use App\Models\MonitoringLayanan;
use App\Models\MonitoringKeamanan;
use App\Models\MonitoringAksesJaringan;
use App\Models\User;

class Monitoring extends Model
{
    use HasFactory;

    // Izinkan semua kolom diisi
    protected $guarded = ['id'];

    // PENTING: Ubah JSON jadi Array otomatis
    protected $casts = [
        'periode' => 'array',
        'lokasi' => 'array',
        'fisik' => 'array',
        'virtualisasi' => 'array',
        'keamanan' => 'array',
        'akses_jaringan' => 'array',
        'status_umum' => 'array',
        'lampiran' => 'array',
    ];

    // 3. Relasi ke User (Tetap sama)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // --- 4. RELASI KE TABEL ANAK (HAS MANY) ---
    // Ini menggantikan fungsi kolom JSON yang lama.
    
    public function fisiks()
    {
        // Artinya: Satu Monitoring punya BANYAK data Fisik
        return $this->hasMany(MonitoringFisik::class);
    }

    public function layanans()
    {
        return $this->hasMany(MonitoringLayanan::class);
    }

    public function keamanans()
    {
        return $this->hasMany(MonitoringKeamanan::class);
    }

    public function aksesJaringans()
    {
        // Pastikan nama Modelnya sesuai dengan file yang Anda buat
        return $this->hasMany(MonitoringAksesJaringan::class);
    }
    
}