<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();

            // Human-readable: EXP-20260917-0001
            $table->string('expense_number', 30)->unique();

            $table->foreignId('expense_category_id')
                  ->constrained('expense_categories')->restrictOnDelete();

            $table->foreignId('supplier_id')->nullable()
                  ->constrained('suppliers')->nullOnDelete();

            // For expenses tied to a specific goods receipt (auto-generated)
            $table->foreignId('goods_receipt_id')->nullable()
                  ->constrained('goods_receipts')->nullOnDelete();

            $table->date('expense_date');
            $table->string('description');
            $table->decimal('amount', 12, 2);

            // cash | bank_transfer | wallet | cheque | other
            $table->string('payment_method', 30)->default('cash');

            // Optional reference: cheque no., bank ref, voucher no.
            $table->string('payment_reference')->nullable();

            // draft | approved | paid
            $table->string('status', 20)->default('approved');

            // Medialibrary handles receipt image — no file path column here.

            $table->foreignId('recorded_by')->nullable()
                  ->constrained('users')->nullOnDelete();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['expense_date', 'status']);
            $table->index('expense_category_id');
            $table->index('supplier_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};