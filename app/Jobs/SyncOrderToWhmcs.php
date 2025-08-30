<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\WhmcsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Date;

class SyncOrderToWhmcs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public int $backoff = 300;

    public function __construct(private Order $order) {}

    public function handle(WhmcsService $whmcs): void
    {
        if ($this->order->whmcs_order_id) {
            return;
        }

        $user = $this->order->user;

        $domains = [];
        $domaintype = [];
        $regperiod = [];
        $billingcycle = [];

        foreach ($this->order->items as $item) {
            $domains[] = $item->domain_name;
            $domaintype[] = 'register';
            $regperiod[] = $item->number_of_years ?? 1;
            $billingcycle[] = 'annually';
        }

        $payload = [
            'clientid' => $user->whmcs_client_id,
            'paymentmethod' => 'mpesa',
            'domain' => $domains,
            'domaintype' => $domaintype,
            'regperiod' => $regperiod,
            'billingcycle' => $billingcycle,
            'nameserver1' => 'ns1.he.net',
            'nameserver2' => 'ns2.he.net',
        ];

        $result = $whmcs->addOrder($payload);

        $this->order->update([
            'whmcs_order_id' => $result['orderid'] ?? null,
            'whmcs_invoice_id' => $result['invoiceid'] ?? null,
            'external_status' => 'synced',
            'external_synced_at' => Date::now(),
        ]);

        $domainIds = $this->splitIdList($result['domainids'] ?? '');
        $serviceIds = $this->splitIdList($result['serviceids'] ?? '');

        foreach ($this->order->items as $index => $item) {
            $item->update([
                'whmcs_domain_id' => $domainIds[$index] ?? null,
                'whmcs_service_id' => $serviceIds[$index] ?? null,
            ]);
        }
    }

    public function failed(\Throwable $e): void
    {
        $this->order->update([
            'external_status' => 'sync_failed',
        ]);
    }

    private function splitIdList(?string $list): array
    {
        if (!$list) {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $list)), fn ($v) => $v !== ''));
    }
}
