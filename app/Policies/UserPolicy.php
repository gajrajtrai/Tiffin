<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('user.view');
    }

    public function view(User $user, User $model): bool
    {
        // Users can view their own profile regardless of role
        if ($user->id === $model->id) {
            return true;
        }

        return $user->can('user.view');
    }

    public function create(User $user): bool
    {
        return $user->can('user.create');
    }

    public function update(User $user, User $model): bool
    {
        return $user->can('user.edit');
    }

    /**
     * Suspend or reactivate an account.
     * Admins cannot suspend themselves — that would lock them out.
     */
    public function toggleStatus(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return false;
        }

        return $user->can('user.edit');
    }

    public function delete(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return false;
        }

        return $user->can('user.delete');
    }

    /**
     * Credit a customer's wallet manually.
     */
    public function creditWallet(User $user, User $model): bool
    {
        return $user->can('wallet.credit');
    }

    /**
     * Debit a customer's wallet manually.
     */
    public function debitWallet(User $user, User $model): bool
    {
        return $user->can('wallet.debit');
    }
}