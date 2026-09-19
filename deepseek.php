use App\Modules\Menu\Models\DailyMenu;

// Items published this week
DailyMenu::query()
    ->whereBetween('service_date', [now()->startOfWeek()->toDateString(), now()->endOfWeek()->toDateString()])
    ->count();

exit