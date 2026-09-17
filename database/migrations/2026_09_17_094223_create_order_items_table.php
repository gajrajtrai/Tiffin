<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('menu_item_id')->constrained('menu_items')->restrictOnDelete();

            // Snapshots — so later menu edits don't rewrite order history
            $table->string('item_name');
            $table->decimal('item_price', 8, 2);
            $table->boolean('is_veg')->default(true);
            $table->string('item_type', 20)->default('fastfood');

            // No quantity column — always 1 per your requirement.

            $table->timestamps();

            // An order can't include the same menu item twice
            $table->unique(['order_id', 'menu_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};