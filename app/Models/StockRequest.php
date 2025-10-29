<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'petugas_id',
        'product_id',
        'qty_request',
        'status',
        'keterangan',
    ];

    // Relasi ke branch
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    // Relasi ke petugas
    public function petugas()
    {
        return $this->belongsTo(Petugas::class);
    }

    // Relasi ke product
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
