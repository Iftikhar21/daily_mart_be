<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Petugas;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class PetugasController extends Controller
{
    // GET /api/petugas
    public function index()
    {
        $petugas = Petugas::with(['user', 'branch'])->get();
        return response()->json($petugas);
    }

    // GET /api/petugas/{id}
    public function show($id)
    {
        $petugas = Petugas::with(['user', 'branch'])->find($id);
        if (!$petugas) return response()->json(['message' => 'Petugas not found'], 404);
        return response()->json($petugas);
    }

    // POST /api/petugas
    public function store(Request $request)
    {
        // Validasi input user + petugas sekaligus
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin,petugas,user',
            'branch_id' => 'required_if:role,petugas|exists:branches,id',
            'no_hp' => 'nullable|string|max:20',
        ]);

        // Buat user sesuai input role
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        $petugas = null;

        // Jika role = petugas, buat petugas
        if ($request->role === 'petugas') {
            $petugas = Petugas::create([
                'user_id' => $user->id,
                'branch_id' => $request->branch_id,
                'no_hp' => $request->no_hp,
            ]);
        }

        return response()->json([
            'user' => $user,
            'petugas' => $petugas
        ], 201);
    }

    // PUT /api/petugas/{id}
    public function update(Request $request, $id)
    {
        $petugas = Petugas::with('user')->find($id);
        if (!$petugas) return response()->json(['message' => 'Petugas not found'], 404);

        // Validasi update
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $petugas->user_id,
            'password' => 'sometimes|string|min:6',
            'branch_id' => 'sometimes|exists:branches,id',
            'no_hp' => 'nullable|string|max:20',
        ]);

        // Update user jika ada perubahan
        $userData = [];
        if ($request->has('name')) $userData['name'] = $request->name;
        if ($request->has('email')) $userData['email'] = $request->email;
        if ($request->has('password')) $userData['password'] = Hash::make($request->password);

        if (!empty($userData)) {
            $petugas->user->update($userData);
        }

        // Update petugas jika ada
        $petugasData = $request->only(['branch_id', 'no_hp']);
        if (!empty($petugasData)) {
            $petugas->update($petugasData);
        }

        return response()->json([
            'user' => $petugas->user,
            'petugas' => $petugas
        ]);
    }

    // DELETE /api/petugas/{id}
    public function destroy($id)
    {
        $petugas = Petugas::with('user')->find($id);
        if (!$petugas) return response()->json(['message' => 'Petugas not found'], 404);

        // Hapus petugas & user sekaligus
        $petugas->delete();
        $petugas->user->delete();

        return response()->json(['message' => 'Petugas and associated user deleted']);
    }
}
