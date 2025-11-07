<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'product_id',
        'qty',
        'harga_satuan',
        'subtotal',
    ];

    // 🔗 Relasi ke transaksi
    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    // 🔗 Relasi ke produk
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
