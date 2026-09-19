<?php

namespace App\Modules\Admin\Http\Livewire;

use App\Modules\Supplier\Models\Supplier;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class SupplierForm extends Component
{
    public ?Supplier $supplier = null;

    public string $name = '';
    public string $contact_person = '';
    public string $mobile = '';
    public string $email = '';
    public string $address = '';
    public string $supplies = '';
    public string $bank_account = '';
    public string $notes = '';
    public bool $is_active = true;

    public function mount(?Supplier $supplier = null): void
    {
        if ($supplier && $supplier->exists) {
            if (! auth()->user()->can('supplier.edit')) {
                abort(403);
            }

            $this->supplier = $supplier;
            $this->name = $supplier->name;
            $this->contact_person = (string) $supplier->contact_person;
            $this->mobile = (string) $supplier->mobile;
            $this->email = (string) $supplier->email;
            $this->address = (string) $supplier->address;
            $this->supplies = (string) $supplier->supplies;
            $this->bank_account = (string) $supplier->bank_account;
            $this->notes = (string) $supplier->notes;
            $this->is_active = (bool) $supplier->is_active;
        } else {
            if (! auth()->user()->can('supplier.create')) {
                abort(403);
            }
        }
    }

    public function layoutData(): array
    {
        return [
            'title'   => $this->supplier ? 'Edit Supplier' : 'New Supplier',
            'heading' => $this->supplier ? 'Edit Supplier' : 'Create Supplier',
        ];
    }

    protected function rules(): array
    {
        return [
            'name'           => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'mobile'         => 'nullable|string|max:20',
            'email'          => 'nullable|email|max:255',
            'address'        => 'nullable|string|max:500',
            'supplies'       => 'nullable|string|max:255',
            'bank_account'   => 'nullable|string|max:255',
            'notes'          => 'nullable|string|max:1000',
            'is_active'      => 'boolean',
        ];
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'name'           => $this->name,
            'contact_person' => $this->contact_person !== '' ? $this->contact_person : null,
            'mobile'         => $this->mobile !== '' ? $this->mobile : null,
            'email'          => $this->email !== '' ? $this->email : null,
            'address'        => $this->address !== '' ? $this->address : null,
            'supplies'       => $this->supplies !== '' ? $this->supplies : null,
            'bank_account'   => $this->bank_account !== '' ? $this->bank_account : null,
            'notes'          => $this->notes !== '' ? $this->notes : null,
            'is_active'      => $this->is_active,
        ];

        if ($this->supplier) {
            $this->supplier->update($data);
            session()->flash('status', 'Supplier updated.');
        } else {
            Supplier::create($data);
            session()->flash('status', 'Supplier created.');
        }

        $this->redirect(route('admin.suppliers.index'), navigate: true);
    }

    public function render(): View
    {
        return view('admin.suppliers.form');
    }
}