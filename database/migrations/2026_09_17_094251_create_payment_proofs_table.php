<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_proofs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // Amount claimed by the customer in the screenshot
            $table->decimal('claimed_amount', 10, 2);

            // Bank reference / remarks typed by the customer (optional)
            $table->string('bank_reference')->nullable();

            // Customer's note (e.g. "paid from mBoB, 12:45 PM")
            $table->text('note')->nullable();

            // pending | approved | rejected
            $table->string('status', 20)->default('pending');

            // Admin reviewer
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();

            // The credit transaction created on approval
            $table->foreignId('wallet_transaction_id')->nullable()
                  ->constrained('wallet_transactions')->nullOnDelete();

            // Medialibrary handles the screenshot file — no path column here.

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_proofs');
    }
};