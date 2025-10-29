<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\PetugasController;
use App\Http\Controllers\ProductController;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

// Register & Login (tidak perlu login sebelumnya)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Routes yang butuh autentikasi
Route::middleware('auth:sanctum')->group(function () {

    // User profile & logout
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);

    /*
    |--------------------------------------------------------------------------
    | Branch Routes (CRUD)
    |--------------------------------------------------------------------------
    */
    Route::get('/branches', [BranchController::class, 'index']);           // GET all branches
    Route::get('/branches/{id}', [BranchController::class, 'show']);       // GET single branch
    Route::post('/branches', [BranchController::class, 'store']);          // CREATE branch
    Route::put('/branches/{id}', [BranchController::class, 'update']);    // UPDATE branch
    Route::delete('/branches/{id}', [BranchController::class, 'destroy']); // DELETE branch


    /*
    |--------------------------------------------------------------------------
    | Products Routes (CRUD)
    |--------------------------------------------------------------------------
    */
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{id}', [ProductController::class, 'show']);
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);


    /*
    |--------------------------------------------------------------------------
    | Petugas Routes (CRUD)
    |--------------------------------------------------------------------------
    */
    Route::get('/petugas', [PetugasController::class, 'index']);
    Route::get('/petugas/{id}', [PetugasController::class, 'show']);
    Route::post('/petugas', [PetugasController::class, 'store']);
    Route::put('/petugas/{id}', [PetugasController::class, 'update']);
    Route::delete('/petugas/{id}', [PetugasController::class, 'destroy']);
});
