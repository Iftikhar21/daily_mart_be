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
        $favorites = $user->favorites()->with('branch')->get();

        return response()->json($favorites);
    }
}
