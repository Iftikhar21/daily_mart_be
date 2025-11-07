<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\KurirController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\PetugasController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StockRequestController;
use App\Http\Controllers\TransactionController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    // 🔹 Semua role (admin, petugas, kurir, pelanggan)
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);

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
        Route::post('/stock-requests', [StockRequestController::class, 'store']); // buat request stok
        Route::get('/stock-requests/my', [StockRequestController::class, 'myRequests']); // lihat request sendiri

        // 💰 Transaksi Offline
        Route::post('/transactions/offline', [TransactionController::class, 'storeOffline']); // buat transaksi offline
        Route::get('/transactions/offline', [TransactionController::class, 'listOffline']);   // lihat transaksi offline cabang
    });

    /*
    |------------------------------------------------------------------
    | 🧩 KURIR
    |------------------------------------------------------------------
    */
    Route::middleware('role:kurir')->group(function () {
        Route::get('/kurir/me', [KurirController::class, 'me']);
        Route::put('/kurir/profile', [KurirController::class, 'updateProfile']);
    });

    /*
    |------------------------------------------------------------------
    | 🧩 PELANGGAN
    |------------------------------------------------------------------
    */
    Route::middleware('role:pelanggan')->group(function () {
        // 💻 Transaksi Online
        Route::post('/transactions/online', [TransactionController::class, 'storeOnline']); // buat transaksi online
        Route::get('/transactions/online', [TransactionController::class, 'listOnline']);   // lihat riwayat transaksi online
    });

    /*
    |------------------------------------------------------------------
    | 🧩 PRODUK (admin & petugas)
    |------------------------------------------------------------------
    */
    Route::middleware('role:admin,petugas')->group(function () {
        Route::get('/products', [ProductController::class, 'index']);
        Route::get('/products/{id}', [ProductController::class, 'show']);
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

        // Admin Management
        Route::apiResource('admins', AdminController::class)->except(['create', 'edit']);

        // Petugas Management
        Route::get('/petugas', [PetugasController::class, 'index']);
        Route::get('/petugas/{id}', [PetugasController::class, 'showById']);

        // Kurir Management
        Route::get('/kurir', [KurirController::class, 'index']);
        Route::get('/kurir/{id}', [KurirController::class, 'show']);
        Route::delete('/kurir/{id}', [KurirController::class, 'destroy']);

        // Stock Request (Admin)
        Route::get('/stock-requests', [StockRequestController::class, 'index']);
        Route::get('/stock-requests/{id}', [StockRequestController::class, 'show']);
        Route::put('/stock-requests/{id}/approve', [StockRequestController::class, 'approve']);
        Route::put('/stock-requests/{id}/reject', [StockRequestController::class, 'reject']);

        // Transaksi Online Management
        Route::get('/transactions', [TransactionController::class, 'index']); // semua transaksi
        Route::put('/transactions/{id}/status', [TransactionController::class, 'updateStatus']); // update status
    });
});
