<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('is_manual')->default(false)->after('payment_status');
            $table->foreignId('entered_by')->nullable()->after('is_manual')
                  ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['entered_by']);
            $table->dropColumn(['is_manual', 'entered_by']);
        });
    }
};