<?php

namespace App\Policies;

use App\Models\User;
use App\Modules\Order\Models\Order;

class OrderPolicy
{
    /*
    |--------------------------------------------------------------------------
    | Browse (list / index)
    |--------------------------------------------------------------------------
    */

    public function viewAny(User $user): bool
    {
        return $user->can('order.view');
    }

    /*
    |--------------------------------------------------------------------------
    | View a single order
    |--------------------------------------------------------------------------
    |
    | Staff with order.view can view any order.
    | Customers can view their own orders.
    |
    */

    public function view(User $user, Order $order): bool
    {
        if ($user->can('order.view')) {
            return true;
        }

        return $user->id === $order->user_id;
    }

    /*
    |--------------------------------------------------------------------------
    | Update (status transitions, forward to kitchen, mark ready)
    |--------------------------------------------------------------------------
    |
    | Only active orders can be updated. A cancelled or completed order is
    | frozen — its state is part of the historical record.
    |
    */

    public function update(User $user, Order $order): bool
    {
        if (! $order->isActive()) {
            return false;
        }

        return $user->can('order.update-status');
    }

    /*
    |--------------------------------------------------------------------------
    | Forward (New Orders → Preparing)
    |--------------------------------------------------------------------------
    |
    | Only valid from pending or confirmed (legacy) states.
    |
    */

    public function forward(User $user, Order $order): bool
    {
        if (! in_array($order->status, [Order::STATUS_PENDING, Order::STATUS_CONFIRMED], true)) {
            return false;
        }

        return $user->can('order.update-status');
    }

    /*
    |--------------------------------------------------------------------------
    | Advance (Preparing → Ready → Delivered / Picked Up)
    |--------------------------------------------------------------------------
    |
    | Only valid from preparing or ready states.
    |
    */

    public function advance(User $user, Order $order): bool
    {
        if (! in_array($order->status, [Order::STATUS_PREPARING, Order::STATUS_READY], true)) {
            return false;
        }

        return $user->can('order.update-status');
    }

    /*
    |--------------------------------------------------------------------------
    | Cancel (with refund)
    |--------------------------------------------------------------------------
    |
    | Only cancellable orders (pending, confirmed, preparing) can be
    | cancelled. Ready and beyond means the kitchen already did the work.
    |
    */

    public function cancel(User $user, Order $order): bool
    {
        if (! $order->isCancellable()) {
            return false;
        }

        return $user->can('order.cancel');
    }
	
	    /*
    |--------------------------------------------------------------------------
    | Edit (customer self-service within the window)
    |--------------------------------------------------------------------------
    |
    | Only the owning customer can edit. Staff cannot edit a customer's
    | order mid-flight — they cancel or advance it instead.
    |
    */

    public function edit(User $user, Order $order): bool
    {
        if ($user->id !== $order->user_id) {
            return false;
        }

        return $order->isEditable();
    }
}