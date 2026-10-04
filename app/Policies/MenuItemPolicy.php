<?php

namespace App\Policies;

use App\Models\User;
use App\Modules\Menu\Models\MenuItem;

class MenuItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('menu.view');
    }

    public function view(User $user, MenuItem $item): bool
    {
        return $user->can('menu.view');
    }

    public function create(User $user): bool
    {
        return $user->can('menu.create');
    }

    public function update(User $user, MenuItem $item): bool
    {
        return $user->can('menu.edit');
    }

    public function delete(User $user, MenuItem $item): bool
    {
        // Permission check — the "has order history" rule is on the model
        // (MenuItem::canBeDeleted) so the UI can show a friendly message
        // instead of a hard 403.
        return $user->can('menu.delete');
    }

    /**
     * Publishing to the daily / weekly menu is a permission, not a per-item rule.
     */
    public function publish(User $user): bool
    {
        return $user->can('menu.publish');
    }
}