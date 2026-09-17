<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_receipt_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('goods_receipt_id')->constrained('goods_receipts')->cascadeOnDelete();

            // Which PO line this fulfils (null for walk-in receipts)
            $table->foreignId('purchase_order_item_id')->nullable()
                  ->constrained('purchase_order_items')->nullOnDelete();

            $table->foreignId('inventory_item_id')->nullable()
                  ->constrained('inventory_items')->nullOnDelete();

            // Snapshots
            $table->string('item_name');
            $table->string('unit', 20);

            $table->decimal('quantity_received', 12, 3);
            $table->decimal('unit_cost', 10, 2);
            $table->decimal('line_total', 10, 2);

            // Optional batch tracking for perishables
            $table->string('batch_code')->nullable();
            $table->date('expiry_date')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('goods_receipt_id');
            $table->index('purchase_order_item_id');
            $table->index('inventory_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_items');
    }
};