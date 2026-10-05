<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Convert three identifier families to their short formats:
     *   orders         : YYMMDD-NNN  →  YYMMDD-NN
     *   purchase_orders: PO-YYYYMMDD-NNNN → PYYMMDD-NN
     *   goods_receipts : GR-YYYYMMDD-NNNN → GYYMMDD-NN
     *
     * Sequence resets per day for each family. Chronological order preserved.
     */
    public function up(): void
    {
        // ─── Orders ─────────────────────────────────────────────
        $seq = [];
        DB::table('orders')
            ->orderBy('service_date')->orderBy('id')
            ->get(['id', 'service_date'])
            ->each(function ($row) use (&$seq) {
                $date = Carbon::parse($row->service_date)->format('ymd');
                $seq[$date] = ($seq[$date] ?? 0) + 1;
                DB::table('orders')->where('id', $row->id)->update([
                    'order_number' => $date.'-'.str_pad((string) $seq[$date], 2, '0', STR_PAD_LEFT),
                ]);
            });

        // ─── Purchase Orders ────────────────────────────────────
        $seq = [];
        DB::table('purchase_orders')
            ->orderBy('order_date')->orderBy('id')
            ->get(['id', 'order_date'])
            ->each(function ($row) use (&$seq) {
                $date = Carbon::parse($row->order_date)->format('ymd');
                $seq[$date] = ($seq[$date] ?? 0) + 1;
                DB::table('purchase_orders')->where('id', $row->id)->update([
                    'po_number' => 'P'.$date.'-'.str_pad((string) $seq[$date], 2, '0', STR_PAD_LEFT),
                ]);
            });

        // ─── Goods Receipts ─────────────────────────────────────
        $seq = [];
        DB::table('goods_receipts')
            ->orderBy('received_date')->orderBy('id')
            ->get(['id', 'received_date'])
            ->each(function ($row) use (&$seq) {
                $date = Carbon::parse($row->received_date)->format('ymd');
                $seq[$date] = ($seq[$date] ?? 0) + 1;
                DB::table('goods_receipts')->where('id', $row->id)->update([
                    'receipt_number' => 'G'.$date.'-'.str_pad((string) $seq[$date], 2, '0', STR_PAD_LEFT),
                ]);
            });
    }

    public function down(): void
    {
        // Irreversible — old long format carried no extra info we could restore.
    }
};