<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();

            // 'main' = main course (max 2/day); 'fastfood' = snacks, drinks, sides
            $table->string('type', 20)->default('fastfood');

            $table->boolean('is_veg')->default(true);
            $table->decimal('price', 8, 2);
            $table->string('image_path')->nullable();

            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};