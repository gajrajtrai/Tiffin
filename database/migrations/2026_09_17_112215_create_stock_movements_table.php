<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('inventory_item_id')->constrained('inventory_items')->restrictOnDelete();

            // in   = received stock (usually from a goods receipt)
            // out  = consumed in the kitchen (often linked to an order)
            // waste = spoiled, expired, damaged
            // adjustment = physical count correction (can be positive or negative)
            $table->string('type', 20);

            // Signed: positive = stock in, negative = stock out
            $table->decimal('quantity', 12, 3);

            // Balance snapshots for audit trail
            $table->decimal('balance_before', 12, 3);
            $table->decimal('balance_after', 12, 3);

            // Only used for 'in' movements — records actual purchase price
            $table->decimal('unit_cost', 10, 2)->nullable();

            $table->string('reason')->nullable();
            $table->text('notes')->nullable();

            // Polymorphic link: GoodsReceipt, Order, or null (manual entry)
            $table->nullableMorphs('reference');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['inventory_item_id', 'created_at']);
            $table->index(['type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};