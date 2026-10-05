<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Requests\ExportReportRequest;
use App\Modules\Expense\Models\Expense;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use App\Models\User;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportController
{
    public function __invoke(ExportReportRequest $request): StreamedResponse
    {
        $type = $request->exportType();
        $from = $request->fromDate();
        $to = $request->toDate();
        $fromStr = $from->toDateString();
        $toStr = $to->toDateString();

        $filename = sprintf(
            'chula-tiffins-%s-%s-to-%s.csv',
            $type,
            $fromStr,
            $toStr,
        );

        return match ($type) {
            'orders'    => $this->exportOrders($fromStr, $toStr, $filename),
            'items'     => $this->exportItems($fromStr, $toStr, $filename),
            'customers' => $this->exportCustomers($fromStr, $toStr, $filename),
            'expenses'  => $this->exportExpenses($fromStr, $toStr, $filename),
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Orders
    |--------------------------------------------------------------------------
    */

    protected function exportOrders(string $from, string $to, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($from, $to) {
            $out = fopen('php://output', 'w');

            fputcsv($out, [
                'Order Number',
                'Service Date',
                'Placed At',
                'Customer',
                'Mobile',
                'Delivery Method',
                'Delivery Slot',
                'Items',
                'Total (Nu.)',
                'Payment Status',
                'Status',
            ]);

            Order::query()
                ->with(['user', 'items'])
                ->whereBetween('service_date', [$from, $to])
                ->orderBy('service_date')
                ->orderBy('id')
                ->chunk(500, function ($orders) use ($out) {
                    foreach ($orders as $order) {
                        $items = $order->items
                            ->map(fn ($i) => ($i->quantity > 1 ? $i->quantity.'× ' : '').$i->item_name)
                            ->implode(' | ');

                        fputcsv($out, [
                            $order->order_number,
                            $order->service_date?->toDateString(),
                            $order->created_at?->toDateTimeString(),
                            $order->user?->name,
                            $order->user?->mobile,
                            $order->delivery_method,
                            $order->delivery_slot,
                            $items,
                            number_format((float) $order->total, 2, '.', ''),
                            $order->payment_status,
                            $order->status,
                        ]);
                    }
                });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Items
    |--------------------------------------------------------------------------
    */

    protected function exportItems(string $from, string $to, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($from, $to) {
            $out = fopen('php://output', 'w');

            fputcsv($out, [
                'Item',
                'Type',
                'Diet',
                'Orders',
                'Quantity Sold',
                'Revenue (Nu.)',
            ]);

            OrderItem::query()
                ->selectRaw('
                    item_name,
                    item_type,
                    is_veg,
                    COUNT(*) as order_count,
                    SUM(quantity) as total_quantity,
                    SUM(item_price * quantity) as revenue
                ')
                ->whereHas('order', function ($q) use ($from, $to) {
                    $q->whereBetween('service_date', [$from, $to])
                      ->where('status', '!=', Order::STATUS_CANCELLED);
                })
                ->groupBy('item_name', 'item_type', 'is_veg')
                ->orderByDesc('total_quantity')
                ->chunk(500, function ($rows) use ($out) {
                    foreach ($rows as $row) {
                        fputcsv($out, [
                            $row->item_name,
                            $row->item_type === 'main' ? 'Main' : 'Fast Food',
                            $row->is_veg ? 'Veg' : 'Non-Veg',
                            $row->order_count,
                            $row->total_quantity,
                            number_format((float) $row->revenue, 2, '.', ''),
                        ]);
                    }
                });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Customers
    |--------------------------------------------------------------------------
    */

    protected function exportCustomers(string $from, string $to, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($from, $to) {
            $out = fopen('php://output', 'w');

            fputcsv($out, [
                'Customer',
                'Mobile',
                'Orders',
                'Total Spend (Nu.)',
                'Wallet Balance (Nu.)',
                'Status',
            ]);

            User::query()
                ->selectRaw('
                    users.id,
                    users.name,
                    users.mobile,
                    users.status,
                    users.wallet_balance,
                    COUNT(orders.id) as order_count,
                    COALESCE(SUM(orders.total), 0) as total_spend
                ')
                ->join('orders', function ($join) use ($from, $to) {
                    $join->on('orders.user_id', '=', 'users.id')
                         ->whereBetween('orders.service_date', [$from, $to])
                         ->where('orders.status', '!=', Order::STATUS_CANCELLED);
                })
                ->groupBy('users.id', 'users.name', 'users.mobile', 'users.status', 'users.wallet_balance')
                ->orderByDesc('total_spend')
                ->chunk(500, function ($rows) use ($out) {
                    foreach ($rows as $row) {
                        fputcsv($out, [
                            $row->name,
                            $row->mobile,
                            $row->order_count,
                            number_format((float) $row->total_spend, 2, '.', ''),
                            number_format((float) $row->wallet_balance, 2, '.', ''),
                            $row->status,
                        ]);
                    }
                });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Expenses
    |--------------------------------------------------------------------------
    */

    protected function exportExpenses(string $from, string $to, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($from, $to) {
            $out = fopen('php://output', 'w');

            fputcsv($out, [
                'Expense #',
                'Date',
                'Category',
                'Supplier',
                'Description',
                'Amount (Nu.)',
                'Payment Method',
                'Status',
            ]);

            Expense::query()
                ->with(['category', 'supplier'])
                ->whereBetween('expense_date', [$from, $to])
                ->whereNull('voided_at')
                ->orderBy('expense_date')
                ->chunk(500, function ($expenses) use ($out) {
                    foreach ($expenses as $exp) {
                        fputcsv($out, [
                            $exp->expense_number,
                            $exp->expense_date?->toDateString(),
                            $exp->category?->name,
                            $exp->supplier?->name,
                            $exp->description,
                            number_format((float) $exp->amount, 2, '.', ''),
                            $exp->payment_method,
                            $exp->status,
                        ]);
                    }
                });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}