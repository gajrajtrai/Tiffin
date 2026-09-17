<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('sku', 40)->nullable()->unique();

            // vegetables | meat | dry_goods | dairy | packaging | other
            $table->string('category', 40)->default('other');

            // kg | g | L | ml | pcs | pack | bottle
            $table->string('unit', 20)->default('kg');

            $table->decimal('current_stock', 12, 3)->default(0);
            $table->decimal('reorder_level', 12, 3)->default(0);

            // Last known purchase price per unit (cache, authoritative record is on stock_movements)
            $table->decimal('unit_cost', 10, 2)->default(0);

            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['category', 'is_active']);
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};