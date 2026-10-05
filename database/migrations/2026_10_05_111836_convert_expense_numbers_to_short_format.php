<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Convert expense numbers from "EXP-YYYYMMDD-NNNN" to "EYYMMDD-NN".
     * Sequence resets daily. Chronological order preserved.
     */
    public function up(): void
    {
        $seq = [];

        DB::table('expenses')
            ->orderBy('expense_date')->orderBy('id')
            ->get(['id', 'expense_date'])
            ->each(function ($row) use (&$seq) {
                $date = Carbon::parse($row->expense_date)->format('ymd');
                $seq[$date] = ($seq[$date] ?? 0) + 1;

                DB::table('expenses')->where('id', $row->id)->update([
                    'expense_number' => 'E'.$date.'-'.str_pad((string) $seq[$date], 2, '0', STR_PAD_LEFT),
                ]);
            });
    }

    public function down(): void
    {
        // Irreversible — old format carried no extra info.
    }
};