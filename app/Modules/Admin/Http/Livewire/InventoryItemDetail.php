<?php

namespace App\Modules\Admin\Http\Livewire;

use App\Modules\Inventory\Models\InventoryItem;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Inventory\Services\StockService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class InventoryItemDetail extends Component
{
    public InventoryItem $item;

    public ?string $statusMessage = null;
    public ?string $statusType = null;

    // Modal form fields
    public string $stockInQuantity = '';
    public string $stockInCost = '';
    public string $stockInReason = '';

    public string $stockOutQuantity = '';
    public string $stockOutReason = '';

    public string $wasteQuantity = '';
    public string $wasteReason = '';

    public string $countQuantity = '';
    public string $countReason = '';

    public function mount(InventoryItem $item): void
    {
        if (! auth()->user()->can('inventory.view')) {
            abort(403);
        }

        $this->item = $item;
        $this->stockInCost = (string) $item->unit_cost;
    }

    public function layoutData(): array
    {
        return [
            'title'   => $this->item->name,
            'heading' => 'Inventory Item',
        ];
    }

    protected function flash(string $type, string $message): void
    {
        $this->statusType = $type;
        $this->statusMessage = $message;
    }

    protected function refreshItem(): void
    {
        $this->item = $this->item->fresh();
    }

    /*
    |--------------------------------------------------------------------------
    | Stock movements
    |--------------------------------------------------------------------------
    */

    public function stockIn(): void
    {
        if (! auth()->user()->can('inventory.adjust')) {
            abort(403);
        }

        $this->validate([
            'stockInQuantity' => 'required|numeric|min:0.001|max:999999',
            'stockInCost'     => 'required|numeric|min:0|max:999999',
            'stockInReason'   => 'required|string|min:3|max:255',
        ], [
            'stockInQuantity.required' => 'Enter the quantity received.',
            'stockInQuantity.min'      => 'Quantity must be greater than zero.',
            'stockInReason.required'   => 'A reason is required for the audit trail.',
        ]);

        try {
            app(StockService::class)->stockIn(
                item: $this->item,
                quantity: (float) $this->stockInQuantity,
                reason: $this->stockInReason,
                unitCost: (float) $this->stockInCost,
                performedBy: auth()->user(),
            );

            $this->refreshItem();
            $this->reset(['stockInQuantity', 'stockInReason']);
            $this->stockInCost = (string) $this->item->unit_cost;
            $this->dispatch('close-modal-stock-in');

            $this->flash('success', 'Stock added. New balance: '.$this->item->displayStock());
        } catch (\Throwable $e) {
            $this->flash('error', $e->getMessage());
        }
    }

    public function stockOut(): void
    {
        if (! auth()->user()->can('inventory.adjust')) {
            abort(403);
        }

        $this->validate([
            'stockOutQuantity' => 'required|numeric|min:0.001|max:999999',
            'stockOutReason'   => 'required|string|min:3|max:255',
        ], [
            'stockOutQuantity.required' => 'Enter the quantity used.',
            'stockOutReason.required'   => 'A reason is required for the audit trail.',
        ]);

        try {
            app(StockService::class)->stockOut(
                item: $this->item,
                quantity: (float) $this->stockOutQuantity,
                reason: $this->stockOutReason,
                performedBy: auth()->user(),
            );

            $this->refreshItem();
            $this->reset(['stockOutQuantity', 'stockOutReason']);
            $this->dispatch('close-modal-stock-out');

            $this->flash('success', 'Stock deducted. New balance: '.$this->item->displayStock());
        } catch (\Throwable $e) {
            $this->flash('error', $e->getMessage());
        }
    }

    public function recordWaste(): void
    {
        if (! auth()->user()->can('inventory.adjust')) {
            abort(403);
        }

        $this->validate([
            'wasteQuantity' => 'required|numeric|min:0.001|max:999999',
            'wasteReason'   => 'required|string|min:3|max:255',
        ], [
            'wasteQuantity.required' => 'Enter the quantity wasted.',
            'wasteReason.required'   => 'A reason is required for the audit trail.',
        ]);

        try {
            app(StockService::class)->waste(
                item: $this->item,
                quantity: (float) $this->wasteQuantity,
                reason: $this->wasteReason,
                performedBy: auth()->user(),
            );

            $this->refreshItem();
            $this->reset(['wasteQuantity', 'wasteReason']);
            $this->dispatch('close-modal-stock-waste');

            $this->flash('success', 'Waste recorded. New balance: '.$this->item->displayStock());
        } catch (\Throwable $e) {
            $this->flash('error', $e->getMessage());
        }
    }

    public function adjustStock(): void
    {
        if (! auth()->user()->can('inventory.adjust')) {
            abort(403);
        }

        $this->validate([
            'countQuantity' => 'required|numeric|min:0|max:999999',
            'countReason'   => 'required|string|min:3|max:255',
        ], [
            'countQuantity.required' => 'Enter the physical count.',
            'countReason.required'   => 'A reason is required for the audit trail.',
        ]);

        try {
            app(StockService::class)->adjust(
                item: $this->item,
                newQuantity: (float) $this->countQuantity,
                reason: $this->countReason,
                performedBy: auth()->user(),
            );

            $this->refreshItem();
            $this->reset(['countQuantity', 'countReason']);
            $this->dispatch('close-modal-stock-count');

            $this->flash('success', 'Stock adjusted. New balance: '.$this->item->displayStock());
        } catch (\Throwable $e) {
            $this->flash('error', $e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Render
    |--------------------------------------------------------------------------
    */

    public function render(): View
    {
        $movements = StockMovement::query()
            ->forItem($this->item->id)
            ->with('createdBy')
            ->recent()
            ->limit(50)
            ->get();

        $stats = [
            'stockValue' => $this->item->stockValue(),
            'reorder'    => (float) $this->item->reorder_level,
            'isLow'      => $this->item->isLowStock(),
            'isOut'      => $this->item->isOutOfStock(),
        ];

        return view('admin.inventory.show', compact('movements', 'stats'));
    }
}