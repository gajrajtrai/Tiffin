<?php

namespace App\Modules\Admin\Http\Livewire;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

#[Layout('components.layouts.admin')]
class AuditLogIndex extends Component
{
    use WithPagination;

    #[Url(as: 'log', except: '')]
    public string $logNameFilter = '';

    #[Url(as: 'event', except: '')]
    public string $eventFilter = '';

    #[Url(as: 'user', except: '')]
    public string $userFilter = '';

    #[Url(as: 'from', except: '')]
    public string $from = '';

    #[Url(as: 'to', except: '')]
    public string $to = '';

    #[Url(as: 'q', except: '')]
    public string $search = '';

    public ?int $expandedId = null;

    public ?string $statusMessage = null;
    public ?string $statusType = null;

    public function mount(): void
    {
        if (! auth()->user()->can('audit.view')) {
            abort(403);
        }

        if ($this->from === '') {
            $this->from = now()->subDays(6)->toDateString();
        }
        if ($this->to === '') {
            $this->to = today()->toDateString();
        }
    }

    public function layoutData(): array
    {
        return [
            'title'   => 'Audit Log',
            'heading' => 'Audit Log',
        ];
    }

    public function updating(string $name): void
    {
        if (in_array($name, ['logNameFilter', 'eventFilter', 'userFilter', 'from', 'to', 'search'], true)) {
            $this->resetPage();
            $this->expandedId = null;
        }
    }

    public function clearFilters(): void
    {
        $this->logNameFilter = '';
        $this->eventFilter = '';
        $this->userFilter = '';
        $this->from = now()->subDays(6)->toDateString();
        $this->to = today()->toDateString();
        $this->search = '';
        $this->resetPage();
        $this->expandedId = null;
    }

    public function presetToday(): void
    {
        $this->from = today()->toDateString();
        $this->to = today()->toDateString();
        $this->resetPage();
    }

    public function presetLast7(): void
    {
        $this->from = now()->subDays(6)->toDateString();
        $this->to = today()->toDateString();
        $this->resetPage();
    }

    public function presetLast30(): void
    {
        $this->from = now()->subDays(29)->toDateString();
        $this->to = today()->toDateString();
        $this->resetPage();
    }

    public function toggleExpand(int $id): void
    {
        $this->expandedId = $this->expandedId === $id ? null : $id;
    }

    public function pruneOldLogs(): void
    {
        if (! auth()->user()->can('audit.view')) {
            abort(403);
        }

        $cutoff = now()->subDays(90);

        $deleted = Activity::query()
            ->where('created_at', '<', $cutoff)
            ->delete();

        $this->statusType = 'success';
        $this->statusMessage = $deleted.' log '.($deleted === 1 ? 'entry' : 'entries').' older than 90 days deleted.';
    }

    public function render(): View
    {
        $fromDate = Carbon::parse($this->from)->startOfDay();
        $toDate = Carbon::parse($this->to)->endOfDay();

        $query = Activity::query()
            ->with(['causer', 'subject'])
            ->whereBetween('created_at', [$fromDate, $toDate]);

        if ($this->logNameFilter !== '') {
            $query->where('log_name', $this->logNameFilter);
        }

        if ($this->eventFilter !== '') {
            $query->where('event', $this->eventFilter);
        }

        if ($this->userFilter !== '') {
            $query->where('causer_type', User::class)
                  ->where('causer_id', $this->userFilter);
        }

        if ($this->search !== '') {
            $term = '%'.$this->search.'%';
            $query->where(function ($q) use ($term) {
                $q->where('description', 'like', $term)
                  ->orWhere('subject_type', 'like', $term);
            });
        }

        $activities = $query->orderByDesc('created_at')->paginate(40);

        $logNames = Activity::query()
            ->select('log_name')
            ->whereNotNull('log_name')
            ->distinct()
            ->orderBy('log_name')
            ->pluck('log_name');

        $events = Activity::query()
            ->select('event')
            ->whereNotNull('event')
            ->distinct()
            ->orderBy('event')
            ->pluck('event');

        $causers = User::query()
            ->whereIn('id', Activity::query()
                ->whereNotNull('causer_id')
                ->where('causer_type', User::class)
                ->distinct()
                ->pluck('causer_id')
            )
            ->orderBy('name')
            ->get(['id', 'name']);

        $stats = [
            'total'   => Activity::query()->whereBetween('created_at', [$fromDate, $toDate])->count(),
            'updates' => Activity::query()->whereBetween('created_at', [$fromDate, $toDate])->where('event', 'updated')->count(),
            'creates' => Activity::query()->whereBetween('created_at', [$fromDate, $toDate])->where('event', 'created')->count(),
            'deletes' => Activity::query()->whereBetween('created_at', [$fromDate, $toDate])->where('event', 'deleted')->count(),
        ];

        return view('admin.audit.index', compact(
            'activities',
            'logNames',
            'events',
            'causers',
            'stats',
            'fromDate',
            'toDate',
        ));
    }
}