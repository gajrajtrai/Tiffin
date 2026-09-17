<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_receipts', function (Blueprint $table) {
            $table->id();

            // Human-readable: GR-20260917-0001
            $table->string('receipt_number', 30)->unique();

            // Can receive without a PO (walk-in purchase)
            $table->foreignId('purchase_order_id')->nullable()
                  ->constrained('purchase_orders')->nullOnDelete();

            $table->foreignId('supplier_id')->nullable()
                  ->constrained('suppliers')->nullOnDelete();

            $table->foreignId('received_by')->nullable()
                  ->constrained('users')->nullOnDelete();

            $table->date('received_date');

            // draft | confirmed
            $table->string('status', 20)->default('draft');

            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);

            $table->text('notes')->nullable();

            $table->timestamp('confirmed_at')->nullable();

            $table->timestamps();

            $table->index(['status', 'received_date']);
            $table->index('supplier_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receipts');
    }
};