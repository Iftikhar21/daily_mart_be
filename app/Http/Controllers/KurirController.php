<?php

namespace App\Http\Controllers;

use App\Models\Kurir;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

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
            'no_hp' => 'nullable|string|max:20',
            'alamat' => 'nullable|string|max:255',
            'kendaraan' => 'nullable|string|max:100',
        ]);

        $kurir = Kurir::updateOrCreate(
            ['user_id' => $user->id],
            [
                'no_hp' => $request->no_hp,
                'alamat' => $request->alamat,
                'kendaraan' => $request->kendaraan,
            ]
        );

        return response()->json([
            'message' => 'Profile updated successfully',
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
