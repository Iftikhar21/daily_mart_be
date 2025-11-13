<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class PelangganProductController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        // Pastikan user login
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Ambil data pelanggan (relasi dari user)
        $pelanggan = $user->pelanggan;

        // Jika belum ada data pelanggan terkait user
        if (!$pelanggan) {
            return response()->json(['message' => 'Data pelanggan tidak ditemukan.'], 404);
        }

        // Ambil branch_id dari tabel pelanggan
        $branchId = $pelanggan->branch_id;

        // Kalau pelanggan belum terdaftar di cabang mana pun
        if (!$branchId) {
            return response()->json(['message' => 'Branch pelanggan tidak ditemukan.'], 404);
        }

        // Ambil produk hanya dari cabang pelanggan
        $products = Product::where('branch_id', $branchId)
            ->with(['branch', 'kategori'])
            ->get();

        // Tambahkan URL gambar supaya bisa diakses langsung
        $products->map(function ($product) {
            $product->gambar_url = $product->gambar
                ? asset('storage/' . $product->gambar)
                : asset('images/no-image.png');
            return $product;
        });

        return response()->json([
            'branch_id' => $branchId,
            'products' => $products,
        ]);
    }
}
