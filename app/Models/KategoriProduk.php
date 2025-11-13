<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KategoriProduk extends Model
{
    use HasFactory;

    protected $table = 'kategori_produk';

    protected $fillable = ['nama_kategori'];

    public function products()
    {
        return $this->hasMany(Product::class, 'kategori_id');
    }
}
