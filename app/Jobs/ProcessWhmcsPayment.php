<?php

namespace App\Jobs;

use App\Models\Payment;
use App\Services\WhmcsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessWhmcsPayment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60; // 1 minute backoff

    public function __construct(
        private int $paymentId,
        private array $paymentData
    ) {}

    public function handle(WhmcsService $whmcsService): void
    {
        $payment = Payment::with('order:id,whmcs_invoice_id,whmcs_order_id')->find($this->paymentId);

        if (!$payment || !$payment->order) {
            Log::error('Payment or order not found for WHMCS processing', [
                'payment_id' => $this->paymentId,
            ]);

            return;
        }

        if (!$payment->order->whmcs_invoice_id) {
            Log::warning('No WHMCS invoice ID found for payment', [
                'payment_id' => $this->paymentId,
                'order_id' => $payment->order->id,
            ]);

            return;
        }

        try {
            Log::info('Starting WHMCS invoice payment process (background)', [
                'payment_id' => $payment->id,
                'order_id' => $payment->order->id,
                'whmcs_invoice_id' => $payment->order->whmcs_invoice_id,
                'whmcs_order_id' => $payment->order->whmcs_order_id,
            ]);

            $invoicePaymentResult = $whmcsService->addInvoicePayment([
                'invoiceid' => (int) $payment->order->whmcs_invoice_id,
                'transid' => $this->paymentData['reference'],
                'gateway' => $this->paymentData['gateway'],
                'date' => $this->paymentData['date'],
                'noemail' => false,
            ]);

            Log::info('WHMCS invoice payment result (background)', [
                'payment_id' => $payment->id,
                'whmcs_invoice_id' => $payment->order->whmcs_invoice_id,
                'result' => $invoicePaymentResult,
            ]);

            if ($payment->order->whmcs_order_id) {
                Log::info('Starting WHMCS order acceptance (background)', [
                    'payment_id' => $payment->id,
                    'whmcs_order_id' => $payment->order->whmcs_order_id,
                ]);

                $acceptOrderResult = $whmcsService->acceptOrder(
                    (int) $payment->order->whmcs_order_id
                );

                Log::info('WHMCS accept order result (background)', [
                    'payment_id' => $payment->id,
                    'whmcs_order_id' => $payment->order->whmcs_order_id,
                    'result' => $acceptOrderResult,
                ]);

                if (($acceptOrderResult['result'] ?? 'error') === 'success') {
                    Log::info('WHMCS order successfully accepted and processed', [
                        'payment_id' => $payment->id,
                        'order_id' => $payment->order->id,
                    ]);
                }
            } else {
                Log::warning('No WHMCS order ID found for payment (background)', [
                    'payment_id' => $payment->id,
                    'order_id' => $payment->order->id,
                ]);
            }

        } catch (\Throwable $e) {
            Log::error('Failed to process WHMCS payment in background', [
                'payment_id' => $this->paymentId,
                'invoice_id' => $payment->order->whmcs_invoice_id,
                'order_id' => $payment->order->whmcs_order_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessWhmcsPayment job failed after all retries', [
            'payment_id' => $this->paymentId,
            'error' => $exception->getMessage(),
        ]);
    }
}
