<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();

            // Nullable so inventory items can be deleted without breaking PO history
            $table->foreignId('inventory_item_id')->nullable()
                  ->constrained('inventory_items')->nullOnDelete();

            // Snapshots
            $table->string('item_name');
            $table->string('unit', 20);

            $table->decimal('quantity_ordered', 12, 3);
            $table->decimal('quantity_received', 12, 3)->default(0);

            $table->decimal('unit_cost', 10, 2);
            $table->decimal('line_total', 10, 2);

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('purchase_order_id');
            $table->index('inventory_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
    }
};