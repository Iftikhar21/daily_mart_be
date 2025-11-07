<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'kode_produk',
        'nama_produk',
        'satuan',
        'harga',
        'gambar',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function stocks()
    {
        return $this->hasMany(Stock::class);
    }

    public function transactionDetails()
    {
        return $this->hasMany(TransactionDetail::class);
    }
}
