<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * GET /api/products
     * Menampilkan semua produk beserta cabang & kategori
     */
    public function index()
    {
        $products = Product::with(['branch', 'kategori'])->get();

        $products->map(function ($product) {
            $product->gambar_url = $product->gambar
                ? asset('storage/' . $product->gambar)
                : asset('images/no-image.png');
            return $product;
        });

        return response()->json($products);
    }

    /**
     * GET /api/products/{id}
     */
    public function show($id)
    {
        $product = Product::with(['branch', 'kategori'])->find($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $product->gambar_url = $product->gambar
            ? asset('storage/' . $product->gambar)
            : asset('images/no-image.png');

        return response()->json($product);
    }

    public function getByBranch($branch_id)
    {
        $products = Product::with(['branch', 'kategori', 'stocks'])
            ->where('branch_id', $branch_id)
            ->get();

        if ($products->isEmpty()) {
            return response()->json([
                'message' => 'Produk pada cabang ini tidak ditemukan'
            ], 404);
        }

        // Tambahkan gambar_url untuk setiap produk
        $products->map(function ($product) {
            $product->gambar_url = $product->gambar
                ? asset('storage/' . $product->gambar)
                : asset('images/no-image.png');

            return $product;
        });

        return response()->json($products);
    }

    /**
     * POST /api/products
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'branch_id'   => 'required|exists:branches,id',
            'kategori_id' => 'required|exists:kategori_produk,id',
            'kode_produk' => 'required|string|max:50',
            'nama_produk' => 'required|string|max:255',
            'satuan'      => 'nullable|string|max:20',
            'harga'       => 'required|numeric|min:0',
            'gambar'      => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->hasFile('gambar')) {
            $data['gambar'] = $request->file('gambar')->store('products', 'public');
        }

        $product = Product::create($data);

        // Tambahkan stok awal
        Stock::create([
            'branch_id'  => $product->branch_id,
            'product_id' => $product->id,
            'qty'        => 0, // default
        ]);

        $product->gambar_url = $product->gambar
            ? asset('storage/' . $product->gambar)
            : asset('images/no-image.png');

        return response()->json([
            'message' => 'Produk berhasil ditambahkan',
            'product' => $product->load(['branch', 'kategori'])
        ], 201);
    }

    /**
     * PUT /api/products/{id}
     */
    public function update(Request $request, $id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $data = $request->validate([
            'branch_id'   => 'sometimes|exists:branches,id',
            'kategori_id' => 'sometimes|exists:kategori_produk,id',
            'kode_produk' => 'sometimes|string|max:50',
            'nama_produk' => 'sometimes|string|max:255',
            'satuan'      => 'nullable|string|max:20',
            'harga'       => 'sometimes|numeric|min:0',
            'gambar'      => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Ganti gambar kalau upload baru
        if ($request->hasFile('gambar')) {
            if ($product->gambar) {
                Storage::disk('public')->delete($product->gambar);
            }
            $data['gambar'] = $request->file('gambar')->store('products', 'public');
        }

        $product->update($data);

        $product->gambar_url = $product->gambar
            ? asset('storage/' . $product->gambar)
            : asset('images/no-image.png');

        return response()->json([
            'message' => 'Produk berhasil diperbarui',
            'product' => $product->load(['branch', 'kategori'])
        ]);
    }

    /**
     * DELETE /api/products/{id}
     */
    public function destroy($id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        if ($product->gambar) {
            Storage::disk('public')->delete($product->gambar);
        }

        $product->delete();

        return response()->json(['message' => 'Produk berhasil dihapus']);
    }
}
