<?php

namespace App\Modules\Admin\Http\Livewire;

use App\Modules\Menu\Models\MenuItem;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.admin')]
class MenuItemForm extends Component
{
    use WithFileUploads;

    public ?MenuItem $item = null;

    public string $name = '';
    public string $description = '';
    public string $type = 'fastfood';
    public bool $is_veg = true;
    public string $price = '';
    public int $sort_order = 0;
    public bool $is_active = true;

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public $image = null;
    public bool $remove_image = false;

    public function mount(?MenuItem $item = null): void
    {
        if ($item && $item->exists) {
            if (! auth()->user()->can('menu.edit')) {
                abort(403);
            }

            $this->item = $item;
            $this->name = $item->name;
            $this->description = (string) $item->description;
            $this->type = $item->type;
            $this->is_veg = (bool) $item->is_veg;
            $this->price = (string) $item->price;
            $this->sort_order = (int) $item->sort_order;
            $this->is_active = (bool) $item->is_active;
        } else {
            if (! auth()->user()->can('menu.create')) {
                abort(403);
            }
        }
    }

    public function layoutData(): array
    {
        return [
            'title'   => $this->item ? 'Edit Menu Item' : 'New Menu Item',
            'heading' => $this->item ? 'Edit Menu Item' : 'Create Menu Item',
        ];
    }

    protected function rules(): array
    {
        return [
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type'        => 'required|in:main,fastfood',
            'is_veg'      => 'boolean',
            'price'       => 'required|numeric|min:0|max:99999',
            'sort_order'  => 'required|integer|min:0|max:9999',
            'is_active'   => 'boolean',
            'image'       => 'nullable|image|max:5120', // 5 MB
        ];
    }

    protected function messages(): array
    {
        return [
            'image.image' => 'File must be a valid image (JPEG, PNG, WebP).',
            'image.max'   => 'Image must be smaller than 5 MB.',
        ];
    }

    public function save(): void
    {
        $this->validate();

        if ($this->item) {
            $this->item->update([
                'name'        => $this->name,
                'description' => $this->description !== '' ? $this->description : null,
                'type'        => $this->type,
                'is_veg'      => $this->is_veg,
                'price'       => $this->price,
                'sort_order'  => $this->sort_order,
                'is_active'   => $this->is_active,
            ]);
            $item = $this->item;
        } else {
            $item = MenuItem::create([
                'name'        => $this->name,
                'description' => $this->description !== '' ? $this->description : null,
                'type'        => $this->type,
                'is_veg'      => $this->is_veg,
                'price'       => $this->price,
                'sort_order'  => $this->sort_order,
                'is_active'   => $this->is_active,
            ]);
        }

        // Handle image changes
        if ($this->remove_image) {
            $item->clearMediaCollection(MenuItem::MEDIA_IMAGE);
        }

        if ($this->image) {
            $item->clearMediaCollection(MenuItem::MEDIA_IMAGE);
            $item->addMedia($this->image)
                 ->usingFileName($this->image->getClientOriginalName())
                 ->toMediaCollection(MenuItem::MEDIA_IMAGE);
        }

        session()->flash('status', $this->item
            ? 'Menu item updated.'
            : 'Menu item created.');

        $this->redirect(route('admin.menu.index'), navigate: true);
    }

    public function render(): View
    {
        return view('admin.menu.form');
    }
}