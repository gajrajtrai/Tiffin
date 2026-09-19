<?php

namespace App\Modules\Admin\Http\Livewire;

use App\Modules\Menu\Models\DailyMenu;
use App\Modules\Menu\Models\MenuItem;
use App\Modules\Menu\Models\ServiceDay;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class WeeklyPublisher extends Component
{
    #[Url(as: 'week')]
    public string $weekStart = '';

    public ?string $statusMessage = null;
    public ?string $statusType = null;

    public function mount(): void
    {
        if (! auth()->user()->can('menu.publish')) {
            abort(403);
        }

        if ($this->weekStart === '') {
            $this->weekStart = now()->startOfWeek(Carbon::MONDAY)->toDateString();
        }
    }

    public function layoutData(): array
    {
        return [
            'title'   => 'Weekly Menu Publisher',
            'heading' => 'Weekly Publisher',
        ];
    }

    protected function weekStartDate(): Carbon
    {
        return Carbon::parse($this->weekStart)->startOfWeek(Carbon::MONDAY);
    }

    protected function weekDates(): array
    {
        $start = $this->weekStartDate();
        return collect(range(0, 6))
            ->map(fn ($i) => $start->copy()->addDays($i))
            ->all();
    }

    protected function flash(string $type, string $message): void
    {
        $this->statusType = $type;
        $this->statusMessage = $message;
    }

    /*
    |--------------------------------------------------------------------------
    | Navigation
    |--------------------------------------------------------------------------
    */

    public function previousWeek(): void
    {
        $this->weekStart = $this->weekStartDate()->subWeek()->toDateString();
    }

    public function nextWeek(): void
    {
        $this->weekStart = $this->weekStartDate()->addWeek()->toDateString();
    }

    public function currentWeek(): void
    {
        $this->weekStart = now()->startOfWeek(Carbon::MONDAY)->toDateString();
    }

    /*
    |--------------------------------------------------------------------------
    | Cell toggle
    |--------------------------------------------------------------------------
    */

    public function toggle(int $menuItemId, string $dateString): void
    {
        if (! auth()->user()->can('menu.publish')) {
            abort(403);
        }

        $date = Carbon::parse($dateString);
        $item = MenuItem::findOrFail($menuItemId);

        if (DailyMenu::isPublished($date, $item->id)) {
            DailyMenu::unpublish($date, [$item->id]);
            $this->flash('success', $item->name.' removed from '.$date->format('D, M j').'.');
        } else {
            try {
                DailyMenu::publish($date, [$item->id]);
                $this->flash('success', $item->name.' published for '.$date->format('D, M j').'.');
            } catch (\RuntimeException $e) {
                $this->flash('error', $e->getMessage());
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Bulk actions
    |--------------------------------------------------------------------------
    */

    /**
     * Copy Monday's menu to all weekdays Tue-Fri (of the same week).
     */
    public function copyMondayToWeekdays(): void
    {
        if (! auth()->user()->can('menu.publish')) {
            abort(403);
        }

        $monday = $this->weekStartDate();
        $mondayIds = DailyMenu::query()
            ->whereDate('service_date', $monday)
            ->pluck('menu_item_id')
            ->all();

        if (empty($mondayIds)) {
            $this->flash('error', 'Nothing is published on Monday ('.$monday->format('M j').').');
            return;
        }

        $mains = MenuItem::whereIn('id', $mondayIds)->where('type', 'main')->count();
        if ($mains > DailyMenu::MAX_MAINS_PER_DAY) {
            $this->flash('error', 'Monday has '.$mains.' mains — cannot copy.');
            return;
        }

        $copied = 0;
        for ($i = 1; $i <= 4; $i++) { // Tue-Fri
            $day = $monday->copy()->addDays($i);
            DailyMenu::unpublish($day);
            DailyMenu::publish($day, $mondayIds);
            $copied++;
        }

        $this->flash('success', 'Monday\'s menu copied to '.$copied.' weekdays.');
    }

    public function clearWeek(): void
    {
        if (! auth()->user()->can('menu.publish')) {
            abort(403);
        }

        $start = $this->weekStartDate();
        $end = $start->copy()->endOfWeek(Carbon::SUNDAY);

        $count = DailyMenu::query()
            ->whereBetween('service_date', [$start->toDateString(), $end->toDateString()])
            ->delete();

        if ($count === 0) {
            $this->flash('error', 'Nothing was published this week.');
            return;
        }

        $this->flash('success', $count.' items cleared from the week.');
    }

    public function toggleServiceDay(string $dateString): void
    {
        if (! auth()->user()->can('menu.publish')) {
            abort(403);
        }

        $date = Carbon::parse($dateString);
        $day = ServiceDay::forDate($date);
        $day->is_open = ! $day->is_open;
        $day->save();

        $this->flash('success', $date->format('D, M j').' is now '.($day->is_open ? 'OPEN' : 'CLOSED').'.');
    }

    /*
    |--------------------------------------------------------------------------
    | Render
    |--------------------------------------------------------------------------
    */

    public function render(): View
    {
        $dates = $this->weekDates();
        $mains = MenuItem::active()->mains()->ordered()->get();
        $fastFood = MenuItem::active()->fastFood()->ordered()->get();

        $publishedByDate = [];
        foreach ($dates as $d) {
            $publishedByDate[$d->toDateString()] = DailyMenu::query()
                ->whereDate('service_date', $d)
                ->pluck('menu_item_id')
                ->all();
        }

        $serviceDays = [];
        foreach ($dates as $d) {
            $serviceDays[$d->toDateString()] = ServiceDay::forDate($d);
        }

        $mainCountsByDate = [];
        foreach ($dates as $d) {
            $mainCountsByDate[$d->toDateString()] = DailyMenu::query()
                ->whereDate('service_date', $d)
                ->whereHas('menuItem', fn ($q) => $q->where('type', 'main'))
                ->count();
        }

        return view('admin.menu.weekly', compact(
            'dates',
            'mains',
            'fastFood',
            'publishedByDate',
            'serviceDays',
            'mainCountsByDate',
        ));
    }
}