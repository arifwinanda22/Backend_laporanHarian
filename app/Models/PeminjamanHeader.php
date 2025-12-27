<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class PeminjamanHeader extends Model
{
    protected $guarded = ['id'];

    public function details()
    {
        return $this->hasMany(PeminjamanDetail::class, 'peminjaman_header_id');
    }
}