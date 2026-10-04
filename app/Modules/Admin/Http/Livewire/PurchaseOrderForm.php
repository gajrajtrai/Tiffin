<?php

namespace App\Modules\Admin\Http\Livewire;

use App\Modules\Inventory\Models\InventoryItem;
use App\Modules\Supplier\Models\PurchaseOrder;
use App\Modules\Supplier\Models\PurchaseOrderItem;
use App\Modules\Supplier\Models\Supplier;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class PurchaseOrderForm extends Component
{
    public ?PurchaseOrder $po = null;

    public string $supplier_id = '';
    public string $order_date = '';
    public string $expected_date = '';
    public string $notes = '';

    /**
     * Line items — array of:
     * ['inventory_item_id' => int|null, 'item_name' => string, 'unit' => string,
     *  'quantity_ordered' => numeric, 'unit_cost' => numeric]
     */
    public array $items = [];

    public function mount(?PurchaseOrder $po = null): void
    {
        if ($po && $po->exists) {
            Gate::authorize('update', $po);

            if (! $po->isEditable()) {
                session()->flash('error', 'Only draft POs can be edited.');
                $this->redirect(route('admin.purchases.show', $po), navigate: true);
                return;
            }

            $this->po = $po->load('items');
            $this->supplier_id = (string) $po->supplier_id;
            $this->order_date = $po->order_date?->toDateString() ?? today()->toDateString();
            $this->expected_date = $po->expected_date?->toDateString() ?? '';
            $this->notes = (string) $po->notes;

            $this->items = $po->items->map(fn ($i) => [
                'inventory_item_id' => $i->inventory_item_id,
                'item_name'         => $i->item_name,
                'unit'              => $i->unit,
                'quantity_ordered'  => (float) $i->quantity_ordered,
                'unit_cost'         => (float) $i->unit_cost,
            ])->all();
        } else {
            Gate::authorize('create', PurchaseOrder::class);

            $this->order_date = today()->toDateString();
            $this->addItem(); // start with one blank line
        }
    }

    public function layoutData(): array
    {
        return [
            'title'   => $this->po ? 'Edit Purchase Order' : 'New Purchase Order',
            'heading' => $this->po ? 'Edit Purchase Order' : 'New Purchase Order',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Line items
    |--------------------------------------------------------------------------
    */

    public function addItem(): void
    {
        $this->items[] = [
            'inventory_item_id' => '',
            'item_name'         => '',
            'unit'              => '',
            'quantity_ordered'  => 1,
            'unit_cost'         => 0,
        ];
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);

        if (empty($this->items)) {
            $this->addItem();
        }
    }

    /**
     * When the inventory item dropdown changes, prefill name, unit, and cost.
     */
    public function updatedItems($value, $key): void
    {
        // $key looks like "0.inventory_item_id"
        if (! str_ends_with($key, '.inventory_item_id')) {
            return;
        }

        $index = (int) explode('.', $key)[0];

        if (empty($this->items[$index]['inventory_item_id'])) {
            return;
        }

        $item = InventoryItem::find($this->items[$index]['inventory_item_id']);

        if ($item) {
            $this->items[$index]['item_name'] = $item->name;
            $this->items[$index]['unit'] = $item->unit;
            $this->items[$index]['unit_cost'] = (float) $item->unit_cost;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Save
    |--------------------------------------------------------------------------
    */

    protected function rules(): array
    {
        return [
            'supplier_id'           => 'required|exists:suppliers,id',
            'order_date'            => 'required|date',
            'expected_date'         => 'nullable|date|after_or_equal:order_date',
            'notes'                 => 'nullable|string|max:1000',
            'items'                 => 'required|array|min:1',
            'items.*.item_name'     => 'required|string|max:255',
            'items.*.unit'          => 'required|string|max:20',
            'items.*.quantity_ordered' => 'required|numeric|min:0.001',
            'items.*.unit_cost'     => 'required|numeric|min:0',
        ];
    }

    protected function messages(): array
    {
        return [
            'supplier_id.required'      => 'Choose a supplier.',
            'items.*.item_name.required' => 'Every line needs an item name.',
            'items.*.unit.required'      => 'Every line needs a unit.',
            'items.*.quantity_ordered.min' => 'Quantity must be greater than zero.',
        ];
    }

    public function save(bool $send = false): void
    {
        $this->validate();

        DB::transaction(function () use ($send) {
            $subtotal = collect($this->items)->sum(fn ($i) => (float) $i['quantity_ordered'] * (float) $i['unit_cost']);

            $data = [
                'supplier_id'   => $this->supplier_id,
                'order_date'    => $this->order_date,
                'expected_date' => $this->expected_date !== '' ? $this->expected_date : null,
                'notes'         => $this->notes !== '' ? $this->notes : null,
                'subtotal'      => $subtotal,
                'tax'           => 0,
                'total'         => $subtotal,
            ];

            if ($send) {
                $data['status'] = PurchaseOrder::STATUS_SENT;
                $data['sent_at'] = now();
            } elseif ($this->po) {
                // keep existing status
            } else {
                $data['status'] = PurchaseOrder::STATUS_DRAFT;
            }

            if ($this->po) {
                $this->po->update($data);
                $po = $this->po;
                // Clear existing lines, re-create
                $po->items()->delete();
            } else {
                $data['ordered_by'] = auth()->id();
                $po = PurchaseOrder::create($data);
            }

            foreach ($this->items as $line) {
                $qty = (float) $line['quantity_ordered'];
                $cost = (float) $line['unit_cost'];

                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'inventory_item_id' => $line['inventory_item_id'] ?: null,
                    'item_name'         => $line['item_name'],
                    'unit'              => $line['unit'],
                    'quantity_ordered'  => $qty,
                    'quantity_received' => 0,
                    'unit_cost'         => $cost,
                    'line_total'        => $qty * $cost,
                ]);
            }

            session()->flash('status', $send ? 'Purchase order created and marked as sent.' : 'Purchase order saved as draft.');
            $this->redirect(route('admin.purchases.show', $po), navigate: true);
        });
    }

    public function saveDraft(): void
    {
        $this->save(send: false);
    }

    public function saveAndSend(): void
    {
        $this->save(send: true);
    }

    /*
    |--------------------------------------------------------------------------
    | Render
    |--------------------------------------------------------------------------
    */

    public function render(): View
    {
        $suppliers = Supplier::active()->ordered()->get(['id', 'name']);
        $inventoryItems = InventoryItem::active()->ordered()->get(['id', 'name', 'unit', 'unit_cost']);

        $subtotal = collect($this->items)->sum(
            fn ($i) => (float) ($i['quantity_ordered'] ?? 0) * (float) ($i['unit_cost'] ?? 0)
        );

        return view('admin.purchases.form', compact('suppliers', 'inventoryItems', 'subtotal'));
    }
}