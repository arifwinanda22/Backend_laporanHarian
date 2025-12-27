<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kategori extends Model
{
    use HasFactory;

    // Kolom yang tidak boleh diisi massal
    protected $guarded = ['id'];

    // ✅ Nama tabel eksplisit (opsional, tapi baik jika kamu konsisten)
    protected $table = 'kategoris';

    /**
     * Relasi ke BarangMasuk (satu kategori bisa punya banyak barang masuk)
     */
    public function barangMasuks(): HasMany
    {
        return $this->hasMany(BarangMasuk::class, 'kategori_id', 'id');
    }

    /**
     * Akses nama kategori dengan format kapital di awal huruf
     */
    public function getNamaKategoriAttribute($value): string
    {
        return ucfirst($value);
    }

    /**
     * Scope untuk pencarian (membantu select di Filament)
     */
    public function scopeSearch($query, $term)
    {
        return $query->where('nama_kategori', 'like', "%{$term}%");
    }
}
