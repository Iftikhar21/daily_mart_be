<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\TransactionDetail;

class TransactionController extends Controller
{
    public function storeOffline(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
            'payment_method' => 'required|in:cash,transfer',
        ]);

        $total = 0;
        foreach ($request->items as $item) {
            $product = Product::find($item['product_id']);
            $total += $product->harga * $item['qty'];
        }

        $transaction = Transaction::create([
            'branch_id' => $request->branch_id,
            'petugas_id' => auth()->id(),
            'is_online' => false,
            'total' => $total,
            'payment_method' => $request->payment_method,
            'status' => 'paid',
        ]);

        foreach ($request->items as $item) {
            $product = Product::find($item['product_id']);

            TransactionDetail::create([
                'transaction_id' => $transaction->id,
                'product_id' => $product->id,
                'qty' => $item['qty'],
                'harga_satuan' => $product->harga,
                'subtotal' => $product->harga * $item['qty'],
            ]);

            // Kurangi stok
            $stock = Stock::where('branch_id', $request->branch_id)
                ->where('product_id', $product->id)
                ->first();
            if ($stock) {
                $stock->decrement('qty', $item['qty']);
            }
        }

        return response()->json([
            'message' => 'Transaksi offline berhasil dibuat.',
            'data' => $transaction->load('details')
        ]);
    }

    public function storeOnline(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
            'nama_pembeli' => 'required|string',
            'alamat_pembeli' => 'required|string',
        ]);

        $total = 0;
        foreach ($request->items as $item) {
            $product = Product::find($item['product_id']);
            $total += $product->harga * $item['qty'];
        }

        $transaction = Transaction::create([
            'branch_id' => $request->branch_id,
            'pelanggan_id' => auth()->id(),
            'is_online' => true,
            'total' => $total,
            'status' => 'pending',
            'nama_pembeli' => $request->nama_pembeli,
            'alamat_pembeli' => $request->alamat_pembeli,
        ]);

        foreach ($request->items as $item) {
            $product = Product::find($item['product_id']);

            TransactionDetail::create([
                'transaction_id' => $transaction->id,
                'product_id' => $product->id,
                'qty' => $item['qty'],
                'harga_satuan' => $product->harga,
                'subtotal' => $product->harga * $item['qty'],
            ]);
        }

        return response()->json([
            'message' => 'Pesanan online berhasil dibuat.',
            'data' => $transaction->load('details')
        ]);
    }

    public function updateStatus($id, Request $request)
    {
        $request->validate(['status' => 'required|in:pending,paid,completed,cancelled']);

        $transaction = Transaction::findOrFail($id);
        $transaction->update(['status' => $request->status]);

        return response()->json([
            'message' => 'Status transaksi diperbarui.',
            'data' => $transaction
        ]);
    }
}
