<?php

namespace App\Http\Controllers;

use App\Models\Kurir;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class KurirController extends Controller
{
    // Hanya admin yang bisa lihat semua kurir
    public function index()
    {
        $kurirs = Kurir::with('user')->get();
        return response()->json($kurirs);
    }

    // Hanya admin yang bisa lihat 1 kurir by id
    public function show($id)
    {
        $kurir = Kurir::with('user')->find($id);
        if (!$kurir) {
            return response()->json(['message' => 'Kurir not found'], 404);
        }
        return response()->json($kurir);
    }

    // Kurir login bisa lihat profilnya sendiri
    public function me()
    {
        $user = Auth::user();

        // Log untuk debugging
        Log::info('Kurir me endpoint accessed', [
            'user_id' => $user ? $user->id : 'null',
            'user_role' => $user ? $user->role : 'null'
        ]);

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if ($user->role !== 'kurir') {
            Log::warning('User role mismatch', [
                'expected' => 'kurir',
                'actual' => $user->role
            ]);
            return response()->json(['message' => 'Forbidden - Role mismatch'], 403);
        }

        $kurir = Kurir::with('user')->where('user_id', $user->id)->first();

        return response()->json([
            'user' => $user,
            'kurir' => $kurir
        ]);
    }

    // Kurir isi biodata sendiri setelah register
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'no_hp' => 'required|string|max:20',
            'current_password' => 'nullable|string',
            'password' => 'nullable|string|min:6|confirmed',
        ]);

        // --- Update ke tabel users ---
        $user->name = $request->name;
        $user->email = $request->email;

        // Jika ganti password
        if ($request->filled('password')) {
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'message' => 'Password saat ini salah'
                ], 400);
            }

            $user->password = Hash::make($request->password);
        }

        $user->save();

        // --- Update atau create kurir ---
        $kurir = Kurir::updateOrCreate(
            ['user_id' => $user->id],
            ['no_hp' => $request->no_hp]
        );

        return response()->json([
            'message' => 'Profil berhasil diperbarui',
            'user' => $user,
            'kurir' => $kurir
        ]);
    }

    // Admin bisa hapus kurir (beserta akun user-nya)
    public function destroy($id)
    {
        $kurir = Kurir::with('user')->find($id);
        if (!$kurir) {
            return response()->json(['message' => 'Kurir not found'], 404);
        }

        $kurir->delete();
        $kurir->user->delete();

        return response()->json(['message' => 'Kurir and user deleted successfully']);
    }
}
