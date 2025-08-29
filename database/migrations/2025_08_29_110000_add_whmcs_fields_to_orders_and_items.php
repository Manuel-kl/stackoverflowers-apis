<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('whmcs_order_id')->nullable()->after('payment_reference');
            $table->unsignedBigInteger('whmcs_invoice_id')->nullable()->after('whmcs_order_id');
            $table->string('external_status')->nullable()->after('status');
            $table->timestamp('external_synced_at')->nullable()->after('updated_at');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->unsignedBigInteger('whmcs_domain_id')->nullable()->after('registrar_order_id');
            $table->unsignedBigInteger('whmcs_service_id')->nullable()->after('whmcs_domain_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['whmcs_order_id', 'whmcs_invoice_id', 'external_status', 'external_synced_at']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['whmcs_domain_id', 'whmcs_service_id']);
        });
    }
};
