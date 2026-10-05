<?php

namespace App\Modules\Order\Services;
use App\Modules\Menu\Models\DailyMenu;
use App\Modules\Menu\Models\MenuItem;
use App\Modules\Menu\Models\ServiceDay;
use App\Modules\Order\Models\OrderItem;
use App\Models\User;
use App\Modules\Order\Models\Order;
use App\Modules\Payment\Services\WalletService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class OrderService
{
    public function __construct(
        protected WalletService $wallet,
    ) {}

    /**
     * Move an order to the next valid status.
     *
     * @throws RuntimeException on invalid transition
     */
    public function transition(Order $order, string $newStatus, User $performedBy): Order
    {
        return DB::transaction(function () use ($order, $newStatus, $performedBy) {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);

            $allowed = match ($locked->status) {
                // New orders go straight to the kitchen — no confirmation step.
                Order::STATUS_PENDING   => [Order::STATUS_PREPARING],
                // Legacy: any pre-existing "confirmed" orders can still be moved forward.
                Order::STATUS_CONFIRMED => [Order::STATUS_PREPARING],
                Order::STATUS_PREPARING => [Order::STATUS_READY],
                Order::STATUS_READY     => [Order::STATUS_DELIVERED, Order::STATUS_PICKED_UP],
                default                 => [],
            };

            if (! in_array($newStatus, $allowed, true)) {
                throw new RuntimeException(
                    "Cannot move order from '{$locked->status}' to '{$newStatus}'."
                );
            }

            if ($newStatus === Order::STATUS_DELIVERED && ! $locked->isDelivery()) {
                throw new RuntimeException('Pickup orders should be marked as Picked Up, not Delivered.');
            }

            if ($newStatus === Order::STATUS_PICKED_UP && ! $locked->isPickup()) {
                throw new RuntimeException('Delivery orders should be marked as Delivered, not Picked Up.');
            }

            $locked->status = $newStatus;
            $locked->save();

            return $locked->fresh(['items', 'user']);
        });
    }

    /**
     * Cancel an order and refund the full amount to the customer's wallet.
     * Only allowed from pending, confirmed, or preparing.
     */
    public function cancel(Order $order, User $performedBy, string $reason): Order
    {
        if (trim($reason) === '') {
            throw new RuntimeException('A cancellation reason is required.');
        }

        return DB::transaction(function () use ($order, $performedBy, $reason) {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);

            if (! in_array($locked->status, [
                Order::STATUS_PENDING,
                Order::STATUS_CONFIRMED,
                Order::STATUS_PREPARING,
            ], true)) {
                throw new RuntimeException(
                    'Orders in "'.ucfirst(str_replace('_', ' ', $locked->status))
                    .'" state cannot be cancelled.'
                );
            }

            // Refund the wallet
            if ((float) $locked->total > 0) {
                $this->wallet->refund(
                    user:        $locked->user,
                    amount:      (float) $locked->total,
                    description: 'Refund for cancelled order '.$locked->order_number,
                    reference:   $locked,
                    performedBy: $performedBy,
                );
            }

            $locked->status = Order::STATUS_CANCELLED;
            $locked->cancelled_at = now();
            $locked->cancelled_reason = $reason;
            $locked->save();

            return $locked->fresh(['items', 'user']);
        });
    }
	
	    /*
    |--------------------------------------------------------------------------
    | Customer self-service edit (within the 15-minute window)
    |--------------------------------------------------------------------------
    */

    /**
     * Add a menu item to an editable order. If the item is already in the
     * order, increments its quantity instead of creating a new line.
     */
    public function addItem(Order $order, int $menuItemId, User $performedBy): Order
    {
        return DB::transaction(function () use ($order, $menuItemId, $performedBy) {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);

            if (! $locked->isEditable()) {
                throw new RuntimeException('This order can no longer be edited.');
            }

            $menuItem = MenuItem::query()
                ->where('id', $menuItemId)
                ->where('is_active', true)
                ->first();

            if (! $menuItem) {
                throw new RuntimeException('That item is no longer available.');
            }

            if (! DailyMenu::isPublished(today(), $menuItemId)) {
                throw new RuntimeException('"'.$menuItem->name.'" is not available today.');
            }

            if (DailyMenu::isSoldOut(today(), $menuItemId)) {
                throw new RuntimeException('"'.$menuItem->name.'" is sold out for today.');
            }

            $customer = User::query()->lockForUpdate()->findOrFail($locked->user_id);
            $itemPrice = (float) $menuItem->price;

            if ((float) $customer->wallet_balance < $itemPrice) {
                throw new RuntimeException(sprintf(
                    'Insufficient wallet balance. You have Nu. %s but this item costs Nu. %s.',
                    number_format((float) $customer->wallet_balance, 2),
                    number_format($itemPrice, 2),
                ));
            }

            $existing = OrderItem::query()
                ->where('order_id', $locked->id)
                ->where('menu_item_id', $menuItemId)
                ->first();

            if ($existing) {
                $existing->quantity = (int) $existing->quantity + 1;
                $existing->save();
            } else {
                OrderItem::create([
                    'order_id'     => $locked->id,
                    'menu_item_id' => $menuItem->id,
                    'item_name'    => $menuItem->name,
                    'item_price'   => $menuItem->price,
                    'is_veg'       => $menuItem->is_veg,
                    'item_type'    => $menuItem->type,
                    'quantity'     => 1,
                ]);
            }

            $this->wallet->debit(
                user: $customer,
                amount: $itemPrice,
                description: 'Order '.$locked->order_number.' — added '.$menuItem->name,
                reference: $locked,
                performedBy: $performedBy,
            );

            $locked->total = $locked->items()->get()->sum(
                fn ($i) => (float) $i->item_price * (int) $i->quantity
            );
            $locked->save();

            return $locked->fresh(['items']);
        });
    }

    /**
     * Remove a line item from an editable order. Refunds the line total.
     * Blocked if it would leave the order empty — use cancel() instead.
     */
    public function removeItem(Order $order, int $orderItemId, User $performedBy): Order
    {
        return DB::transaction(function () use ($order, $orderItemId, $performedBy) {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);

            if (! $locked->isEditable()) {
                throw new RuntimeException('This order can no longer be edited.');
            }

            $orderItem = OrderItem::query()
                ->where('order_id', $locked->id)
                ->where('id', $orderItemId)
                ->first();

            if (! $orderItem) {
                throw new RuntimeException('Item not found in this order.');
            }

            $remaining = OrderItem::where('order_id', $locked->id)->count();

            if ($remaining <= 1) {
                throw new RuntimeException('Your order must contain at least one item. Cancel the order instead.');
            }

            $refundAmount = (float) $orderItem->item_price * (int) $orderItem->quantity;
            $removedName = $orderItem->item_name;

            $customer = User::query()->lockForUpdate()->findOrFail($locked->user_id);

            $this->wallet->refund(
                user: $customer,
                amount: $refundAmount,
                description: 'Order '.$locked->order_number.' — removed '.$removedName,
                reference: $locked,
                performedBy: $performedBy,
            );

            $orderItem->delete();

            $locked->total = $locked->items()->get()->sum(
                fn ($i) => (float) $i->item_price * (int) $i->quantity
            );
            $locked->save();

            return $locked->fresh(['items']);
        });
    }

    /**
     * Change the delivery method on an editable order.
     * Switching TO delivery requires the cutoff not to have passed.
     */
    public function changeDeliveryMethod(Order $order, string $method, User $performedBy): Order
    {
        if (! in_array($method, [Order::METHOD_DELIVERY, Order::METHOD_PICKUP], true)) {
            throw new RuntimeException('Invalid delivery method.');
        }

        return DB::transaction(function () use ($order, $method) {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);

            if (! $locked->isEditable()) {
                throw new RuntimeException('This order can no longer be edited.');
            }

            if ($method === Order::METHOD_DELIVERY) {
                $serviceDay = ServiceDay::forDate($locked->service_date);

                if ($serviceDay->isPastCutoff()) {
                    throw new RuntimeException(
                        'Delivery cut-off was at '.$serviceDay->effectiveCutoffTime().'. Please choose pickup.'
                    );
                }
            }

            $locked->delivery_method = $method;
            $locked->delivery_slot = $method === Order::METHOD_DELIVERY
                ? ($locked->delivery_slot ?: '11:00 AM – 2:00 PM')
                : null;
            $locked->save();

            return $locked->fresh(['items']);
        });
    }
}