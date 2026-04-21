<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\SellController;
use App\Http\Controllers\MypageController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\RatingController;



/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', [ItemController::class, 'index'])->name('items.index');

Route::get('/register', [RegisterController::class, 'showRegister'])->name('register');
Route::post('/register', [RegisterController::class, 'store']);

Route::get('/login', [LoginController::class, 'loginView'])->name('login');
Route::post('/login', [LoginController::class, 'store']);

Route::get('/item/{item}', [ItemController::class, 'show'])->name('item.show');

Route::get('/email/verify/{id}/{hash}', [RegisterController::class, 'verifyEmail'])
    ->middleware(['signed'])
    ->name('verification.verify');

Route::middleware('auth')->group(function () {
    Route::get('/email/verify', [RegisterController::class, 'showVerifyNotice'])
        ->name('verification.notice');

    Route::post('/email/verification-notification', [RegisterController::class, 'resendVerification'])
        ->name('verification.send');
});

Route::middleware(['auth','verified'])->group(function () {

    Route::post('/item/{item}', [ItemController::class, 'store'])->name('item.store');

    Route::get('/purchase/{item_id}', [PurchaseController::class, 'show'])->name('purchase.show');
    Route::post('/purchase/{item_id}', [PurchaseController::class, 'store'])->name('purchase.store');

    Route::get('/purchase/address/{item}', [PurchaseController::class, 'editAddress'])->name('purchase.address.edit');
    Route::post('/purchase/address/{item}', [PurchaseController::class, 'updateAddress'])->name('purchase.address.update');

    Route::get('/sell', [SellController::class, 'create'])->name('sell.create');
    Route::post('/sell', [SellController::class, 'store'])->name('sell.store');

    Route::get('/mypage', [MypageController::class, 'index'])->name('mypage.index');

    Route::get('/mypage/profile', [MypageController::class, 'edit'])->name('profile.edit');
    Route::post('/mypage/profile', [MypageController::class, 'update'])->name('profile.update');

    Route::get('/transaction/{transaction_id}', [TransactionController::class, 'show'])
    ->name('transaction.show');

    Route::post('/transaction/{transaction_id}/message', [MessageController::class, 'store'])
        ->name('message.store');
    Route::patch('/messages/{message_id}', [MessageController::class, 'update'])
        ->name('message.update');
    Route::delete('/messages/{message_id}', [MessageController::class, 'destroy'])
        ->name('message.destroy');

    Route::post('/transaction/{transaction_id}/complete', [TransactionController::class, 'complete'])
        ->name('transaction.complete');

    Route::post('/transaction/{transaction_id}/rating', [RatingController::class, 'store'])
        ->name('transaction.rating.store');
});
