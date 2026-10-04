<?php

namespace App\Policies;

use App\Models\User;
use App\Modules\Inventory\Models\InventoryItem;

class InventoryItemPolicy
{
    /*
    |--------------------------------------------------------------------------
    | Browse
    |--------------------------------------------------------------------------
    */

    public function viewAny(User $user): bool
    {
        return $user->can('inventory.view');
    }

    public function view(User $user, InventoryItem $item): bool
    {
        return $user->can('inventory.view');
    }

    /*
    |--------------------------------------------------------------------------
    | Create / Update
    |--------------------------------------------------------------------------
    |
    | There is no separate "inventory.create" or "inventory.edit" permission
    | in the matrix — inventory.adjust covers any mutating action, because
    | adding an item, editing its details, and recording stock are all part
    | of managing the same asset.
    |
    */

    public function create(User $user): bool
    {
        return $user->can('inventory.adjust');
    }

    public function update(User $user, InventoryItem $item): bool
    {
        return $user->can('inventory.adjust');
    }

    /*
    |--------------------------------------------------------------------------
    | Adjust (stock in, out, waste, physical count)
    |--------------------------------------------------------------------------
    |
    | Same permission as create/update — kept as a separate policy method
    | so the call site reads naturally: "authorize the user to adjust
    | this item's stock".
    |
    */

    public function adjust(User $user, InventoryItem $item): bool
    {
        return $user->can('inventory.adjust');
    }

    /*
    |--------------------------------------------------------------------------
    | Delete (soft)
    |--------------------------------------------------------------------------
    |
    | Deleting an inventory item is rare — usually you'd just set it
    | inactive. Soft delete preserves the movement history.
    |
    */

    public function delete(User $user, InventoryItem $item): bool
    {
        return $user->can('inventory.adjust');
    }
}