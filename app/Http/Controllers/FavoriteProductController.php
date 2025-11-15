<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class FavoriteProductController extends Controller
{
    public function toggle(Request $request, $productId)
    {
        $user = $request->user();

        $product = Product::findOrFail($productId);

        // cek apakah produk sudah difavoritkan
        $isFavorited = $user->favorites()->where('product_id', $productId)->exists();

        if ($isFavorited) {
            // kalau sudah ada → hapus dari favorit
            $user->favorites()->detach($productId);
            return response()->json(['message' => 'Produk dihapus dari favorit']);
        } else {
            // kalau belum → tambahkan ke favorit
            $user->favorites()->attach($productId);
            return response()->json(['message' => 'Produk ditambahkan ke favorit']);
        }
    }

    public function list(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // ambil semua produk yang difavoritkan user
        $favorites = $user->favorites()
            ->with(['branch', 'kategori'])
            ->get()
            ->map(function ($product) {
                $product->gambar_url = $product->gambar
                    ? asset('storage/' . $product->gambar)
                    : asset('images/no-image.png');
                return $product;
            });

        return response()->json($favorites);
    }
}
