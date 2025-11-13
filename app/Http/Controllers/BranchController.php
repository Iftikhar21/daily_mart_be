<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Branch;

class BranchController extends Controller
{
    // GET /api/branches
    public function index()
    {
        return response()->json(Branch::all());
    }

    // GET /api/branches/{id}
    public function show($id)
    {
        $branch = Branch::find($id);
        if (!$branch) {
            return response()->json(['message' => 'Cabang tidak ditemukan'], 404);
        }
        return response()->json($branch);
    }

    // POST /api/branches
    public function store(Request $request)
    {
        $request->validate([
            'nama_cabang' => 'required|string|max:255',
            'alamat' => 'nullable|string',
            'no_telp' => 'nullable|string|max:20',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $branch = Branch::create($request->all());
        return response()->json($branch, 201);
    }

    // PUT /api/branches/{id}
    public function update(Request $request, $id)
    {
        $branch = Branch::find($id);
        if (!$branch) {
            return response()->json(['message' => 'Cabang tidak ditemukan'], 404);
        }

        $request->validate([
            'nama_cabang' => 'required|string|max:255',
            'alamat' => 'nullable|string',
            'no_telp' => 'nullable|string|max:20',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $branch->update($request->all());
        return response()->json($branch);
    }

    // DELETE /api/branches/{id}
    public function destroy($id)
    {
        $branch = Branch::find($id);
        if (!$branch) {
            return response()->json(['message' => 'Cabang tidak ditemukan'], 404);
        }

        $branch->delete();
        return response()->json(['message' => 'Cabang berhasil dihapus']);
    }
}
