<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\ReportExportController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\SubscriberController;
use App\Http\Controllers\SocialAuthController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\UserAddressController;
use App\Http\Controllers\WishlistController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

// Static pages
Route::get('/about',   [PageController::class, 'about'])->name('page.about');
Route::get('/contact', [PageController::class, 'contact'])->name('page.contact');
Route::post('/contact', [PageController::class, 'contactSend'])->name('page.contact.send');
Route::get('/faq',     [PageController::class, 'faq'])->name('page.faq');
Route::get('/privacy', [PageController::class, 'privacy'])->name('page.privacy');
Route::get('/terms', [PageController::class, 'termsConditions'])->name('page.terms');
Route::get('/blog',    [PageController::class, 'blog'])->name('page.blog');
Route::get('/blog/{slug}', [PageController::class, 'blogPost'])->name('page.blog.post');

// Newsletter subscribe / confirm / unsubscribe
Route::post('/subscribe', [SubscriberController::class, 'subscribe'])->name('subscribe.store');
Route::get('/subscribe/confirm/{token}', [SubscriberController::class, 'confirm'])->name('subscribe.confirm');
Route::get('/subscribe/unsubscribe/{token}', [SubscriberController::class, 'unsubscribe'])->name('subscribe.unsubscribe');
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/robots.txt', [SitemapController::class, 'robots'])->name('robots');
Route::get('/search', [ShopController::class, 'category'])->name('shop.search');
Route::get('/products', [ShopController::class, 'category'])->name('shop.category');
Route::get('/products/{slug}', [ProductController::class, 'show'])->name('product.show');
Route::get('/lang/{locale}', [LocaleController::class, 'switch'])
    ->where('locale', 'en|bn')
    ->name('lang.switch');

// Cart — accessible by guests (session) and logged-in users
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart', [CartController::class, 'store'])->name('cart.store');
Route::patch('/cart/{cartItem}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/{cartItem}', [CartController::class, 'destroy'])->name('cart.destroy');

// Checkout — guests auto-register/login on order placement
Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
Route::post('/checkout/shipping-rate', [CheckoutController::class, 'shippingRate'])->middleware('throttle:30,1')->name('checkout.shipping-rate');
Route::post('/checkout/apply-coupon', [CheckoutController::class, 'applyCoupon'])->middleware('throttle:10,1')->name('checkout.apply-coupon');
Route::post('/checkout/remove-coupon', [CheckoutController::class, 'removeCoupon'])->name('checkout.remove-coupon');
Route::post('/checkout/place-order', [CheckoutController::class, 'placeOrder'])->middleware('throttle:10,1')->name('checkout.place-order');
Route::get('/checkout/confirmation/{order}', [CheckoutController::class, 'confirmation'])->name('checkout.confirmation');

Route::middleware('auth')->group(function () {
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::get('/orders/{order}/invoice', [OrderController::class, 'invoice'])->name('orders.invoice');

    Route::post('/products/{product}/reviews', [ReviewController::class, 'store'])->name('reviews.store');
    Route::post('/reviews/{review}/vote', [ReviewController::class, 'vote'])->name('reviews.vote');

    Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
    Route::post('/wishlist', [WishlistController::class, 'store'])->name('wishlist.store');
    Route::delete('/wishlist/{product}', [WishlistController::class, 'destroy'])->name('wishlist.destroy');
    Route::post('/wishlist/{product}/move-to-cart', [WishlistController::class, 'moveToCart'])->name('wishlist.move-to-cart');

    Route::get('/payment/initiate', [PaymentController::class, 'initiate'])->name('payment.initiate');
    Route::get('/payment/retry/{order}', [PaymentController::class, 'retry'])->name('payment.retry');

    // Account hub
    Route::get('/account', [AccountController::class, 'index'])->name('account.index');
    Route::get('/account/reviews', [AccountController::class, 'reviews'])->name('account.reviews');
    Route::get('/account/password', [AccountController::class, 'password'])->name('account.password');
    Route::put('/account/password', [AccountController::class, 'updatePassword'])->name('account.password.update');

    // Address book
    Route::get('/account/addresses', [UserAddressController::class, 'index'])->name('account.addresses');
    Route::post('/account/addresses', [UserAddressController::class, 'store'])->name('account.addresses.store');
    Route::put('/account/addresses/{address}', [UserAddressController::class, 'update'])->name('account.addresses.update');
    Route::delete('/account/addresses/{address}', [UserAddressController::class, 'destroy'])->name('account.addresses.destroy');
    Route::post('/account/addresses/{address}/default', [UserAddressController::class, 'setDefault'])->name('account.addresses.default');
});

// Public result page — user lands here after gateway redirect (no auth needed)
Route::get('/payment/result/{orderNumber}', [PaymentController::class, 'result'])->name('payment.result');

// Admin CSV export — requires Filament admin session
Route::get('/admin/reports/export', [ReportExportController::class, 'export'])
    ->middleware(['web', \Filament\Http\Middleware\Authenticate::class])
    ->name('admin.reports.export');

// Social Login (Google OAuth) — admin-controlled via Settings
Route::get('/auth/google/redirect', [SocialAuthController::class, 'redirectToGoogle'])->name('auth.google.redirect');
Route::get('/auth/google/callback', [SocialAuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');

require __DIR__.'/auth.php';
