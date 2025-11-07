<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'pelanggan_id',
        'petugas_id',
        'branch_id',
        'is_online',
        'total',
        'payment_method',
        'status',
        'nama_pembeli',
        'alamat_pembeli',
    ];

    protected $casts = [
        'is_online' => 'boolean',
        'total' => 'decimal:2',
    ];

    // 🔗 Relasi ke cabang
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    // 🔗 Relasi ke pelanggan
    public function pelanggan()
    {
        return $this->belongsTo(User::class, 'pelanggan_id');
    }

    // 🔗 Relasi ke petugas
    public function petugas()
    {
        return $this->belongsTo(User::class, 'petugas_id');
    }

    // 🔗 Relasi ke detail transaksi
    public function details()
    {
        return $this->hasMany(TransactionDetail::class);
    }
}
