<?php

namespace App\Modules\Admin\Http\Livewire;

use App\Modules\Inventory\Models\InventoryItem;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class InventoryItemForm extends Component
{
    public ?InventoryItem $item = null;

    public string $name = '';
    public string $sku = '';
    public string $category = 'other';
    public string $unit = 'kg';
    public string $current_stock = '0';
    public string $reorder_level = '0';
    public string $unit_cost = '0';
    public bool $is_active = true;
    public string $notes = '';

    public function mount(?InventoryItem $item = null): void
    {
        if ($item && $item->exists) {
            if (! auth()->user()->can('inventory.adjust')) {
                abort(403);
            }

            $this->item = $item;
            $this->name = $item->name;
            $this->sku = (string) $item->sku;
            $this->category = $item->category;
            $this->unit = $item->unit;
            $this->current_stock = (string) $item->current_stock;
            $this->reorder_level = (string) $item->reorder_level;
            $this->unit_cost = (string) $item->unit_cost;
            $this->is_active = (bool) $item->is_active;
            $this->notes = (string) $item->notes;
        } else {
            if (! auth()->user()->can('inventory.adjust')) {
                abort(403);
            }
        }
    }

    public function layoutData(): array
    {
        return [
            'title'   => $this->item ? 'Edit Inventory Item' : 'New Inventory Item',
            'heading' => $this->item ? 'Edit Inventory Item' : 'Create Inventory Item',
        ];
    }

    protected function rules(): array
    {
        return [
            'name'          => 'required|string|max:255',
            'sku'           => 'nullable|string|max:40',
            'category'      => 'required|string|max:40',
            'unit'          => 'required|string|max:20',
            'current_stock' => 'required|numeric|min:0|max:999999',
            'reorder_level' => 'required|numeric|min:0|max:999999',
            'unit_cost'     => 'required|numeric|min:0|max:999999',
            'is_active'     => 'boolean',
            'notes'         => 'nullable|string|max:1000',
        ];
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'name'          => $this->name,
            'sku'           => $this->sku !== '' ? $this->sku : null,
            'category'      => $this->category,
            'unit'          => $this->unit,
            'reorder_level' => $this->reorder_level,
            'unit_cost'     => $this->unit_cost,
            'is_active'     => $this->is_active,
            'notes'         => $this->notes !== '' ? $this->notes : null,
        ];

        if ($this->item) {
            // Note: current_stock is intentionally NOT updated here.
            // Stock changes must go through StockService (with audit trail).
            $this->item->update($data);

            session()->flash('status', 'Inventory item updated. Use the detail page for stock adjustments.');
        } else {
            $data['current_stock'] = 0;
            $item = InventoryItem::create($data);

            // If they entered opening stock, push it in via the service
            $opening = (float) $this->current_stock;
            if ($opening > 0) {
                app(\App\Modules\Inventory\Services\StockService::class)->stockIn(
                    item: $item,
                    quantity: $opening,
                    reason: 'Opening stock',
                    unitCost: (float) $this->unit_cost,
                    performedBy: auth()->user(),
                );
            }

            session()->flash('status', 'Inventory item created.');
        }

        $this->redirect(route('admin.inventory.index'), navigate: true);
    }

    public function render(): View
    {
        $categories = [
            'vegetables' => 'Vegetables',
            'meat'       => 'Meat & Poultry',
            'dry_goods'  => 'Dry Goods',
            'dairy'      => 'Dairy',
            'beverages'  => 'Beverages',
            'packaging'  => 'Packaging',
            'cleaning'   => 'Cleaning Supplies',
            'other'      => 'Other',
        ];

        $units = ['kg', 'g', 'L', 'ml', 'pcs', 'pack', 'bottle', 'box'];

        return view('admin.inventory.form', compact('categories', 'units'));
    }
}