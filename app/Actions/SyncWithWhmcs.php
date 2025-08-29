<?php

namespace App\Actions;

use App\Enums\OrderStatusEnum;
use App\Models\Order;
use App\Services\WhmcsService;
use Illuminate\Support\Facades\Date;

class SyncWithWhmcs
{
    public function __construct(private WhmcsService $whmcs) {}

    public function syncPendingAndFailed(): int
    {
        $count = 0;

        Order::with('items', 'user')
            ->whereNull('whmcs_order_id')
            ->orWhere('external_status', 'sync_failed')
            ->chunkById(100, function ($orders) use (&$count) {
                foreach ($orders as $order) {
                    try {
                        dispatch_sync(new \App\Jobs\SyncOrderToWhmcs($order));
                        $count++;
                    } catch (\Throwable $e) {
                    }
                }
            });

        return $count;
    }

    public function refreshPaidStatuses(): int
    {
        $count = 0;

        Order::whereNotNull('whmcs_invoice_id')
            ->chunkById(100, function ($orders) use (&$count) {
                foreach ($orders as $order) {
                    try {
                        $invoice = $this->whmcs->getInvoice((int) $order->whmcs_invoice_id);
                        $status = strtolower($invoice['status'] ?? '');

                        if ($status === 'paid' && $order->status->value !== OrderStatusEnum::PAID->value) {
                            $order->update([
                                'status' => OrderStatusEnum::PAID->value,
                                'external_status' => 'paid',
                                'external_synced_at' => Date::now(),
                            ]);
                            $count++;
                        }
                    } catch (\Throwable $e) {
                    }
                }
            });

        return $count;
    }
}
