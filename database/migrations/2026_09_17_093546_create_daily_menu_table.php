<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_menu', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_item_id')->constrained('menu_items')->cascadeOnDelete();
            $table->date('service_date');
            $table->timestamps();

            // One row per item per day
            $table->unique(['menu_item_id', 'service_date']);
            $table->index('service_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_menu');
    }
};