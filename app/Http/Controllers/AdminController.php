<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    // GET /api/admins
    public function index()
    {
        $admins = Admin::with('user')->get();
        return response()->json($admins);
    }

    // GET /api/admins/{id}
    public function show($id)
    {
        $admin = Admin::with('user')->find($id);
        if (!$admin) {
            return response()->json(['message' => 'Admin not found'], 404);
        }
        return response()->json($admin);
    }

    // POST /api/admins
    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => 'admin',
        ]);

        $admin = Admin::create([
            'user_id' => $user->id,
        ]);

        return response()->json(['user' => $user, 'admin' => $admin], 201);
    }

    // PUT /api/admins/{id}
    public function update(Request $request, $id)
    {
        $admin = Admin::with('user')->find($id);
        if (!$admin) {
            return response()->json(['message' => 'Admin not found'], 404);
        }

        $request->validate([
            'name'     => 'sometimes|string|max:255',
            'email'    => 'sometimes|email|unique:users,email,' . $admin->user_id,
            'password' => 'sometimes|string|min:6',
        ]);

        $userData = [];
        if ($request->has('name')) $userData['name'] = $request->name;
        if ($request->has('email')) $userData['email'] = $request->email;
        if ($request->has('password')) $userData['password'] = Hash::make($request->password);

        if (!empty($userData)) {
            $admin->user->update($userData);
        }

        return response()->json(['user' => $admin->user, 'admin' => $admin]);
    }

    // DELETE /api/admins/{id}
    public function destroy($id)
    {
        $admin = Admin::with('user')->find($id);
        if (!$admin) {
            return response()->json(['message' => 'Admin not found'], 404);
        }

        $admin->delete();
        $admin->user->delete();

        return response()->json(['message' => 'Admin and associated user deleted']);
    }
}
