<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Admin;
use App\Models\Kurir;
use App\Models\Petugas;
use App\Models\Pelanggan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // REGISTER
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
        ]);

        // Semua yang register lewat mobile otomatis jadi user
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'role' => 'user',
        ]);

        return response()->json([
            'message' => 'Registrasi berhasil!',
            'user' => $user
        ]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        // Hapus token lama
        $user->tokens()->delete();

        // Buat token baru
        $token = $user->createToken('auth_token')->plainTextToken;

        // ✅ Jika role user dan belum punya data pelanggan, buat otomatis
        if ($user->role === 'user' && !Pelanggan::where('user_id', $user->id)->exists()) {
            Pelanggan::create([
                'user_id' => $user->id,
                'alamat' => null,
                'no_hp' => null,
                'is_guest' => false,
            ]);
        }

        return response()->json([
            'message' => 'Login berhasil!',
            'user' => $user,
            'token' => $token,
        ]);
    }

    // LOGOUT
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logout berhasil!']);
    }

    // PROFILE
    public function profile(Request $request)
    {
        return response()->json($request->user());
    }

    public function adminCreateUser(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'role'     => 'nullable|string|in:admin,petugas,kurir,user',
        ]);

        // Jika role tidak dikirim → default user
        $role = $validated['role'] ?? 'user';

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => bcrypt($validated['password']),
            'role'     => $role,
        ]);

        return response()->json([
            'message' => 'Akun berhasil dibuat oleh admin',
            'user' => $user,
        ]);
    }

    public function adminUpdateUser(Request $request, $id)
    {
        $user = User::find($id);

        if (! $user) {
            return response()->json(['message' => 'User tidak ditemukan'], 404);
        }

        $validated = $request->validate([
            'name'     => 'sometimes|string|max:255',
            'email'    => 'sometimes|string|email|max:255|unique:users,email,' . $id,
            'password' => 'sometimes|string|min:6',
            'role'     => 'nullable|string|in:admin,petugas,kurir,user',
        ]);

        // Update basic fields
        if (isset($validated['name'])) {
            $user->name = $validated['name'];
        }
        if (isset($validated['email'])) {
            $user->email = $validated['email'];
        }
        if (isset($validated['password'])) {
            $user->password = bcrypt($validated['password']);
        }

        // Role (opsional)
        if (array_key_exists('role', $validated)) {
            $user->role = $validated['role'] ?? $user->role;
        }

        $user->save();

        return response()->json([
            'message' => 'Akun berhasil diperbarui',
            'user' => $user,
        ]);
    }

    public function adminDeleteUser($id)
    {
        $user = User::find($id);

        if (! $user) {
            return response()->json(['message' => 'User tidak ditemukan'], 404);
        }

        // Hapus semua token akses user
        $user->tokens()->delete();

        // Hapus user
        $user->delete();

        return response()->json(['message' => 'Akun berhasil dihapus']);
    }
}
