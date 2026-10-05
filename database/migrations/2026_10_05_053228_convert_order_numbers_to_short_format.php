<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Convert all order numbers from "TTF-YYYYMMDD-NNNN" to "YYMMDD-NNN".
     * Sequence resets daily. Order preserved by service_date then id.
     */
    public function up(): void
    {
        $sequences = [];

        DB::table('orders')
            ->orderBy('service_date')
            ->orderBy('id')
            ->get(['id', 'service_date'])
            ->each(function ($order) use (&$sequences) {
                $date = Carbon::parse($order->service_date)->format('ymd');
                $sequences[$date] = ($sequences[$date] ?? 0) + 1;

                $newNumber = $date.'-'.str_pad((string) $sequences[$date], 3, '0', STR_PAD_LEFT);

                DB::table('orders')
                    ->where('id', $order->id)
                    ->update(['order_number' => $newNumber]);
            });
    }

    /**
     * Irreversible — we don't know what the old numbers were.
     * Rollback is a no-op; restore from backup if needed.
     */
    public function down(): void
    {
        //
    }
};