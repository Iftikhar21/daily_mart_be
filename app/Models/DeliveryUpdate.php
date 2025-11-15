<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DeliveryUpdate extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'kurir_id',
        'status_message',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function kurir()
    {
        return $this->belongsTo(Kurir::class);
    }
}
