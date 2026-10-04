<?php

namespace App\Policies;

use App\Models\User;
use App\Modules\Expense\Models\Expense;

class ExpensePolicy
{
    /*
    |--------------------------------------------------------------------------
    | Browse
    |--------------------------------------------------------------------------
    */

    public function viewAny(User $user): bool
    {
        return $user->can('expense.view');
    }

    public function view(User $user, Expense $expense): bool
    {
        return $user->can('expense.view');
    }

    /*
    |--------------------------------------------------------------------------
    | Create
    |--------------------------------------------------------------------------
    */

    public function create(User $user): bool
    {
        return $user->can('expense.create');
    }

    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    |
    | Voided expenses are frozen — their amounts are excluded from all
    | reports, and editing them would break the historical record.
    |
    | GR-linked expenses are managed from their goods receipt — editing
    | them here would desync the two records.
    |
    */

    public function update(User $user, Expense $expense): bool
    {
        if ($expense->isVoided()) {
            return false;
        }

        if ($expense->isGrLinked()) {
            return false;
        }

        return $user->can('expense.edit');
    }

    /*
    |--------------------------------------------------------------------------
    | Delete (hard delete — only for drafts)
    |--------------------------------------------------------------------------
    |
    | Only drafts with no attached receipt can be hard-deleted. Anything
    | committed should be voided instead, to preserve the audit trail.
    |
    */

    public function delete(User $user, Expense $expense): bool
    {
        if (! $expense->canBeDeleted()) {
            return false;
        }

        return $user->can('expense.delete');
    }

    /*
    |--------------------------------------------------------------------------
    | Void
    |--------------------------------------------------------------------------
    |
    | Voiding excludes the expense from reports and totals but keeps the
    | record visible with a "Voided" badge.
    |
    */

    public function void(User $user, Expense $expense): bool
    {
        if (! $expense->canBeVoided()) {
            return false;
        }

        return $user->can('expense.delete');
    }
}