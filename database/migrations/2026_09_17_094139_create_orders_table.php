<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            // Human-readable reference: TTF-20260917-0001
            $table->string('order_number', 30)->unique();

            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();

            // The date the meal is FOR (not when the order was placed)
            $table->date('service_date');

            // delivery | pickup
            $table->string('delivery_method', 20)->default('delivery');

            // Nullable because pickup orders don't use a slot
            // Single slot for now ("11:00-14:00"), kept as string for future splits
            $table->string('delivery_slot', 40)->nullable();

            $table->decimal('total', 10, 2);

            // pending | confirmed | preparing | ready | delivered | picked_up | cancelled
            $table->string('status', 20)->default('pending');

            // paid | unpaid | refunded  (always 'paid' in wallet flow, kept for future)
            $table->string('payment_status', 20)->default('paid');

            // Link to the debit transaction that paid for this order
            $table->foreignId('wallet_transaction_id')->nullable()
                  ->constrained('wallet_transactions')->nullOnDelete();

            $table->text('notes')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancelled_reason')->nullable();

            $table->timestamps();

            $table->index(['service_date', 'status']);
            $table->index(['user_id', 'service_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};