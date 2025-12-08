<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Relasi ke admin
    public function admin()
    {
        return $this->hasOne(Admin::class, 'user_id');
    }

    // app/Models/User.php
    public function pelanggan()
    {
        return $this->hasOne(Pelanggan::class, 'user_id');
    }

    public function petugas()
    {
        return $this->hasOne(Petugas::class, 'user_id');
    }

    public function kurir()
    {
        return $this->hasOne(Kurir::class, 'user_id');
    }

    public function favorites()
    {
        return $this->belongsToMany(Product::class, 'favorites_products', 'user_id', 'product_id')
            ->withTimestamps();
    }

    public function transaksiSebagaiPelanggan()
    {
        return $this->hasMany(Transaction::class, 'pelanggan_id');
    }

    public function transaksiSebagaiPetugas()
    {
        return $this->hasMany(Transaction::class, 'petugas_id');
    }
}
