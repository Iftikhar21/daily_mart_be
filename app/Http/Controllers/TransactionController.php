<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Stock;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Models\DeliveryUpdate;
use App\Models\Kurir;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransactionController extends Controller
{

    // Di TransactionController.php - tambah method ini
    public function show($id)
    {
        $user = auth()->user();

        if ($user->role === 'user') {
            $pelangganId = $user->pelanggan->id;
            $transaction = Transaction::with([
                'details.product',
                'branch',
                'pelanggan'
            ])
                ->where('id', $id)
                ->where('pelanggan_id', $pelangganId)
                ->firstOrFail();
        } else {
            $transaction = Transaction::with([
                'details.product',
                'branch',
                'pelanggan',
                'pelanggan.user',
            ])->findOrFail($id);
        }

        return response()->json($transaction);
    }
    /*
    |--------------------------------------------------------------------------
    | 🛒 KERANJANG BELANJA (Shared untuk Online & Offline)
    |--------------------------------------------------------------------------
    */

    public function getCart(Request $request)
    {
        $cartItems = Cart::with('product')
            ->where('user_id', auth()->id())
            ->get();

        $total = 0;
        foreach ($cartItems as $item) {
            $total += $item->product->harga * $item->qty;
        }

        return response()->json([
            'items' => $cartItems,
            'total' => $total
        ]);
    }

    public function addToCart(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'qty' => 'required|integer|min:1',
        ]);

        // Cek stok (untuk online perlu cek, untuk offline langsung di checkout)
        $product = Product::find($request->product_id);
        
        // Untuk petugas (offline) tidak perlu cek stok di keranjang
        if (auth()->user()->role === 'user') {
            $availableStock = Stock::where('product_id', $product->id)
                ->sum('qty');
                
            if ($availableStock < $request->qty) {
                return response()->json(['message' => 'Stok tidak cukup'], 400);
            }
        }

        // Cek apakah produk sudah ada di keranjang
        $existingCart = Cart::where('user_id', auth()->id())
            ->where('product_id', $request->product_id)
            ->first();

        if ($existingCart) {
            $existingCart->update(['qty' => $existingCart->qty + $request->qty]);
        } else {
            Cart::create([
                'user_id' => auth()->id(),
                'product_id' => $request->product_id,
                'qty' => $request->qty,
            ]);
        }

        return response()->json(['message' => 'Produk berhasil ditambahkan ke keranjang']);
    }

    public function updateCart(Request $request, $id)
    {
        $request->validate([
            'qty' => 'required|integer|min:1',
        ]);

        $cart = Cart::where('user_id', auth()->id())->findOrFail($id);
        
        // Untuk pelanggan (online) cek stok
        if (auth()->user()->role === 'user') {
            $product = Product::find($cart->product_id);
            $availableStock = Stock::where('product_id', $product->id)
                ->sum('qty');
                
            if ($availableStock < $request->qty) {
                return response()->json(['message' => 'Stok tidak cukup'], 400);
            }
        }

        $cart->update(['qty' => $request->qty]);

        return response()->json(['message' => 'Keranjang berhasil diupdate']);
    }

    public function removeFromCart($id)
    {
        $cart = Cart::where('user_id', auth()->id())->findOrFail($id);
        $cart->delete();

        return response()->json(['message' => 'Produk berhasil dihapus dari keranjang']);
    }

    public function clearCart()
    {
        Cart::where('user_id', auth()->id())->delete();
        return response()->json(['message' => 'Keranjang berhasil dikosongkan']);
    }

    /*
    |--------------------------------------------------------------------------
    | 💻 CHECKOUT ONLINE (Pelanggan)
    |--------------------------------------------------------------------------
    */

    public function checkoutOnline(Request $request)
    {
        $request->validate([
            'payment_method' => 'required|in:cash,transfer,ewallet',
        ]);

        $user = auth()->user();
        $pelanggan = $user->pelanggan;

        if (!$pelanggan) {
            return response()->json(['message' => 'Data pelanggan tidak ditemukan'], 404);
        }

        $branchId = $pelanggan->branch_id;

        // Ambil items keranjang
        $cartItems = Cart::with('product')
            ->where('user_id', $user->id)
            ->get();

        if ($cartItems->isEmpty()) {
            return response()->json(['message' => 'Keranjang belanja kosong'], 400);
        }

        // Hitung total dan cek stok
        $total = 0;
        foreach ($cartItems as $item) {
            $availableStock = Stock::where('branch_id', $branchId)
                ->where('product_id', $item->product_id)
                ->sum('qty');

            if ($availableStock < $item->qty) {
                return response()->json([
                    'message' => "Stok tidak cukup untuk produk: {$item->product->nama_produk}",
                    'requested' => $item->qty,
                    'available' => $availableStock
                ], 400);
            }

            $total += $item->product->harga * $item->qty;
        }

        // Buat transaksi
        $transaction = Transaction::create([
            'branch_id' => $branchId,
            'pelanggan_id' => $pelanggan->id,
            'is_online' => true,
            'total' => $total,
            'payment_method' => $request->payment_method,
            'status' => 'pending',
            'delivery_status' => 'pending',
            'nama_pembeli' => $pelanggan->nama,
            'alamat_pembeli' => $pelanggan->alamat,
            'kurir_id' => null, // default online checkout
        ]);

        // Buat detail transaksi + kurangi stok
        foreach ($cartItems as $item) {
            TransactionDetail::create([
                'transaction_id' => $transaction->id,
                'product_id' => $item->product_id,
                'qty' => $item->qty,
                'harga_satuan' => $item->product->harga,
                'subtotal' => $item->product->harga * $item->qty,
            ]);

            // Kurangi stok
            $remainingQty = $item->qty;
            $stocks = Stock::where('branch_id', $branchId)
                ->where('product_id', $item->product_id)
                ->orderBy('created_at')
                ->get();

            foreach ($stocks as $stock) {
                if ($remainingQty <= 0) break;

                if ($stock->qty >= $remainingQty) {
                    $stock->decrement('qty', $remainingQty);
                    $remainingQty = 0;
                } else {
                    $remainingQty -= $stock->qty;
                    $stock->update(['qty' => 0]);
                }
            }
        }

        Cart::where('user_id', $user->id)->delete();

        return response()->json([
            'message' => 'Checkout berhasil',
            'data' => $transaction->load('details.product')
        ]);
    }
    /*
    |--------------------------------------------------------------------------
    | 💰 CHECKOUT OFFLINE (Petugas)
    |--------------------------------------------------------------------------
    */

    public function checkoutOffline(Request $request)
    {
        $request->validate([
            'payment_method' => 'required|in:cash,transfer,ewallet',
        ]);

        return DB::transaction(function () use ($request) {
            // Ambil branch_id dari petugas yang login
            $petugas = auth()->user()->petugas;
            if (!$petugas) {
                return response()->json(['message' => 'Data petugas tidak ditemukan'], 400);
            }

            $branch_id = $petugas->branch_id;

            // Ambil items dari keranjang petugas
            $cartItems = Cart::with('product')
                ->where('user_id', auth()->id())
                ->get();

            if ($cartItems->isEmpty()) {
                return response()->json(['message' => 'Keranjang belanja kosong'], 400);
            }

            // Hitung total dan validasi stok untuk offline
            $total = 0;
            foreach ($cartItems as $item) {
                $availableStock = Stock::where('branch_id', $branch_id)
                    ->where('product_id', $item->product_id)
                    ->sum('qty');

                if ($availableStock < $item->qty) {
                    return response()->json([
                        'message' => "Stok tidak cukup untuk produk: {$item->product->nama_produk}",
                        'product' => $item->product->nama_produk,
                        'requested' => $item->qty,
                        'available' => $availableStock
                    ], 400);
                }

                $total += $item->product->harga * $item->qty;
            }

            // Buat transaksi offline
            $transaction = Transaction::create([
                'branch_id' => $branch_id,
                'petugas_id' => auth()->id(),
                'is_online' => false,
                'total' => $total,
                'payment_method' => $request->payment_method,
                'status' => 'paid', // Langsung paid untuk offline
                'delivery_status' => 'completed', // Langsung completed untuk offline
            ]);

            // Buat detail transaksi dan kurangi stok
            foreach ($cartItems as $item) {
                TransactionDetail::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $item->product_id,
                    'qty' => $item->qty,
                    'harga_satuan' => $item->product->harga,
                    'subtotal' => $item->product->harga * $item->qty,
                ]);

                // Kurangi stok
                $this->decreaseStock($branch_id, $item->product_id, $item->qty);
            }

            // Kosongkan keranjang petugas
            Cart::where('user_id', auth()->id())->delete();

            return response()->json([
                'message' => 'Transaksi offline berhasil dibuat',
                'data' => $transaction->load('details.product')
            ]);
        });
    }

    /*
|--------------------------------------------------------------------------
| 📦 MANAGEMENT PENGIRIMAN (Online Only)
|--------------------------------------------------------------------------
*/
    public function getKurirs(Request $request)
    {
        $kurirs = Kurir::with('user')->get();
        return response()->json($kurirs);
    }

    public function assignKurir(Request $request, $id)
    {
        $request->validate([
            'kurir_id' => 'required|exists:kurirs,id',
        ]);

        $transaction = Transaction::where('is_online', true)
            ->findOrFail($id);

        // Dapatkan petugas yang sedang login (yang assign kurir)
        $petugas = auth()->user()->petugas;
        if (!$petugas) {
            return response()->json(['message' => 'Data petugas tidak ditemukan'], 400);
        }

        $transaction->update([
            'kurir_id' => $request->kurir_id,
            'petugas_id' => $petugas->id,
            'delivery_status' => 'assigned',
        ]);

        // Buat tracking update pertama
        DeliveryUpdate::create([
            'transaction_id' => $transaction->id,
            'kurir_id' => $request->kurir_id,
            'status_message' => 'Kurir telah ditugaskan untuk pengiriman',
        ]);

        return response()->json([
            'message' => 'Kurir berhasil ditugaskan',
            'data' => $transaction->load(['details.product', 'deliveryUpdates'])
        ]);
    }

    public function updateDeliveryStatus(Request $request, $id)
    {
        $request->validate([
            'delivery_status' => 'required|in:assigned,picked_up,on_delivery,delivered',
        ]);

        $transaction = Transaction::where('is_online', true)
            ->findOrFail($id);

        // Validasi hanya kurir yang ditugaskan yang bisa update
        if (auth()->user()->role === 'kurir' && $transaction->kurir->user_id !== auth()->id()) {
            return response()->json(['message' => 'Anda bukan kurir yang ditugaskan'], 403);
        }

        $transaction->update(['delivery_status' => $request->delivery_status]);

        return response()->json([
            'message' => 'Status pengiriman diperbarui',
            'data' => $transaction
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | 🚚 KURIR TRACKING UPDATES
    |--------------------------------------------------------------------------
    */

    public function addDeliveryUpdate(Request $request, $id)
    {
        $request->validate([
            'status_message' => 'required|string|max:255',
        ]);

        // Ambil data kurir berdasarkan user yang login
        $kurir = auth()->user()->kurir;

        if (!$kurir) {
            return response()->json(['message' => 'Data kurir tidak ditemukan'], 404);
        }

        // Cari transaksi yang sesuai
        $transaction = Transaction::where('is_online', true)
            ->where('kurir_id', $kurir->id) // KURIR.ID, BUKAN USER.ID
            ->findOrFail($id);

        if ($transaction->delivery_status !== 'on_delivery') {
            return response()->json([
                'message' => 'Hanya bisa update tracking ketika status pengiriman sedang on_delivery'
            ], 400);
        }

        $deliveryUpdate = DeliveryUpdate::create([
            'transaction_id' => $transaction->id,
            'kurir_id' => $kurir->id,
            'status_message' => $request->status_message,
        ]);

        return response()->json([
            'message' => 'Update tracking berhasil ditambahkan',
            'data' => $deliveryUpdate
        ]);
    }

    public function markAsDelivered($id)
    {
        $kurirId = auth()->user()->kurir->id;

        if (!$kurirId) {
            return response()->json(['message' => 'Data kurir tidak ditemukan'], 404);
        }

        $transaction = Transaction::where('is_online', true)
            ->where('kurir_id', $kurirId)
            ->findOrFail($id);

        $transaction->update([
            'delivery_status' => 'delivered'
        ]);

        DeliveryUpdate::create([
            'transaction_id' => $transaction->id,
            'kurir_id' => $kurirId,
            'status_message' => 'Pesanan telah sampai di tujuan',
        ]);

        return response()->json([
            'message' => 'Pesanan telah ditandai sebagai sampai',
            'data' => $transaction->load(['details.product', 'deliveryUpdates'])
        ]);
    }


    public function getDeliveryUpdates($id)
    {
        $transaction = Transaction::where('is_online', true)
            ->findOrFail($id);

        // Untuk kurir: hanya bisa lihat updates pesanan mereka
        // Untuk pelanggan: hanya bisa lihat updates pesanan mereka
        if (auth()->user()->role === 'kurir' && $transaction->kurir_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (auth()->user()->role === 'user' && $transaction->pelanggan_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $updates = DeliveryUpdate::with('kurir.user')
            ->where('transaction_id', $transaction->id)
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($updates);
    }

    // Di TransactionController.php - tambahkan method completeOrder
    public function completeOrder($id)
    {
        $pelangganId = auth()->user()->pelanggan->id;

        if (!$pelangganId) {
            return response()->json(['message' => 'Data pelanggan tidak ditemukan'], 400);
        }

        $transaction = Transaction::where('pelanggan_id', $pelangganId)
            ->where('is_online', true)
            ->findOrFail($id);

        if ($transaction->delivery_status !== 'delivered') {
            return response()->json(['message' => 'Pesanan belum sampai'], 400);
        }

        $transaction->update([
            'status' => 'completed',
        ]);

        return response()->json([
            'message' => 'Pesanan telah diselesaikan',
            'data' => $transaction
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | 📋 GET TRANSAKSI
    |--------------------------------------------------------------------------
    */

    public function myOnlineTransactions()
    {
        $user = auth()->user();

        // Debug: cek user dan role
        Log::info('User:', [$user->id, $user->role, $user->email]);

        // Cek relasi pelanggan
        if (!$user->pelanggan) {
            Log::error('Pelanggan not found for user:', [$user->id]);
            return response()->json([
                'message' => 'Data pelanggan tidak ditemukan untuk user ini',
                'user_role' => $user->role
            ], 404);
        }

        $pelangganId = $user->pelanggan->id;

        $transactions = Transaction::with([
            'details.product',
            'pelanggan',
            'branch',
            'kurir.user',
            'deliveryUpdates.kurir.user'
        ])
            ->where('pelanggan_id', $pelangganId)
            ->where('is_online', true)
            ->orderBy('created_at', 'desc')
            ->get();

        // Cek jika transaksi kosong
        if ($transactions->isEmpty()) {
            return response()->json([
                'message' => 'Belum ada transaksi online',
                'data' => []
            ], 200);
        }

        return response()->json($transactions);
    }


    public function myOfflineTransactions()
    {
        $transactions = Transaction::with(['details.product', 'branch'])
            ->where('petugas_id', auth()->id())
            ->where('is_online', false)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($transactions);
    }

    public function getBranchTransactions()
    {
        $petugas = auth()->user()->petugas;
        if (!$petugas) {
            return response()->json(['message' => 'Data petugas tidak ditemukan'], 400);
        }

        $transactions = Transaction::with(['details.product', 'pelanggan.user', 'kurir'])
            ->where('branch_id', $petugas->branch_id)
            ->where('is_online', true)
            ->where('status', '!=', 'completed')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($transactions);
    }

    public function getAssignedOrders()
    {
        $kurir = auth()->user()->kurir;

        if (!$kurir) {
            return response()->json(['message' => 'Data kurir tidak ditemukan'], 404);
        }

        $orders = Transaction::with([
            'details.product',
            'branch',
            'pelanggan',
            'kurir.user' // ⬅️ tambahkan ini untuk menampilkan nama kurir
        ])
            ->where('kurir_id', $kurir->id)
            ->where('is_online', true)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($orders);
    }


    /*
    |--------------------------------------------------------------------------
    | 🔧 HELPER METHODS
    |--------------------------------------------------------------------------
    */

    private function decreaseStock($branch_id, $product_id, $qty)
    {
        $remainingQty = $qty;
        $stocks = Stock::where('branch_id', $branch_id)
            ->where('product_id', $product_id)
            ->where('qty', '>', 0)
            ->orderBy('created_at')
            ->get();

        foreach ($stocks as $stock) {
            if ($remainingQty <= 0) break;

            if ($stock->qty >= $remainingQty) {
                $stock->decrement('qty', $remainingQty);
                $remainingQty = 0;
            } else {
                $remainingQty -= $stock->qty;
                $stock->update(['qty' => 0]);
            }
        }
    }

    public function updateStatus(Request $request, $id)
    {

        $pelanggan = auth()->user()->pelanggan;

        if (!$pelanggan) {
            return response()->json(['message' => 'Data pelanggan tidak ditemukan'], 400);
        }

        $transaction = Transaction::where('id', $id)
            ->where('pelanggan_id', $pelanggan->id)  // FIX: gunakan pelanggan->id
            ->firstOrFail();


        $request->validate([
            'status' => 'required|in:paid,cancelled'
        ]);

        // Validasi: hanya transaksi dengan status pending yang bisa diupdate
        if ($transaction->status !== 'pending') {
            return response()->json([
                'message' => 'Transaksi tidak dapat diupdate. Status saat ini: ' . $transaction->status
            ], 400);
        }

        // Jika user memilih bayar (paid)
        if ($request->status === 'paid') {
            // Simulasi proses pembayaran (dummy)
            // Di sini bisa ditambahkan logika dummy payment gateway

            $transaction->update([
                'status' => 'paid',
                // delivery_status tetap pending menunggu assign kurir
            ]);

            return response()->json([
                'message' => 'Pembayaran berhasil dikonfirmasi. Menunggu proses pengiriman.',
                'data' => $transaction
            ]);
        }

        // Jika user memilih cancel
        if ($request->status === 'cancelled') {
            $transaction->update([
                'status' => 'cancelled'
            ]);

            // Kembalikan stok yang sudah dikurangi
            $this->restoreStock($transaction);

            return response()->json([
                'message' => 'Pesanan telah dibatalkan',
                'data' => $transaction
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | 🔧 HELPER METHODS - Restore Stock untuk Cancel
    |--------------------------------------------------------------------------
    */

    private function restoreStock($transaction)
    {
        foreach ($transaction->details as $detail) {
            $remainingQty = $detail->qty;
            $stocks = Stock::where('branch_id', $transaction->branch_id)
                ->where('product_id', $detail->product_id)
                ->orderBy('created_at', 'desc') // Yang terakhir dikurangi dikembalikan dulu
                ->get();

            foreach ($stocks as $stock) {
                if ($remainingQty <= 0) break;

                // Kembalikan stok
                $stock->increment('qty', $remainingQty);
                $remainingQty = 0;
            }
        }
    }

    // Di TransactionController.php - tambahkan method ini
    public function getTransactionForAssign($id)
    {
        // Pastikan hanya petugas yang bisa mengakses
        if (auth()->user()->role !== 'petugas') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $petugas = auth()->user()->petugas;
        if (!$petugas) {
            return response()->json(['message' => 'Data petugas tidak ditemukan'], 400);
        }

        // Ambil transaksi yang hanya dari branch petugas, online, dan status 'paid'
        $transaction = Transaction::with([
            'details.product',
            'pelanggan.user', // Relasi pelanggan dan user
            'branch'
        ])
            ->where('id', $id)
            ->where('branch_id', $petugas->branch_id)
            ->where('is_online', true)
            ->where('status', 'paid') // Hanya transaksi yang sudah bayar
            ->where('kurir_id', null) // Belum ada kurir yang ditugaskan
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $transaction
        ]);
    }

    // Method alternatif: get detail transaksi untuk petugas
    public function getTransactionDetailForPetugas($id)
    {
        $petugas = auth()->user()->petugas;
        if (!$petugas) {
            return response()->json(['message' => 'Data petugas tidak ditemukan'], 400);
        }

        $transaction = Transaction::with([
            'details.product',
            'pelanggan.user',
            'branch',
            'kurir.user' // Jika sudah ada kurir yang ditugaskan
        ])
            ->where('id', $id)
            ->where('branch_id', $petugas->branch_id)
            ->where('is_online', true)
            ->firstOrFail();

        // Tentukan apakah petugas bisa assign kurir
        $canAssignCourier = $transaction->status === 'paid' && $transaction->kurir_id === null;

        return response()->json([
            'success' => true,
            'data' => $transaction,
            'can_assign_courier' => $canAssignCourier
        ]);
    }
}