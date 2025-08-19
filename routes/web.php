<?php

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
