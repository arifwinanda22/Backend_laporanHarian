<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StokOpnameAsetLog extends Model
{
    protected $table = 'stok_opname_aset_logs';

    protected $fillable = [
        'aset_id',
        'status_fisik',
        'catatan',
        'checked_at',
        'checked_by',
    ];

    public function aset()
    {
        return $this->belongsTo(Aset::class, 'aset_id');
    }

    public function checker()
    {
        // sesuaikan kalau nama model user kamu bukan App\Models\User
        return $this->belongsTo(\App\Models\User::class, 'checked_by');
    }
}
