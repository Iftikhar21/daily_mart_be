<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class PelangganProductController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $pelanggan = $user->pelanggan;

        if (!$pelanggan) {
            return response()->json(['message' => 'Data pelanggan tidak ditemukan.'], 404);
        }

        $branchId = $pelanggan->branch_id;

        if (!$branchId) {
            return response()->json(['message' => 'Branch pelanggan tidak ditemukan.'], 404);
        }

        // Ambil produk + total stok
        $products = Product::where('branch_id', $branchId)
            ->with(['branch', 'kategori', 'stocks'])
            ->withSum('stocks as stok', 'qty')
            ->get();

        $products->map(function ($product) {
            $product->gambar_url = $product->gambar
                ? asset('storage/' . $product->gambar)
                : asset('images/no-image.png');

            // stok dari withSum sudah otomatis, tinggal fallback 0
            $product->stok = $product->stok ?? 0;

            return $product;
        });

        return response()->json([
            'branch_id' => $branchId,
            'products' => $products,
        ]);
    }
}
