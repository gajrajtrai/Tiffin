<?php

namespace App\Policies;

use App\Models\User;
use App\Modules\Supplier\Models\PurchaseOrder;

class PurchaseOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('purchase.view');
    }

    public function view(User $user, PurchaseOrder $po): bool
    {
        return $user->can('purchase.view');
    }

    public function create(User $user): bool
    {
        return $user->can('purchase.create');
    }

    /**
     * Only draft POs can be edited.
     */
    public function update(User $user, PurchaseOrder $po): bool
    {
        if (! $po->isEditable()) {
            return false;
        }

        return $user->can('purchase.edit');
    }

    /**
     * Mark as sent — only valid from draft.
     */
    public function send(User $user, PurchaseOrder $po): bool
    {
        if (! $po->isDraft()) {
            return false;
        }

        return $user->can('purchase.edit');
    }

    /**
     * Cancel — only valid from draft or sent (not once partially received).
     */
    public function cancel(User $user, PurchaseOrder $po): bool
    {
        if (! $po->isCancellable()) {
            return false;
        }

        return $user->can('purchase.edit');
    }

    /**
     * Receive goods against a PO — valid from sent or partially_received.
     */
    public function receive(User $user, PurchaseOrder $po): bool
    {
        if (! in_array($po->status, [
            PurchaseOrder::STATUS_SENT,
            PurchaseOrder::STATUS_PARTIALLY_RECEIVED,
        ], true)) {
            return false;
        }

        return $user->can('purchase.receive');
    }
}