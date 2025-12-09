<?php

namespace App\Http\Controllers;

use App\Models\Petugas;
use Illuminate\Http\Request;

class PetugasController extends Controller
{
    // 🔹 List semua petugas (khusus admin)
    public function index()
    {
        $petugas = Petugas::with(['user', 'branch'])->get();
        return response()->json($petugas);
    }

    // 🔹 Lihat biodata petugas yang sedang login
    public function show(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'petugas') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Ambil data petugas jika ada
        $petugas = Petugas::with('branch')->where('user_id', $user->id)->first();

        if (!$petugas) {
            // Jika petugas belum ada, tetap kirim data user
            return response()->json([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'branch_id' => null,
                'no_hp' => null,
                'branch' => null,
            ]);
        }

        // Jika petugas ada, kirim data petugas + relasi
        return response()->json($petugas);
    }

    // 🔹 Isi / update biodata petugas (by user login)
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'petugas') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'no_hp'     => 'nullable|string|max:20',
        ]);

        $petugas = Petugas::updateOrCreate(
            ['user_id' => $user->id],
            $validated
        );

        return response()->json([
            'message' => 'Biodata petugas berhasil disimpan',
            'petugas' => $petugas,
        ]);
    }

    // 🔹 Admin lihat detail petugas berdasarkan ID
    public function showById($id)
    {
        $petugas = Petugas::with(['user', 'branch'])->find($id);

        if (!$petugas) {
            return response()->json(['message' => 'Petugas tidak ditemukan'], 404);
        }

        return response()->json($petugas);
    }
}