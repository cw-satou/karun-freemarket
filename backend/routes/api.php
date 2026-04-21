<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\SellController;
use App\Http\Controllers\Api\MypageController;

// Auth
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

// Products (public)
Route::get('/products',           [ProductController::class, 'index']);
Route::get('/products/{product}', [ProductController::class, 'show']);

// Sell form data (public)
Route::get('/sell/form-data', [SellController::class, 'formData']);

// Authenticated routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);

    // Products (auth actions)
    Route::post('/products/{product}/favorite', [ProductController::class, 'toggleFavorite']);
    Route::post('/products/{product}/comments', [ProductController::class, 'storeComment']);

    // Orders / checkout
    Route::post('/orders', [OrderController::class, 'store']);

    // Messages per transaction
    Route::get('/transactions/{transaction_id}/messages',  [MessageController::class, 'index']);
    Route::post('/transactions/{transaction_id}/messages', [MessageController::class, 'store']);
    Route::patch('/messages/{message_id}',  [MessageController::class, 'update']);
    Route::delete('/messages/{message_id}', [MessageController::class, 'destroy']);

    // Sell
    Route::post('/sell', [SellController::class, 'store']);

    // Mypage
    Route::get('/mypage',          [MypageController::class, 'index']);
    Route::post('/mypage/profile', [MypageController::class, 'updateProfile']);
});
