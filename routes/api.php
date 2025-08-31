<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\OtpController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\Domain\DomainController;
use App\Http\Controllers\Domain\DomainNameserversController;
use App\Http\Controllers\Domain\DomainPricingController;
use App\Http\Controllers\Domain\DomainSettingController;
use App\Http\Controllers\Domain\RegisterDomainController;
use App\Http\Controllers\Domain\RenewDomainController;
use App\Http\Controllers\Domain\TldController;
use App\Http\Controllers\Domain\UserDomainController;
use App\Http\Controllers\DomainTaxController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\Payment\OrderPaymentController;
use App\Http\Controllers\Payment\PaystackController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\Whmcs\CreateOrderController;
use App\Http\Controllers\Whmcs\TicketController;
use Illuminate\Support\Facades\Route;

Route::post('/signup', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/domains/search', [DomainController::class, 'check']);
Route::post('/domains/suggestions', [DomainController::class, 'suggestions']);
Route::post('/domains/renew', [RenewDomainController::class, 'index']);
Route::post('/domains/register', [RegisterDomainController::class, 'index']);
Route::get('/domains/pricing/{type}', [DomainPricingController::class, 'index']);
Route::get('tlds', [TldController::class, 'all']);
Route::get('tlds/pricing', [TldController::class, 'pricing']);
Route::get('/domains/epp-code', [DomainSettingController::class, 'getEppCode']);
Route::post('/domains/update-lock-status', [DomainSettingController::class, 'updateLockingStatus']);
Route::apiResource('domains/nameservers', DomainNameserversController::class)->only(['index', 'store']);

// taxes
Route::get('/taxes', [DomainTaxController::class, 'index']);

Route::group(['middleware' => 'auth:sanctum'], function () {

    // User routes
    Route::get('/user', [UserController::class, 'index']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/otp/send', [OtpController::class, 'sendVerificationCode']);
    Route::post('/otp/verify', [OtpController::class, 'verifyCode']);
    Route::post('/password/change', [PasswordResetController::class, 'changePassword']);
    Route::delete('/user/delete', [AuthController::class, 'deleteUser']);
    Route::post('/user/details', [UserController::class, 'userDetails']);
    Route::get('/user/whmcs-details', [UserController::class, 'checkWhmcsUserDetails']);

    // User domains
    Route::get('/user/domains', [UserDomainController::class, 'index']);

    // Cart routes
    Route::delete('/cart/items/{cartItem}', [CartController::class, 'removeFromCart']);
    Route::apiResource('cart', CartController::class)->only(['index', 'store', 'destroy']);

    // Order routes
    Route::apiResource('orders', OrderController::class)->only(['index', 'store', 'show']);

    // Payment routes
    Route::post('/orders/{order}/pay', [OrderPaymentController::class, 'payOrder']);
    Route::get('/payments/query', [PaystackController::class, 'queryTransaction']);

    // Support and tickets
    Route::apiResource('tickets', TicketController::class);
    Route::post('/tickets/{id}/reply', [TicketController::class, 'ticketReply']);

});

// Route::get('/check', [DomainController::class, 'check']);

// Test routes
Route::prefix('test')->group(function () {
    Route::post('/whmcs/create-order', [CreateOrderController::class, 'addOrder']);
});
