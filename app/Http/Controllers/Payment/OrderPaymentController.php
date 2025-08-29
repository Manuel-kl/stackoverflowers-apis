<?php

namespace App\Http\Controllers\Payment;

use App\Enums\PaymentStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\OrderPaymentRequest;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Payments\PaystackService;

class OrderPaymentController extends Controller
{
    public function __construct(private PaystackService $paystackSvc) {}

    public function payOrder(OrderPaymentRequest $request, Order $order)
    {
        $data = $request->validated();

        $order->load(['items', 'user']);

        $totalAmount = $order->items->sum(fn ($item) => (float) $item->price);

        $response = match ($data['payment_method']) {
            'mpesa' => $this->paystackSvc->stkPush([
                'order' => $order,
                'amount' => $totalAmount,
                'phone_number' => $data['phone_number'],
            ]),
            'card' => !empty($data['authorization_code'])
                ? $this->paystackSvc->chargeWithAuthorization([
                    'order' => $order,
                    'amount' => $totalAmount,
                    'authorization_code' => $data['authorization_code'],
                ])
                : $this->paystackSvc->cardPayment([
                    'order' => $order,
                    'amount' => $totalAmount,
                    'card_number' => $data['card_number'],
                    'cvv' => $data['cvv'],
                    'expiry_month' => $data['expiry_month'],
                    'expiry_year' => $data['expiry_year'],
                ]),
        };

        $payment = Payment::create([
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'amount' => $totalAmount,
            'currency' => $order->currency ?? 'KES',
            'status' => $response['data']['status'] ?? PaymentStatusEnum::PENDING->value,
            'payment_method' => $data['payment_method'],
            'payment_reference' => $response['data']['reference'] ?? null,
            'transaction_id' => $response['data']['id'] ?? null,
        ]);

        $payment['payment_url'] = $response['data']['url'] ?? null;

        if (!empty($payment->payment_reference)) {
            $order->update(['payment_reference' => $payment->payment_reference]);
        }
        $payment['order'] = $order;

        return response()->json([
            'success' => true,
            'data' => $payment,
            'message' => 'Payment initiated',
        ], 200);
    }
}
