<?php

namespace App\Policies;

use App\Models\User;
use App\Modules\Supplier\Models\GoodsReceipt;

class GoodsReceiptPolicy
{
    /**
     * Viewing the receipts list requires either purchase.view (for reviewing
     * purchasing activity) OR purchase.receive (for actually receiving
     * deliveries — kitchen staff need to find the draft receipt to confirm it).
     */
    public function viewAny(User $user): bool
    {
        return $user->can('purchase.view') || $user->can('purchase.receive');
    }

    /**
     * Same logic for viewing a single receipt.
     */
    public function view(User $user, GoodsReceipt $gr): bool
    {
        return $user->can('purchase.view') || $user->can('purchase.receive');
    }

    /**
     * Edit line items — only while the receipt is still a draft.
     */
    public function update(User $user, GoodsReceipt $gr): bool
    {
        if (! $gr->isDraft()) {
            return false;
        }

        return $user->can('purchase.edit');
    }

    /**
     * Confirm — only drafts can be confirmed.
     */
    public function confirm(User $user, GoodsReceipt $gr): bool
    {
        if (! $gr->isDraft()) {
            return false;
        }

        return $user->can('purchase.receive');
    }

    /**
     * Delete — only drafts can be deleted.
     */
    public function delete(User $user, GoodsReceipt $gr): bool
    {
        if (! $gr->isDraft()) {
            return false;
        }

        return $user->can('purchase.edit');
    }
}