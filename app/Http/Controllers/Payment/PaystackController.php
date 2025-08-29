<?php

namespace App\Http\Controllers\Payment;

use App\Enums\OrderItemStatusEnum;
use App\Enums\OrderStatusEnum;
use App\HandlesExceptions;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessWhmcsPayment;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Services\Payments\PaystackService;
use App\Services\WhmcsService;
use Exception;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;

class PaystackController extends Controller
{
    use HandlesExceptions;

    public function __construct(private PaystackService $paystackSvc, private WhmcsService $whmcsService)
    {
        $this->paystackSvc = $paystackSvc;
    }

    public function queryTransaction(Request $request)
    {
        $data = $request->validate([
            'payment_reference' => 'required|string',
        ]);

        $transactionRef = $data['payment_reference'];
        $payment = null;

        try {
            $response = $this->paystackSvc->queryTransaction($transactionRef);

            $payment = Payment::with('order:id,user_id,whmcs_invoice_id,whmcs_order_id', 'taxes')->where('payment_reference', $transactionRef)->first();
            throw_if(!$payment, new \Exception('No payment found', 404));

            if ($response['data']['status'] === 'ongoing' || $response['data']['status'] === 'pending') {
                $maxRetries = 7;
                $retryCount = 0;

                while ($retryCount < $maxRetries) {
                    sleep(5);
                    $retryCount++;

                    $response = $this->paystackSvc->queryTransaction($transactionRef);

                    if ($response['data']['status'] !== 'ongoing' && $response['data']['status'] !== 'pending') {
                        break;
                    }
                }
            }

            if ($response['data']['status'] === 'ongoing' || $response['data']['status'] === 'pending') {
                return response()->json([
                    'success' => true,
                    'data' => $payment,
                    'message' => 'Transaction '.$response['data']['status'],
                ], 200);
            }

            if ($response['data']['status'] === 'success') {
                $authorizationCode = $response['data']['authorization']['authorization_code'];
                $is_existing = PaymentMethod::where('authorization_code', $authorizationCode)->exists();

                if (!$is_existing) {
                    PaymentMethod::create([
                        'user_id' => $payment->order->user_id,
                        'authorization_code' => $authorizationCode,
                        'last4' => $response['data']['authorization']['last4'],
                        'exp_month' => $response['data']['authorization']['exp_month'],
                        'exp_year' => $response['data']['authorization']['exp_year'],
                        'channel' => $response['data']['authorization']['channel'],
                        'reusable' => $response['data']['authorization']['reusable'],
                        'mobile_money_number' => $response['data']['authorization']['mobile_money_number'] ?? null,
                    ]);
                }

                $payment->update([
                    'status' => $response['data']['status'],
                    'paid_at' => Date::parse($response['data']['paid_at']),
                    'mpesa_code' => $response['data']['receipt_number'] == '' ? null : $response['data']['receipt_number'],
                    'transaction_id' => $response['data']['id'],
                ]);

                $payment->order->update([
                    'status' => OrderStatusEnum::PAID->value,
                ]);

                foreach ($payment->taxes as $tax) {
                    $tax->update(['payment_status' => $response['data']['status']]);
                }

                foreach ($payment->order->items as $item) {
                    $item->update(['status' => OrderItemStatusEnum::ACTIVE->value]);
                }

                if ($payment->order->whmcs_invoice_id) {
                    ProcessWhmcsPayment::dispatch($payment->id, [
                        'reference' => $payment->payment_reference,
                        'gateway' => 'mpesa',
                        'date' => Date::parse($response['data']['paid_at'])->format('Y-m-d H:i:s'),
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'data' => $payment,
                    'message' => 'Transaction successful',
                ], 200);
            }

            $payment->update([
                'status' => $response['data']['status'],
                'paid_at' => $response['data']['paid_at'],
                'mpesa_code' => $response['data']['receipt_number'] == '' ? null : $response['data']['receipt_number'],
                'transaction_id' => $response['data']['id'],
            ]);

            foreach ($payment->taxes as $tax) {
                $tax->update(['payment_status' => $response['data']['status']]);
            }

            foreach ($payment->order->items as $item) {
                $item->update(['status' => OrderItemStatusEnum::CANCELLED->value]);
            }

            return response()->json([
                'success' => true,
                'data' => $payment,
                'message' => 'Transaction '.$response['data']['status'],
            ], 200);
        } catch (RequestException $e) {
            return $this->handleException($e, 'Query transaction failed', 400);
        } catch (Exception $e) {
            return $this->handleException($e, $e->getMessage(), 500);
        }
    }
}
