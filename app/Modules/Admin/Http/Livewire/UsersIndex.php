<?php

namespace App\Modules\Admin\Http\Livewire;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

#[Layout('components.layouts.admin')]
class UsersIndex extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'role', except: '')]
    public string $roleFilter = '';

    #[Url(as: 'status', except: 'all')]
    public string $statusFilter = 'all';

    public function updating(string $name): void
    {
        if (in_array($name, ['search', 'roleFilter', 'statusFilter'], true)) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->roleFilter = '';
        $this->statusFilter = 'all';
        $this->resetPage();
    }

    public function layoutData(): array
    {
        return [
            'title'   => 'Users',
            'heading' => 'User Management',
        ];
    }

    public function render(): View
    {
        $query = User::query()->with('roles');

        if ($this->search !== '') {
            $term = '%'.$this->search.'%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                  ->orWhere('email', 'like', $term)
                  ->orWhere('mobile', 'like', $term);
            });
        }

        if ($this->roleFilter !== '') {
            $query->role($this->roleFilter);
        }

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        $users = $query->orderByDesc('created_at')->paginate(15);

        $roles = Role::orderBy('name')->pluck('name');

        $counts = [
            'total'      => User::count(),
            'customers'  => User::role('Customer')->count(),
            'staff'      => User::whereHas('roles', function ($q) {
                                $q->whereIn('name', ['Admin', 'Manager', 'Kitchen Staff', 'Delivery Staff']);
                            })->count(),
            'suspended'  => User::where('status', 'suspended')->count(),
        ];

        return view('admin.users.index', compact('users', 'roles', 'counts'));
    }
}