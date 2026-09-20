<?php

namespace App\Modules\Order\Services;

use App\Models\User;
use App\Modules\Menu\Models\DailyMenu;
use App\Modules\Menu\Models\MenuItem;
use App\Modules\Menu\Models\ServiceDay;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Payment\Services\WalletService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class OrderPlacementService
{
    public function __construct(
        protected WalletService $wallet,
    ) {}

    /**
     * Place a customer order for today.
     *
     * @param array<int> $menuItemIds
     */
    public function place(
        User $customer,
        array $menuItemIds,
        string $deliveryMethod,
        ?string $deliverySlot = null,
        ?string $notes = null,
    ): Order {
        if (! $customer->hasRole('Customer')) {
            throw new RuntimeException('Only customers can place orders.');
        }

        if (empty($menuItemIds)) {
            throw new RuntimeException('Please select at least one item.');
        }

        if (! in_array($deliveryMethod, [Order::METHOD_DELIVERY, Order::METHOD_PICKUP], true)) {
            throw new RuntimeException('Invalid delivery method.');
        }

        $date = today();
        $serviceDay = ServiceDay::forDate($date);

        if (! $serviceDay->is_open) {
            throw new RuntimeException('We are closed today. Please order on a service day.');
        }

        if ($deliveryMethod === Order::METHOD_DELIVERY && $serviceDay->isPastCutoff()) {
            throw new RuntimeException(
                'Delivery cut-off was at '.$serviceDay->effectiveCutoffTime()
                .'. Please choose pickup instead.'
            );
        }

        // One active order per customer per day
        $hasActive = Order::query()
            ->where('user_id', $customer->id)
            ->whereDate('service_date', $date)
            ->whereNotIn('status', [
                Order::STATUS_CANCELLED,
                Order::STATUS_DELIVERED,
                Order::STATUS_PICKED_UP,
            ])
            ->exists();

        if ($hasActive) {
            throw new RuntimeException('You already have an active order for today.');
        }

        // Load items, ensure they're active
        $items = MenuItem::query()
            ->whereIn('id', $menuItemIds)
            ->where('is_active', true)
            ->get();

        if ($items->count() !== count($menuItemIds)) {
            throw new RuntimeException('One or more items are no longer available.');
        }

        // Every item must be published for today
        $publishedIds = DailyMenu::query()
            ->whereDate('service_date', $date)
            ->pluck('menu_item_id')
            ->all();

        foreach ($items as $item) {
            if (! in_array($item->id, $publishedIds, true)) {
                throw new RuntimeException('"'.$item->name.'" is not available today.');
            }
        }

        $total = (float) $items->sum('price');

        // Check wallet balance early (before DB transaction) for a friendly error
        $customer->refresh();

        if ((float) $customer->wallet_balance < $total) {
            throw new RuntimeException(sprintf(
                'Insufficient wallet balance. You have Nu. %s but this order costs Nu. %s. Please top up.',
                number_format((float) $customer->wallet_balance, 2),
                number_format($total, 2),
            ));
        }

        return DB::transaction(function () use ($customer, $items, $total, $deliveryMethod, $deliverySlot, $serviceDay, $notes) {
            $order = Order::create([
                'user_id'         => $customer->id,
                'service_date'    => today(),
                'delivery_method' => $deliveryMethod,
                'delivery_slot'   => $deliveryMethod === Order::METHOD_DELIVERY
                    ? ($deliverySlot ?: $serviceDay->effectiveCutoffTime().' – 14:00')
                    : null,
                'total'           => $total,
                'status'          => Order::STATUS_PENDING,
                'payment_status'  => 'paid',
                'notes'           => $notes,
            ]);

            foreach ($items as $item) {
                OrderItem::create([
                    'order_id'     => $order->id,
                    'menu_item_id' => $item->id,
                    'item_name'    => $item->name,
                    'item_price'   => $item->price,
                    'is_veg'       => $item->is_veg,
                    'item_type'    => $item->type,
                ]);
            }

            $txn = $this->wallet->debit(
                user:        $customer,
                amount:      $total,
                description: 'Order '.$order->order_number,
                reference:   $order,
                performedBy: null,
            );

            $order->wallet_transaction_id = $txn->id;
            $order->save();

            return $order->fresh(['items']);
        });
    }
}