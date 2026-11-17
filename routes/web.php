<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\BayarcashController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LiveCommentController;
use App\Http\Controllers\LiveController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\Seller\DashboardController as SellerDashboardController;
use App\Http\Controllers\Seller\LiveSessionController as SellerLiveSessionController;
use App\Http\Controllers\Seller\OrderController as SellerOrderController;
use App\Http\Controllers\Seller\ProductController as SellerProductController;
use App\Http\Controllers\Seller\ShopSettingsController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\ShopFollowController;
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
] as $path => $title) {
    Route::get("/{$path}", fn () => Inertia::render('ComingSoon', ['title' => $title]))->name($path);
}

Route::post('/bayarcash/callback', [BayarcashController::class, 'callback'])->name('bayarcash.callback');
Route::get('/bayarcash/kembali', [BayarcashController::class, 'return'])->name('bayarcash.return');

Route::get('/live', [LiveController::class, 'index'])->name('live.index');
Route::get('/live/{liveSession}', [LiveController::class, 'show'])->name('live.show');
Route::get('/live/{liveSession}/komen', [LiveCommentController::class, 'index'])->name('live.comments.index');

Route::middleware('auth')->group(function () {
    Route::post('/live/{liveSession}/komen', [LiveCommentController::class, 'store'])
        ->middleware('throttle:20,1')
        ->name('live.comments.store');

    Route::get('/troli', [CartController::class, 'index'])->name('cart.index');
    Route::post('/troli', [CartController::class, 'store'])->name('cart.store');
    Route::patch('/troli/{cartItem}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/troli/{cartItem}', [CartController::class, 'destroy'])->name('cart.destroy');

    Route::get('/checkout', [CheckoutController::class, 'create'])->name('checkout.create');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');

    Route::get('/bayaran/{payment}/simulasi', [PaymentController::class, 'simulate'])->name('payments.simulate');
    Route::post('/bayaran/{payment}/simulasi', [PaymentController::class, 'completeSimulation'])->name('payments.simulate.complete');

    Route::get('/pesanan', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/pesanan/{order:number}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/pesanan/{order:number}/terima', [ReviewController::class, 'complete'])->name('orders.complete');
    Route::post('/pesanan/{order:number}/ulasan', [ReviewController::class, 'store'])->name('reviews.store');

    Route::get('/favorit', [FavoriteController::class, 'index'])->name('favorites.index');
    Route::post('/favorit/{product}', [FavoriteController::class, 'toggle'])->name('favorites.toggle');
    Route::post('/kedai/{shop:slug}/ikut', [ShopFollowController::class, 'toggle'])->name('shops.follow');

    Route::get('/buka-kedai', [ShopRegistrationController::class, 'create'])->name('shops.create');
    Route::post('/buka-kedai', [ShopRegistrationController::class, 'store'])->name('shops.store');

    Route::get('/dashboard', fn () => Inertia::render('Dashboard'))->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'role:seller'])->prefix('penjual')->name('seller.')->group(function () {
    Route::get('/', SellerDashboardController::class)->name('dashboard');
    Route::get('/tetapan', [ShopSettingsController::class, 'edit'])->name('settings.edit');
    Route::post('/tetapan', [ShopSettingsController::class, 'update'])->name('settings.update');
    Route::get('/pesanan', [SellerOrderController::class, 'index'])->name('orders.index');
    Route::patch('/pesanan/{order}/hantar', [SellerOrderController::class, 'ship'])->name('orders.ship');

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

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', AdminDashboardController::class)->name('dashboard');
});

require __DIR__.'/auth.php';
