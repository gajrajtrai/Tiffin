<?php

namespace App\Modules\Order\Services;

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
                Order::STATUS_PENDING   => [Order::STATUS_CONFIRMED],
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
}