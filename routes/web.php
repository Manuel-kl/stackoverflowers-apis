<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminManagementController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\TaxController;
use App\Http\Controllers\MailController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::get('/mg', function () {
    Artisan::call('migrate');

    return Artisan::output();
});

Route::get('/', function () {
    return view('welcome');
});

// Mail integration routes
Route::prefix('mail')->name('mail.')->group(function () {
    Route::get('/', [MailController::class, 'index'])->name('index');
    Route::get('/connect', [MailController::class, 'index'])->name('connect.view');
    Route::post('/connect', [MailController::class, 'connectGoogle'])->name('connect');
    Route::get('/callback', [MailController::class, 'handleCallback'])->name('callback');
});

// Admin routes
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('login.post');

    Route::middleware('admin')->group(function () {
        Route::get('/dashboard', [AdminAuthController::class, 'dashboard'])->name('dashboard');
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

        // Super admin routes
        Route::middleware('super_admin')->group(function () {
            Route::resource('admins', AdminManagementController::class);
        });

        // Tax management routes
        Route::resource('taxes', TaxController::class);

        // Order management routes
        Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    });
});
