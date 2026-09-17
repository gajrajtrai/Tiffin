<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();

            // Human-readable: PO-20260917-0001
            $table->string('po_number', 30)->unique();

            $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();

            // Who placed the order (staff user)
            $table->foreignId('ordered_by')->nullable()->constrained('users')->nullOnDelete();

            $table->date('order_date');
            $table->date('expected_date')->nullable();

            // draft | sent | partially_received | received | cancelled
            $table->string('status', 25)->default('draft');

            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);

            $table->text('notes')->nullable();

            $table->timestamp('sent_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancelled_reason')->nullable();

            $table->timestamps();

            $table->index(['status', 'order_date']);
            $table->index('supplier_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};