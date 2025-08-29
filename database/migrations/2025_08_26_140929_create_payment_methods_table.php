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
        if (!Schema::hasTable('payment_methods')) {
            Schema::create('payment_methods', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('authorization_code');
                $table->string('last4');
                $table->string('exp_month');
                $table->string('exp_year');
                $table->string('channel');
                $table->boolean('reusable')->default(0);
                $table->string('mobile_money_number')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (Schema::hasColumn('payment_methods', 'user_id')) {
            Schema::table('payment_methods', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            });
        }
    }
};
