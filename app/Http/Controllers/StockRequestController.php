<?php

namespace App\Http\Controllers;

use App\Models\StockRequest;
use App\Models\Stock;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StockRequestController extends Controller
{
    /**
     * PETUGAS: Buat permintaan stok
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'product_id' => 'required|exists:products,id',
            'qty_request' => 'required|integer|min:1',
            'keterangan' => 'nullable|string|max:255',
        ]);

        // 🔹 Ambil data petugas (biar tau dia dari branch mana)
        $petugas = \App\Models\Petugas::where('user_id', $user->id)->first();

        if (!$petugas) {
            return response()->json([
                'message' => 'Data petugas tidak ditemukan.'
            ], 404);
        }

        // 🔹 Buat request stok baru
        $stockRequest = StockRequest::create([
            'branch_id'   => $petugas->branch_id, // ✅ otomatis ambil dari cabang petugas
            'petugas_id'  => $petugas->id,
            'product_id'  => $request->product_id,
            'qty_request' => $request->qty_request,
            'keterangan'  => $request->keterangan,
            'status'      => 'pending',
        ]);

        return response()->json([
            'message' => 'Permintaan stok berhasil dibuat.',
            'data'    => $stockRequest
        ], 201);
    }

    /**
     * PETUGAS: Lihat permintaan miliknya sendiri
     */
    public function myRequests()
    {
        $petugas = Auth::user();

        $requests = StockRequest::with(['product'])
            ->where('petugas_id', $petugas->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json($requests);
    }

    /**
     * ADMIN: Lihat semua permintaan stok
     */
    public function index()
    {
        $requests = StockRequest::with(['branch', 'product', 'petugas'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json($requests);
    }

    /**
     * ADMIN: Lihat detail request
     */
    public function show($id)
    {
        $stockRequest = StockRequest::with(['branch', 'product', 'petugas'])->findOrFail($id);
        return response()->json($stockRequest);
    }

    /**
     * ADMIN: Approve permintaan stok
     */
    public function approve(Request $request, $id)
    {
        $stockRequest = StockRequest::findOrFail($id);

        if ($stockRequest->status !== 'pending') {
            return response()->json(['message' => 'Permintaan ini sudah diproses.'], 400);
        }

        // Tambahkan stok di tabel `stocks`
        $stock = Stock::firstOrCreate(
            [
                'branch_id' => $stockRequest->branch_id,
                'product_id' => $stockRequest->product_id
            ],
            ['qty' => 0]
        );

        $stock->qty += $stockRequest->qty_request;
        $stock->save();

        // Update status permintaan
        $stockRequest->status = 'approved';
        $stockRequest->save();

        return response()->json([
            'message' => 'Permintaan stok disetujui dan stok berhasil ditambahkan.',
            'data' => $stockRequest
        ]);
    }

    /**
     * ADMIN: Reject permintaan stok
     */
    public function reject($id, Request $request)
    {
        $request->validate([
            'reason' => 'required|string'
        ]);

        $stockRequest = StockRequest::find($id);

        if (!$stockRequest) {
            return response()->json(['message' => 'Request stok tidak ditemukan.'], 404);
        }

        if ($stockRequest->status !== 'pending') {
            return response()->json(['message' => 'Hanya request yang masih pending yang dapat ditolak.'], 400);
        }

        $stockRequest->update([
            'status' => 'rejected',
            'keterangan' => $request->reason
        ]);

        return response()->json([
            'message' => 'Request stok berhasil ditolak.',
            'data' => $stockRequest
        ], 200);
    }
}