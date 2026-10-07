<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Idempotent — skip if a unique index already exists on the column
        $existing = DB::select(
            "SHOW INDEX FROM orders WHERE Column_name = 'order_number' AND Non_unique = 0"
        );

        if (! empty($existing)) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->unique('order_number', 'orders_order_number_unique');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique('orders_order_number_unique');
        });
    }
};