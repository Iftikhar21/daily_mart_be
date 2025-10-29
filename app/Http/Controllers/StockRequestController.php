<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StockRequest;
use Illuminate\Support\Facades\Auth;

class StockRequestController extends Controller
{
    // GET /api/stock-requests
    public function index()
    {
        $user = Auth::user();
        // Kalau admin, lihat semua, kalau user, lihat miliknya sendiri
        if ($user->role === 'admin') {
            $requests = StockRequest::with(['branch', 'petugas', 'product'])->get();
        } else {
            $requests = StockRequest::with(['branch', 'petugas', 'product'])
                ->where('petugas_id', $user->id)
                ->get();
        }

        return response()->json($requests);
    }

    // GET /api/stock-requests/{id}
    public function show($id)
    {
        $request = StockRequest::with(['branch', 'petugas', 'product'])->find($id);
        if (!$request) return response()->json(['message' => 'Request not found'], 404);
        return response()->json($request);
    }

    // POST /api/stock-requests
    public function store(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'product_id' => 'required|exists:products,id',
            'qty_request' => 'required|integer|min:1',
            'keterangan' => 'nullable|string',
        ]);

        $stockRequest = StockRequest::create([
            'branch_id' => $request->branch_id,
            'petugas_id' => Auth::id(),
            'product_id' => $request->product_id,
            'qty_request' => $request->qty_request,
            'keterangan' => $request->keterangan,
        ]);

        return response()->json($stockRequest, 201);
    }

    // PUT /api/stock-requests/{id} (update qty/keterangan)
    public function update(Request $request, $id)
    {
        $stockRequest = StockRequest::find($id);
        if (!$stockRequest) return response()->json(['message' => 'Request not found'], 404);

        // User hanya bisa update request sendiri dan status masih pending
        if ($stockRequest->petugas_id != Auth::id() || $stockRequest->status != 'pending') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'qty_request' => 'sometimes|integer|min:1',
            'keterangan' => 'nullable|string',
        ]);

        $stockRequest->update($request->only(['qty_request', 'keterangan']));

        return response()->json($stockRequest);
    }

    // DELETE /api/stock-requests/{id}
    public function destroy($id)
    {
        $stockRequest = StockRequest::find($id);
        if (!$stockRequest) return response()->json(['message' => 'Request not found'], 404);

        // Hanya user pemilik atau admin bisa hapus
        $user = Auth::user();
        if ($stockRequest->petugas_id != $user->id && $user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $stockRequest->delete();
        return response()->json(['message' => 'Request deleted']);
    }

    // PATCH /api/stock-requests/{id}/approve
    public function approve($id)
    {
        $user = Auth::user();
        if ($user->role !== 'admin') return response()->json(['message' => 'Unauthorized'], 403);

        $request = StockRequest::find($id);
        if (!$request) return response()->json(['message' => 'Request not found'], 404);

        $request->status = 'approved';
        $request->save();

        return response()->json($request);
    }

    // PATCH /api/stock-requests/{id}/reject
    public function reject($id)
    {
        $user = Auth::user();
        if ($user->role !== 'admin') return response()->json(['message' => 'Unauthorized'], 403);

        $request = StockRequest::find($id);
        if (!$request) return response()->json(['message' => 'Request not found'], 404);

        $request->status = 'rejected';
        $request->save();

        return response()->json($request);
    }
}
