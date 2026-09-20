<?php

namespace App\Modules\Customer\Http\Livewire;

use App\Modules\Menu\Models\DailyMenu;
use App\Modules\Menu\Models\MenuItem;
use App\Modules\Menu\Models\ServiceDay;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.public')]
class MenuBrowse extends Component
{
    #[Url(as: 'view', except: 'today')]
    public string $view = 'today';   // 'today' | 'all'

    public ?string $notice = null;

    public function setView(string $view): void
    {
        if (in_array($view, ['today', 'all'], true)) {
            $this->view = $view;
        }
    }

    /**
     * Placeholder for 13.5c — the real ordering flow lands in the next build.
     */
    public function startOrder(int $menuItemId): void
    {
        $item = MenuItem::find($menuItemId);

        if (! $item) {
            $this->notice = 'That item is no longer available.';
            return;
        }

        $this->notice = "Ordering for \"{$item->name}\" opens in the next update. Please order at the counter for now.";
    }

    public function dismissNotice(): void
    {
        $this->notice = null;
    }

    public function render(): View
    {
        $today = today();
        $serviceDay = ServiceDay::forDate($today);

        if ($this->view === 'today') {
            $grouped = DailyMenu::publishedForDate($today);
            $mains = $grouped['main'];
            $fastFood = $grouped['fastfood'];
        } else {
            $mains = MenuItem::active()->mains()->ordered()->get();
            $fastFood = MenuItem::active()->fastFood()->ordered()->get();
        }

        return view('public.menu', [
            'mains' => $mains,
            'fastFood' => $fastFood,
            'serviceDay' => $serviceDay,
            'isToday' => $this->view === 'today',
        ]);
    }
}