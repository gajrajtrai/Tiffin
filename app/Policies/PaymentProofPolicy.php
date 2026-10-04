<?php

namespace App\Policies;

use App\Models\User;
use App\Modules\Payment\Models\PaymentProof;

class PaymentProofPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('payment.view');
    }

    public function view(User $user, PaymentProof $proof): bool
    {
        if ($user->can('payment.view')) {
            return true;
        }

        // Customers can view their own submissions
        return $user->id === $proof->user_id;
    }

    /**
     * Approve — only pending proofs can be verified.
     */
    public function verify(User $user, PaymentProof $proof): bool
    {
        if (! $proof->isPending()) {
            return false;
        }

        return $user->can('payment.verify');
    }

    /**
     * Reject — only pending proofs can be rejected.
     */
    public function reject(User $user, PaymentProof $proof): bool
    {
        if (! $proof->isPending()) {
            return false;
        }

        return $user->can('payment.reject');
    }
}