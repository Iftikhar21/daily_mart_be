<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\TransactionDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PelangganProductController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $pelanggan = $user->pelanggan;

        if (!$pelanggan) {
            return response()->json(['message' => 'Data pelanggan tidak ditemukan.'], 404);
        }

        $branchId = $pelanggan->branch_id;

        if (!$branchId) {
            return response()->json(['message' => 'Branch pelanggan tidak ditemukan.'], 404);
        }

        // Ambil produk paling sering dibeli di branch ini (max 6 produk)
        $popularProducts = $this->getPopularProducts($branchId, 6);

        // Jika produk populer kurang dari 6, tambahkan produk lainnya
        if ($popularProducts->count() < 6) {
            $remainingCount = 6 - $popularProducts->count();
            $otherProducts = $this->getOtherProducts($branchId, $popularProducts->pluck('id')->toArray(), $remainingCount);

            // Gabungkan produk
            $products = $popularProducts->concat($otherProducts);
        } else {
            $products = $popularProducts;
        }

        // Map data produk untuk response
        $products->map(function ($product) {
            $product->gambar_url = $product->gambar
                ? asset('storage/' . $product->gambar)
                : asset('images/no-image.png');

            $product->stok = $product->stok ?? 0;

            return $product;
        });

        return response()->json([
            'branch_id' => $branchId,
            'products' => $products,
            'is_popular_only' => $popularProducts->count() > 0,
            'popular_count' => $popularProducts->count(),
        ]);
    }

    /**
     * Get popular products based on transaction history
     */
    private function getPopularProducts($branchId, $limit = 6)
    {
        return Product::select(
            'products.*',
            DB::raw('SUM(transaction_details.qty) as total_sold'),
            DB::raw('COALESCE(SUM(stocks.qty), 0) as stok')
        )
            ->join('transaction_details', 'products.id', '=', 'transaction_details.product_id')
            ->join('transactions', 'transaction_details.transaction_id', '=', 'transactions.id')
            ->leftJoin('stocks', function ($join) use ($branchId) {
                $join->on('products.id', '=', 'stocks.product_id')
                    ->where('stocks.branch_id', $branchId);
            })
            ->where('transactions.branch_id', $branchId)
            ->where('transactions.status', '!=', 'cancelled')
            ->where('transactions.is_online', true)
            ->groupBy('products.id')
            ->orderByDesc('total_sold')
            ->limit($limit)
            ->with(['branch', 'kategori'])
            ->get();
    }

    /**
     * Get other products (not in popular list)
     */
    private function getOtherProducts($branchId, $excludeIds = [], $limit = 6)
    {
        $query = Product::where('branch_id', $branchId)
            ->with(['branch', 'kategori'])
            ->withSum('stocks as stok', 'qty');

        if (!empty($excludeIds)) {
            $query->whereNotIn('id', $excludeIds);
        }

        return $query->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * NEW: Endpoint khusus untuk dashboard home (hanya produk popular/paling laku)
     */
    public function dashboardProducts(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $pelanggan = $user->pelanggan;

        if (!$pelanggan) {
            return response()->json(['message' => 'Data pelanggan tidak ditemukan.'], 404);
        }

        $branchId = $pelanggan->branch_id;

        if (!$branchId) {
            return response()->json(['message' => 'Branch pelanggan tidak ditemukan.'], 404);
        }

        // Coba ambil produk populer (max 6)
        $popularProducts = $this->getPopularProducts($branchId, 6);

        // Jika tidak ada produk populer (belum ada transaksi), ambil produk terbaru
        if ($popularProducts->isEmpty()) {
            $popularProducts = Product::where('branch_id', $branchId)
                ->with(['branch', 'kategori'])
                ->withSum('stocks as stok', 'qty')
                ->orderBy('created_at', 'desc')
                ->limit(6)
                ->get();
        }

        // Map data produk untuk response
        $popularProducts->map(function ($product) {
            $product->gambar_url = $product->gambar
                ? asset('storage/' . $product->gambar)
                : asset('images/no-image.png');

            $product->stok = $product->stok ?? 0;

            return $product;
        });

        return response()->json([
            'branch_id' => $branchId,
            'products' => $popularProducts,
            'total_products' => $popularProducts->count(),
            'message' => $popularProducts->count() > 0
                ? 'Produk paling sering dibeli'
                : 'Produk terbaru',
        ]);
    }
}