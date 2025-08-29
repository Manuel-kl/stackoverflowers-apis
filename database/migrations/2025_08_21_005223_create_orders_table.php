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
        if (!Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('status');
                $table->decimal('total_amount', 10, 2)->default(0);
                $table->string('currency', 10)->default('KES');
                $table->string('payment_reference')->nullable();
                $table->timestamps();
            });
        }
    }
};
