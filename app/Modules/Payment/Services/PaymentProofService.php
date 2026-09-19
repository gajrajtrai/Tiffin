<?php

namespace App\Modules\Payment\Services;

use App\Models\User;
use App\Modules\Payment\Models\PaymentProof;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PaymentProofService
{
    public function __construct(
        protected WalletService $wallet,
    ) {}

    /**
     * Approve a pending payment proof and credit the customer's wallet.
     * Optionally override the amount if the screenshot shows a different figure.
     */
    public function approve(
        PaymentProof $proof,
        User $admin,
        ?float $overrideAmount = null,
    ): PaymentProof {
        if (! $proof->isPending()) {
            throw new RuntimeException('Only pending proofs can be approved.');
        }

        return DB::transaction(function () use ($proof, $admin, $overrideAmount) {
            $locked = PaymentProof::query()->lockForUpdate()->findOrFail($proof->id);

            if (! $locked->isPending()) {
                throw new RuntimeException('Proof has already been reviewed.');
            }

            $amount = $overrideAmount ?? (float) $locked->claimed_amount;

            if ($amount <= 0) {
                throw new RuntimeException('Approval amount must be greater than zero.');
            }

            $reference = $locked->bank_reference ?? 'N/A';

            $txn = $this->wallet->credit(
                user:        $locked->user,
                amount:      $amount,
                description: 'Wallet top-up — payment proof #'.$locked->id.' (Ref: '.$reference.')',
                reference:   $locked,
                performedBy: $admin,
            );

            $locked->status = PaymentProof::STATUS_APPROVED;
            $locked->reviewed_by = $admin->id;
            $locked->reviewed_at = now();
            $locked->wallet_transaction_id = $txn->id;
            $locked->save();

            return $locked->fresh(['user', 'walletTransaction']);
        });
    }

    /**
     * Reject a pending proof. No wallet change.
     */
    public function reject(
        PaymentProof $proof,
        User $admin,
        string $reason,
    ): PaymentProof {
        if (trim($reason) === '') {
            throw new RuntimeException('A rejection reason is required.');
        }

        if (! $proof->isPending()) {
            throw new RuntimeException('Only pending proofs can be rejected.');
        }

        $proof->status = PaymentProof::STATUS_REJECTED;
        $proof->reviewed_by = $admin->id;
        $proof->reviewed_at = now();
        $proof->rejection_reason = $reason;
        $proof->save();

        return $proof->fresh(['user']);
    }
}