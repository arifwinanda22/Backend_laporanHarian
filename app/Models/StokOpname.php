<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StokOpname extends Model
{
    protected $fillable = [
        'aset_id',
        'status_fisik',
        'catatan',
        'checked_at',
        'checked_by',
    ];

    protected $casts = [
        'checked_at' => 'datetime',
    ];

    public function aset(): BelongsTo
    {
        return $this->belongsTo(Aset::class, 'aset_id');
    }

    public function checker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }
}