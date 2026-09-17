<?php

namespace App\Modules\Payment\Services;

use App\Models\User;
use App\Modules\Payment\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WalletService
{
    /**
     * Credit a customer's wallet. Creates a ledger entry and updates balance atomically.
     *
     * @throws RuntimeException
     */
    public function credit(
        User $user,
        float $amount,
        string $description,
        ?Model $reference = null,
        ?User $performedBy = null,
    ): WalletTransaction {
        if ($amount <= 0) {
            throw new RuntimeException('Credit amount must be greater than zero.');
        }

        return DB::transaction(function () use ($user, $amount, $description, $reference, $performedBy) {
            $locked = User::query()->lockForUpdate()->findOrFail($user->id);

            $before = (float) $locked->wallet_balance;
            $after  = $before + $amount;

            $txn = WalletTransaction::create([
                'user_id'        => $locked->id,
                'type'           => WalletTransaction::TYPE_CREDIT,
                'amount'         => $amount,
                'balance_before' => $before,
                'balance_after'  => $after,
                'description'    => $description,
                'reference_type' => $reference ? $reference::class : null,
                'reference_id'   => $reference?->getKey(),
                'created_by'     => $performedBy?->id,
            ]);

            $locked->wallet_balance = $after;
            $locked->save();

            return $txn;
        });
    }

    /**
     * Debit a customer's wallet. Fails if balance is insufficient.
     *
     * @throws RuntimeException
     */
    public function debit(
        User $user,
        float $amount,
        string $description,
        ?Model $reference = null,
        ?User $performedBy = null,
    ): WalletTransaction {
        if ($amount <= 0) {
            throw new RuntimeException('Debit amount must be greater than zero.');
        }

        return DB::transaction(function () use ($user, $amount, $description, $reference, $performedBy) {
            $locked = User::query()->lockForUpdate()->findOrFail($user->id);

            $before = (float) $locked->wallet_balance;

            if ($before < $amount) {
                throw new RuntimeException(sprintf(
                    'Insufficient wallet balance. Available: %s, Required: %s',
                    number_format($before, 2),
                    number_format($amount, 2)
                ));
            }

            $after = $before - $amount;

            $txn = WalletTransaction::create([
                'user_id'        => $locked->id,
                'type'           => WalletTransaction::TYPE_DEBIT,
                'amount'         => $amount,
                'balance_before' => $before,
                'balance_after'  => $after,
                'description'    => $description,
                'reference_type' => $reference ? $reference::class : null,
                'reference_id'   => $reference?->getKey(),
                'created_by'     => $performedBy?->id,
            ]);

            $locked->wallet_balance = $after;
            $locked->save();

            return $txn;
        });
    }

    /**
     * Refund a customer's wallet (e.g. order cancelled after debit).
     */
    public function refund(
        User $user,
        float $amount,
        string $description,
        ?Model $reference = null,
        ?User $performedBy = null,
    ): WalletTransaction {
        return DB::transaction(function () use ($user, $amount, $description, $reference, $performedBy) {
            $locked = User::query()->lockForUpdate()->findOrFail($user->id);

            $before = (float) $locked->wallet_balance;
            $after  = $before + $amount;

            $txn = WalletTransaction::create([
                'user_id'        => $locked->id,
                'type'           => WalletTransaction::TYPE_REFUND,
                'amount'         => $amount,
                'balance_before' => $before,
                'balance_after'  => $after,
                'description'    => $description,
                'reference_type' => $reference ? $reference::class : null,
                'reference_id'   => $reference?->getKey(),
                'created_by'     => $performedBy?->id,
            ]);

            $locked->wallet_balance = $after;
            $locked->save();

            return $txn;
        });
    }
}