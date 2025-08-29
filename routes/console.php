<?php

use App\Actions\SyncWithWhmcs;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('orders:sync-whmcs', function (SyncWithWhmcs $sync) {
    $resynced = $sync->syncPendingAndFailed();
    $paid = $sync->refreshPaidStatuses();

    $this->info("Resynced: {$resynced}, Paid updated: {$paid}");
})->purpose('Sync local orders with WHMCS (push missing, refresh paid statuses)');

Schedule::command('orders:sync-whmcs')->everyFifteenMinutes();
