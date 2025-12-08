<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\KurirController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\PetugasController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PelangganController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\StockRequestController;
use App\Http\Controllers\KategoriProdukController;
use App\Http\Controllers\FavoriteProductController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\PelangganProductController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    // 🔹 Semua role (admin, petugas, kurir, pelanggan)
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);

    /*
    |------------------------------------------------------------------
    | 🛒 KERANJANG (Shared untuk Pelanggan dan Petugas)
    |------------------------------------------------------------------
    */
    Route::prefix('cart')->group(function () {
        Route::get('/', [TransactionController::class, 'getCart']);
        Route::post('/add', [TransactionController::class, 'addToCart']);
        Route::put('/update/{id}', [TransactionController::class, 'updateCart']);
        Route::delete('/remove/{id}', [TransactionController::class, 'removeFromCart']);
        Route::delete('/clear', [TransactionController::class, 'clearCart']);
    });

    /*
    |------------------------------------------------------------------
    | 🧩 PELANGGAN
    |------------------------------------------------------------------
    */
    Route::middleware('role:user')->group(function () {
        // Dashboard
        Route::get('/pelanggan/products', [PelangganProductController::class, 'index']);

        // Profile Pelanggan - AUTO CREATE jika belum ada
        Route::get('/pelanggan/profile', [PelangganController::class, 'show']);
        Route::put('/pelanggan/profile', [PelangganController::class, 'update']); // 👈 INI YANG DIGUNAKAN

        // 💻 Transaksi Online
        Route::post('/transactions/online/checkout', [TransactionController::class, 'checkoutOnline']);
        Route::get('/transactions/online', [TransactionController::class, 'myOnlineTransactions']);
        Route::put('/transactions/{id}/status', [TransactionController::class, 'updateStatus']);
        Route::put('/transactions/{id}/complete', [TransactionController::class, 'completeOrder']);

        // Favorit
        Route::post('/favorites/{productId}/toggle', [FavoriteProductController::class, 'toggle']);
        Route::get('/favorites', [FavoriteProductController::class, 'list']);

        // 🚚 Tracking Updates
        Route::get('/transactions/{id}/delivery-updates', [TransactionController::class, 'getDeliveryUpdates']);
    });
    
    /*
    |------------------------------------------------------------------
    | 🧩 PETUGAS
    |------------------------------------------------------------------
    */
    Route::middleware('role:petugas')->group(function () {
        // Profile
        Route::get('/petugas/me', [PetugasController::class, 'show']);
        Route::put('/petugas/profile', [PetugasController::class, 'updateProfile']);

        // 🧱 Stock Request
        Route::post('/stock-requests', [StockRequestController::class, 'store']);
        Route::get('/stock-requests/my', [StockRequestController::class, 'myRequests']);

        // 💰 Transaksi Offline
        Route::post('/transactions/offline/checkout', [TransactionController::class, 'checkoutOffline']);
        Route::get('/transactions/offline', [TransactionController::class, 'myOfflineTransactions']);

        // Management Transaksi untuk Petugas
        // Route::put('/transactions/{id}/status', [TransactionController::class, 'updateStatus']); // ← HAPUS DARI SINI
        Route::get('/transactions/branch', [TransactionController::class, 'getBranchTransactions']);
        Route::get("/kurirs ", [TransactionController::class, 'getKurirs']);
        Route::put('/transactions/{id}/assign-kurir', [TransactionController::class, 'assignKurir']);
        Route::put('/transactions/{id}/delivery-status', [TransactionController::class, 'updateDeliveryStatus']);
    });

    /*
    |------------------------------------------------------------------
    | 🧩 KURIR
    |------------------------------------------------------------------
    */
    Route::middleware('role:kurir')->group(function () {
        Route::get('/kurir/me', [KurirController::class, 'me']);
        Route::put('/kurir/profile', [KurirController::class, 'updateProfile']);

        // Management Pengiriman
        Route::get('/kurir/assigned-orders', [TransactionController::class, 'getAssignedOrders']);
        Route::put('/transactions/{id}/update-delivery', [TransactionController::class, 'updateDeliveryStatus']);

        // 🚚 Tracking Updates (BARU)
        Route::post('/transactions/{id}/delivery-updates', [TransactionController::class, 'addDeliveryUpdate']);
        Route::put('/transactions/{id}/mark-delivered', [TransactionController::class, 'markAsDelivered']);
        Route::get('/transactions/{id}/delivery-updates', [TransactionController::class, 'getDeliveryUpdates']);
    });

    /*
    |------------------------------------------------------------------
    | 🧩 PRODUK (admin & petugas)
    |------------------------------------------------------------------
    */
    Route::middleware('role:admin,petugas')->group(function () {
        Route::get('/products', [ProductController::class, 'index']);
        Route::get('/products/{id}', [ProductController::class, 'show']);
        Route::get('/products/branch/{branch_id}', [ProductController::class, 'getByBranch']);
        Route::post('/products', [ProductController::class, 'store']);
        Route::put('/products/{id}', [ProductController::class, 'update']);
        Route::delete('/products/{id}', [ProductController::class, 'destroy']);
    });

    /*
    |------------------------------------------------------------------
    | 🧩 ADMIN ONLY
    |------------------------------------------------------------------
    */
    Route::middleware('role:admin')->group(function () {
        // Branch Management
        Route::apiResource('branches', BranchController::class);

        // User Management
        Route::get('/user', [AuthController::class, 'adminGetUsers']);

        Route::post('/create-user', [AuthController::class, 'adminCreateUser']);
        Route::put('/update-user/{id}', [AuthController::class, 'adminUpdateUser']);
        Route::delete('/delete-user/{id}', [AuthController::class, 'adminDeleteUser']);
        Route::get('/admin/petugas/{id}', [AuthController::class, 'adminShowPetugas']);
        Route::get('/admin/kurir/{id}', [AuthController::class, 'adminShowKurir']);

        // Admin Management
        Route::apiResource('admins', AdminController::class)->except(['create', 'edit']);
        Route::get('/admin/profile', [AuthController::class, 'profile']);
        Route::put('/admin/profile', [AuthController::class, 'updateProfile']);

        // Petugas Management
        Route::get('/petugas', [PetugasController::class, 'index']);
        Route::get('/petugas/{id}', [PetugasController::class, 'showById']);

        // Kurir Management
        Route::get('/kurir', [KurirController::class, 'index']);
        Route::get('/kurir/{id}', [KurirController::class, 'show']);
        Route::delete('/kurir/{id}', [KurirController::class, 'destroy']);
        
        // Pelanggan Management
        Route::get('/pelanggan', [PelangganController::class, 'index']);

        // Stock Request (Admin)
        Route::get('/stock-requests', [StockRequestController::class, 'index']);
        Route::get('/stock-requests/{id}', [StockRequestController::class, 'show']);
        Route::put('/stock-requests/{id}/approve', [StockRequestController::class, 'approve']);
        Route::put('/stock-requests/{id}/reject', [StockRequestController::class, 'reject']);

        // Transaksi Management
        Route::get('/transactions', [TransactionController::class, 'index']);

        // ✅ Kategori Produk Management
        Route::apiResource('kategori-produk', KategoriProdukController::class);

        Route::prefix('laporan')->group(function () {
            Route::get('/branches', [LaporanController::class, 'getBranches']);
            Route::get('/branch-transactions', [LaporanController::class, 'getBranchTransactions']);
            Route::get('/daily-sales', [LaporanController::class, 'getDailySales']);
        });


    });

    Route::get('/categories', [KategoriProdukController::class, 'index']);

    Route::get('/transactions/{id}', [TransactionController::class, 'show']);
});
