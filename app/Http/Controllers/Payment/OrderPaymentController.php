<?php

namespace App\Http\Controllers\Payment;

use App\Enums\PaymentStatusEnum;
use App\Enums\TaxStatusEnum;
use App\Enums\TaxTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\OrderPaymentRequest;
use App\Models\DomainPurchaseDeduction;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentTaxes;
use App\Services\Payments\PaystackService;

class OrderPaymentController extends Controller
{
    public function __construct(private PaystackService $paystackSvc) {}

    public function payOrder(OrderPaymentRequest $request, Order $order)
    {
        $data = $request->validated();

        $order->load(['items', 'user']);

        $subtotalAmount = $order->items->sum(fn ($item) => (float) $item->price);

        $activeTaxes = PaymentTaxes::where('status', TaxStatusEnum::ACTIVE->value)->get();

        $percentageTaxes = $activeTaxes->where('type', TaxTypeEnum::PERCENTAGE->value);

        $percentageTotal = 0.0;
        foreach ($percentageTaxes as $tax) {
            $percentageTotal += round($subtotalAmount * ((float) $tax->value / 100));
        }

        $totalAmount = round($subtotalAmount + $percentageTotal);

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

        foreach ($order->items as $item) {
            foreach ($percentageTaxes as $tax) {
                $amount = round(((float) $item->price) * ((float) $tax->value / 100));
                if ($amount <= 0) {
                    continue;
                }

                DomainPurchaseDeduction::create([
                    'user_id' => $order->user_id,
                    'order_id' => $order->id,
                    'tax_id' => $tax->id,
                    'order_item_id' => $item->id,
                    'payment_id' => $payment->id,
                    'currency' => $order->currency ?? 'KES',
                    'amount' => $amount,
                ]);
            }
        }

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
