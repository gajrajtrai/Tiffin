<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_days', function (Blueprint $table) {
            $table->id();
            $table->date('service_date')->unique();
            $table->boolean('is_open')->default(true);

            // Null = use the default from settings table
            $table->time('delivery_cutoff_time')->nullable();
            $table->unsignedSmallInteger('max_delivery_capacity')->nullable();

            // Reason for closure, holiday name, "class in session", etc.
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_days');
    }
};