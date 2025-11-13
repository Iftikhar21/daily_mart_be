<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FavoritesProduct extends Model
{
    protected $table = 'favorites_products';

    protected $fillable = [
        'user_id',
        'product_id',
    ];
}
