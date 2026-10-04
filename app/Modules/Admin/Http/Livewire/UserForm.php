<?php

namespace App\Modules\Admin\Http\Livewire;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Spatie\Permission\Models\Role;

#[Layout('components.layouts.admin')]
class UserForm extends Component
{
    /** Null = create mode; populated = edit mode */
    public ?User $user = null;

    public string $name = '';
    public string $mobile = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $status = 'active';
    public array $selectedRoles = [];

    public function mount(?User $user = null): void
    {
        if ($user && $user->exists) {
            Gate::authorize('update', $user);

            // Prevent editing Customer accounts through this form
            if ($user->hasRole('Customer') && $user->roles->count() === 1) {
                session()->flash('error', 'Customer accounts are managed from their detail page.');
                $this->redirect(route('admin.users.show', $user), navigate: true);
                return;
            }

            $this->user = $user;
            $this->name = $user->name;
            $this->mobile = (string) $user->mobile;
            $this->email = (string) $user->email;
            $this->status = $user->status;
            $this->selectedRoles = $user->roles->pluck('name')->all();
        } else {
            Gate::authorize('create', User::class);
        }
    }

    public function layoutData(): array
    {
        return [
            'title'   => $this->user ? 'Edit User' : 'New Staff',
            'heading' => $this->user ? 'Edit Staff' : 'Create Staff Account',
        ];
    }

    protected function rules(): array
    {
        $userId = $this->user?->id;

        return [
            'name'             => 'required|string|max:255',
            'mobile'           => ['required', 'string', 'regex:/^\d{8}$/', Rule::unique('users', 'mobile')->ignore($userId)],
            'email'            => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'status'           => 'required|in:active,suspended',
            'selectedRoles'    => 'required|array|min:1',
            'selectedRoles.*'  => 'exists:roles,name',
            'password'         => $this->user
                                    ? 'nullable|string|min:8|confirmed'
                                    : 'required|string|min:8|confirmed',
        ];
    }

    protected function messages(): array
    {
        return [
            'mobile.regex'           => 'Mobile must be 8 digits (e.g. 17111101).',
            'selectedRoles.required' => 'Assign at least one role.',
            'selectedRoles.min'      => 'Assign at least one role.',
            'password.confirmed'     => 'The password confirmation does not match.',
        ];
    }

    public function save(): void
    {
        $this->validate();

        if ($this->user) {
            // Update
            $this->user->name = $this->name;
            $this->user->mobile = $this->mobile;
            $this->user->email = $this->email !== '' ? $this->email : null;
            $this->user->status = $this->status;

            if ($this->password !== '') {
                $this->user->password = Hash::make($this->password);
            }

            $this->user->save();
            $this->user->syncRoles($this->selectedRoles);

            session()->flash('status', 'Staff account updated.');
            $this->redirect(route('admin.users.show', $this->user), navigate: true);
        } else {
            // Create
            $user = User::create([
                'name'     => $this->name,
                'mobile'   => $this->mobile,
                'email'    => $this->email !== '' ? $this->email : null,
                'password' => Hash::make($this->password),
                'status'   => $this->status,
            ]);
            $user->assignRole($this->selectedRoles);

            session()->flash('status', 'Staff account created.');
            $this->redirect(route('admin.users.show', $user), navigate: true);
        }
    }

    public function render(): View
    {
        // Only staff roles assignable — Customer is created via public registration
        $roles = Role::whereIn('name', ['Admin', 'Manager', 'Kitchen Staff', 'Delivery Staff'])
            ->orderByRaw("FIELD(name, 'Admin', 'Manager', 'Kitchen Staff', 'Delivery Staff')")
            ->pluck('name');

        return view('admin.users.form', compact('roles'));
    }
}