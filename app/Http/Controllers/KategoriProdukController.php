<?php

namespace App\Http\Controllers;

use App\Models\KategoriProduk;
use Illuminate\Http\Request;

class KategoriProdukController extends Controller
{
    /**
     * GET /api/kategori
     * Menampilkan semua kategori produk
     */
    public function index()
    {
        $kategori = KategoriProduk::all();
        return response()->json($kategori);
    }

    /**
     * GET /api/kategori/{id}
     * Menampilkan detail satu kategori
     */
    public function show($id)
    {
        $kategori = KategoriProduk::find($id);

        if (!$kategori) {
            return response()->json(['message' => 'Kategori tidak ditemukan'], 404);
        }

        return response()->json($kategori);
    }

    /**
     * POST /api/kategori
     * Menambahkan kategori baru
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'nama_kategori' => 'required|string|max:255',
        ]);

        $kategori = KategoriProduk::create($data);

        return response()->json([
            'message' => 'Kategori berhasil ditambahkan',
            'kategori' => $kategori
        ], 201);
    }

    /**
     * PUT /api/kategori/{id}
     * Mengedit kategori
     */
    public function update(Request $request, $id)
    {
        $kategori = KategoriProduk::find($id);

        if (!$kategori) {
            return response()->json(['message' => 'Kategori tidak ditemukan'], 404);
        }

        $data = $request->validate([
            'nama_kategori' => 'required|string|max:255',
        ]);

        $kategori->update($data);

        return response()->json([
            'message' => 'Kategori berhasil diperbarui',
            'kategori' => $kategori
        ]);
    }

    /**
     * DELETE /api/kategori/{id}
     * Menghapus kategori (pastikan tidak dipakai produk lain)
     */
    public function destroy($id)
    {
        $kategori = KategoriProduk::find($id);

        if (!$kategori) {
            return response()->json(['message' => 'Kategori tidak ditemukan'], 404);
        }

        $kategori->delete();

        return response()->json(['message' => 'Kategori berhasil dihapus']);
    }
}
