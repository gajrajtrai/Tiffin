<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Bhutanese mobile number: +975 followed by 8 digits
            $table->string('mobile', 20)->nullable()->unique()->after('email');

            $table->decimal('wallet_balance', 10, 2)->default(0)->after('password');
            $table->decimal('low_balance_threshold', 10, 2)->default(500)->after('wallet_balance');
            $table->string('status', 20)->default('active')->after('low_balance_threshold');
        });

        // Customers register with mobile only — email becomes optional
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['mobile', 'wallet_balance', 'low_balance_threshold', 'status']);
        });

        // Restore email to NOT NULL — only safe if no null rows exist
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change();
        });
    }
};