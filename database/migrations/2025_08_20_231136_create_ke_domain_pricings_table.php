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
        Schema::create('ke_domain_pricings', function (Blueprint $table) {
            $table->id();
            $table->string('tld', 20)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('registration_price', 10, 2);
            $table->decimal('renewal_price', 10, 2);
            $table->decimal('transfer_price', 10, 2);
            $table->decimal('grace_fee', 10, 2);
            $table->integer('grace_days');
            $table->integer('redemption_days');
            $table->decimal('redemption_fee', 10, 2)->default(-1.00);
            $table->json('years')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ke_domain_pricings');
    }
};
