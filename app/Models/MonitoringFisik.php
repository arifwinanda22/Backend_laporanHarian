<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonitoringFisik extends Model
{
    use HasFactory;
    
    // Ini penting agar bisa diisi massal lewat createMany()
    protected $guarded = ['id'];
}
