<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    // GET /api/products
    public function index()
    {
        $products = Product::with('branch')->get();

        // Tambahkan URL gambar agar bisa langsung diakses
        $products->map(function ($product) {
            if ($product->gambar) {
                $product->gambar_url = asset('storage/' . $product->gambar);
            } else {
                $product->gambar_url = asset('images/no-image.png'); // fallback kalau kosong
            }
            return $product;
        });

        return response()->json($products);
    }

    // GET /api/products/{id}
    public function show($id)
    {
        $product = Product::with('branch')->find($id);
        if (!$product) return response()->json(['message' => 'Product not found'], 404);

        if ($product->gambar) {
            $product->gambar_url = asset('storage/' . $product->gambar);
        } else {
            $product->gambar_url = asset('images/no-image.png');
        }

        return response()->json($product);
    }

    // POST /api/products
    public function store(Request $request)
    {
        $data = $request->validate([
            'branch_id'   => 'required|exists:branches,id',
            'kode_produk' => 'required|string|max:50',
            'nama_produk' => 'required|string|max:255',
            'satuan'      => 'nullable|string|max:20',
            'harga'       => 'required|numeric|min:0',
            'gambar'      => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // simpan gambar seperti di FooterController
        if ($request->hasFile('gambar')) {
            $data['gambar'] = $request->file('gambar')->store('products', 'public');
        }

        $product = Product::create($data);

        $product->gambar_url = $product->gambar
            ? asset('storage/' . $product->gambar)
            : asset('images/no-image.png');

        return response()->json($product, 201);
    }

    // PUT /api/products/{id}
    public function update(Request $request, $id)
    {
        $product = Product::find($id);
        if (!$product) return response()->json(['message' => 'Product not found'], 404);

        $data = $request->validate([
            'branch_id'   => 'sometimes|exists:branches,id',
            'kode_produk' => 'sometimes|string|max:50',
            'nama_produk' => 'sometimes|string|max:255',
            'satuan'      => 'nullable|string|max:20',
            'harga'       => 'sometimes|numeric|min:0',
            'gambar'      => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->hasFile('gambar')) {
            // hapus gambar lama
            if ($product->gambar) {
                Storage::disk('public')->delete($product->gambar);
            }
            // simpan baru
            $data['gambar'] = $request->file('gambar')->store('products', 'public');
        }

        $product->update($data);

        $product->gambar_url = $product->gambar
            ? asset('storage/' . $product->gambar)
            : asset('images/no-image.png');

        return response()->json($product);
    }

    // DELETE /api/products/{id}
    public function destroy($id)
    {
        $product = Product::find($id);
        if (!$product) return response()->json(['message' => 'Product not found'], 404);

        if ($product->gambar) {
            Storage::disk('public')->delete($product->gambar);
        }

        $product->delete();

        return response()->json(['message' => 'Product deleted']);
    }
}
