<?php

namespace App\Modules\Admin\Http\Livewire;

use App\Modules\Menu\Models\DailyMenu;
use App\Modules\Menu\Models\MenuItem;
use App\Modules\Menu\Models\ServiceDay;
use App\Modules\Order\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class DailyPublisher extends Component
{
    #[Url(as: 'date')]
    public string $date = '';

    public ?string $statusMessage = null;
    public ?string $statusType = null;

    public function mount(): void
    {
        if (! auth()->user()->can('menu.publish')) {
            abort(403);
        }

        if ($this->date === '') {
            $this->date = today()->toDateString();
        }
    }

    public function layoutData(): array
    {
        return [
            'title'   => 'Daily Menu Publisher',
            'heading' => 'Publish Daily Menu',
        ];
    }

    protected function selectedDate(): Carbon
    {
        return Carbon::parse($this->date);
    }

    protected function flash(string $type, string $message): void
    {
        $this->statusType = $type;
        $this->statusMessage = $message;
    }

    /*
    |--------------------------------------------------------------------------
    | Date navigation
    |--------------------------------------------------------------------------
    */

    public function previousDay(): void
    {
        $this->date = $this->selectedDate()->subDay()->toDateString();
    }

    public function nextDay(): void
    {
        $this->date = $this->selectedDate()->addDay()->toDateString();
    }

    public function goToday(): void
    {
        $this->date = today()->toDateString();
    }

    /*
    |--------------------------------------------------------------------------
    | Publish actions
    |--------------------------------------------------------------------------
    */

    public function toggleItem(int $menuItemId): void
    {
        if (! auth()->user()->can('menu.publish')) {
            abort(403);
        }

        $date = $this->selectedDate();
        $item = MenuItem::findOrFail($menuItemId);

        if (DailyMenu::isPublished($date, $item->id)) {
            DailyMenu::unpublish($date, [$item->id]);
            $this->flash('success', $item->name.' removed from '.$date->format('M j').'.');
        } else {
            try {
                DailyMenu::publish($date, [$item->id]);
                $this->flash('success', $item->name.' published for '.$date->format('M j').'.');
            } catch (\RuntimeException $e) {
                $this->flash('error', $e->getMessage());
            }
        }
    }

    public function publishAllFastFood(): void
    {
        if (! auth()->user()->can('menu.publish')) {
            abort(403);
        }

        $date = $this->selectedDate();
        $fastFoodIds = MenuItem::active()->fastFood()->pluck('id')->all();

        if (empty($fastFoodIds)) {
            $this->flash('error', 'No active fast food items to publish.');
            return;
        }

        DailyMenu::publish($date, $fastFoodIds);
        $this->flash('success', count($fastFoodIds).' fast food items published.');
    }

    public function clearDay(): void
    {
        if (! auth()->user()->can('menu.publish')) {
            abort(403);
        }

        $date = $this->selectedDate();
        $count = DailyMenu::unpublish($date);

        if ($count === 0) {
            $this->flash('error', 'Nothing was published on '.$date->format('M j').'.');
            return;
        }

        $this->flash('success', $count.' items removed from '.$date->format('M j').'.');
    }

    public function copyFromYesterday(): void
    {
        if (! auth()->user()->can('menu.publish')) {
            abort(403);
        }

        $date = $this->selectedDate();
        $yesterday = $date->copy()->subDay();

        $itemIds = DailyMenu::query()
            ->whereDate('service_date', $yesterday)
            ->pluck('menu_item_id')
            ->all();

        if (empty($itemIds)) {
            $this->flash('error', 'Nothing was published on '.$yesterday->format('M j').'.');
            return;
        }

        // Guard: check mains limit
        $mains = MenuItem::whereIn('id', $itemIds)->where('type', 'main')->count();

        if ($mains > DailyMenu::MAX_MAINS_PER_DAY) {
            $this->flash('error', 'Yesterday\'s menu had '.$mains.' mains — over the limit. Publish manually.');
            return;
        }

        DailyMenu::unpublish($date);
        DailyMenu::publish($date, $itemIds);

        $this->flash('success', count($itemIds).' items copied from '.$yesterday->format('M j').'.');
    }

    /*
    |--------------------------------------------------------------------------
    | Service day
    |--------------------------------------------------------------------------
    */

    public function toggleServiceDay(): void
    {
        if (! auth()->user()->can('menu.publish')) {
            abort(403);
        }

        $day = ServiceDay::forDate($this->selectedDate());
        $day->is_open = ! $day->is_open;
        $day->save();

        $this->flash('success', 'Service day is now '.($day->is_open ? 'OPEN' : 'CLOSED').'.');
    }

    /*
    |--------------------------------------------------------------------------
    | Render
    |--------------------------------------------------------------------------
    */

    public function render(): View
    {
        $selectedDate = $this->selectedDate();
        $serviceDay = ServiceDay::forDate($selectedDate);

        $mains    = MenuItem::active()->mains()->ordered()->get();
        $fastFood = MenuItem::active()->fastFood()->ordered()->get();

        $publishedIds = DailyMenu::query()
            ->whereDate('service_date', $selectedDate)
            ->pluck('menu_item_id')
            ->all();

        $stats = [
            'mainsPublished'    => collect($mains)->whereIn('id', $publishedIds)->count(),
            'fastfoodPublished' => collect($fastFood)->whereIn('id', $publishedIds)->count(),
            'orderCount'        => Order::forDate($selectedDate)->count(),
            'orderRevenue'      => (float) Order::forDate($selectedDate)
                                        ->where('status', '!=', Order::STATUS_CANCELLED)
                                        ->sum('total'),
            'maxMains'          => DailyMenu::MAX_MAINS_PER_DAY,
        ];

        return view('admin.menu.daily', compact(
            'selectedDate',
            'serviceDay',
            'mains',
            'fastFood',
            'publishedIds',
            'stats',
        ));
    }
}