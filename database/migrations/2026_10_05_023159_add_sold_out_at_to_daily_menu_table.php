<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_menu', function (Blueprint $table) {
            $table->timestamp('sold_out_at')->nullable()->after('service_date');
        });
    }

    public function down(): void
    {
        Schema::table('daily_menu', function (Blueprint $table) {
            $table->dropColumn('sold_out_at');
        });
    }
};