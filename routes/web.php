<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\LiveCommentController;
use App\Http\Controllers\LiveController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Seller\LiveSessionController as SellerLiveSessionController;
use App\Http\Controllers\Seller\ProductController as SellerProductController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\ShopRegistrationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', HomeController::class)->name('home');

Route::get('/teroka', [ProductController::class, 'index'])->name('products.index');
Route::get('/produk/{product:slug}', [ProductController::class, 'show'])->name('products.show');

Route::get('/kedai', [ShopController::class, 'index'])->name('shops.index');
Route::get('/kedai/{shop:slug}', [ShopController::class, 'show'])->name('shops.show');

// Modul yang belum dibina — paparkan halaman "Akan Datang" dulu.
foreach ([
    'ai' => 'AI DeBatik',
    'favorit' => 'Favorit',
    'troli' => 'Troli',
] as $path => $title) {
    Route::get("/{$path}", fn () => Inertia::render('ComingSoon', ['title' => $title]))->name($path);
}

Route::get('/live', [LiveController::class, 'index'])->name('live.index');
Route::get('/live/{liveSession}', [LiveController::class, 'show'])->name('live.show');
Route::get('/live/{liveSession}/komen', [LiveCommentController::class, 'index'])->name('live.comments.index');

Route::middleware('auth')->group(function () {
    Route::post('/live/{liveSession}/komen', [LiveCommentController::class, 'store'])
        ->middleware('throttle:20,1')
        ->name('live.comments.store');

    Route::get('/buka-kedai', [ShopRegistrationController::class, 'create'])->name('shops.create');
    Route::post('/buka-kedai', [ShopRegistrationController::class, 'store'])->name('shops.store');

    Route::get('/dashboard', fn () => Inertia::render('Dashboard'))->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'role:seller'])->prefix('penjual')->name('seller.')->group(function () {
    Route::resource('produk', SellerProductController::class)
        ->except('show')
        ->parameters(['produk' => 'product'])
        ->names('products');

    Route::get('/live', [SellerLiveSessionController::class, 'index'])->name('live.index');
    Route::post('/live', [SellerLiveSessionController::class, 'store'])->name('live.store');
    Route::get('/live/{liveSession}', [SellerLiveSessionController::class, 'show'])->name('live.show');
    Route::patch('/live/{liveSession}', [SellerLiveSessionController::class, 'update'])->name('live.update');
    Route::post('/live/{liveSession}/mula', [SellerLiveSessionController::class, 'start'])->name('live.start');
    Route::post('/live/{liveSession}/tamat', [SellerLiveSessionController::class, 'end'])->name('live.end');
});

require __DIR__.'/auth.php';
