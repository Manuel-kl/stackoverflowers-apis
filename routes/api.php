<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\OtpController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\Domain\DomainController;
use App\Http\Controllers\Domain\DomainPricingController;
use App\Http\Controllers\Domain\RegisterDomainController;
use App\Http\Controllers\Domain\RenewDomainController;
use App\Http\Controllers\Domain\TldController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\User\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/signup', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/domains/search', [DomainController::class, 'index']);
Route::post('/domains/suggestions', [DomainController::class, 'suggestions']);
Route::post('/domains/renew', [RenewDomainController::class, 'index']);
Route::post('/domains/register', [RegisterDomainController::class, 'index']);
Route::get('/domains/pricing/{type}', [DomainPricingController::class, 'index']);
Route::get('tlds', [TldController::class, 'all']);
Route::get('tlds/pricing', [TldController::class, 'pricing']);

Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::get('/user', [UserController::class, 'index']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/otp/send', [OtpController::class, 'sendVerificationCode']);
    Route::post('/otp/verify', [OtpController::class, 'verifyCode']);

    // Cart routes
    Route::delete('/cart/items/{cartItem}', [CartController::class, 'removeFromCart']);
    Route::apiResource('cart', CartController::class)->only(['index', 'store', 'destroy']);

    // Order routes
    Route::apiResource('orders', OrderController::class)->only(['index', 'store', 'show']);
});
