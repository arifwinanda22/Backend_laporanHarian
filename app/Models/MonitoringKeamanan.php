<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MonitoringKeamanan extends Model
{
    use HasFactory;
    
    // Ini penting agar bisa diisi massal lewat createMany()
    protected $guarded = ['id'];
}
