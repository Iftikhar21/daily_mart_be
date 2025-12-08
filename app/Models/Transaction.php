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
        'delivery_status',
        'kurir_id',
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
        return $this->belongsTo(Pelanggan::class, 'pelanggan_id');
    }

    // 🔗 Relasi ke petugas
    public function petugas()
    {
        return $this->belongsTo(Petugas::class, 'petugas_id');
    }

    // 🔗 Relasi ke kurir
    public function kurir()
    {
        return $this->belongsTo(Kurir::class, 'kurir_id');
    }

    // 🔗 Relasi ke detail transaksi
    public function details()
    {
        return $this->hasMany(TransactionDetail::class);
    }

    public function deliveryUpdates()
    {
        return $this->hasMany(DeliveryUpdate::class);
    }
}
