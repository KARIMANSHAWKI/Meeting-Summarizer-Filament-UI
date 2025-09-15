<?php

namespace App\Filament\Widgets;

use App\Models\Organization;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;

class OrganizationsCountWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '10s';

    public function getColumnSpan(): int|string|array
    {
        // 3 equal columns on large screens; stacks on small screens automatically
        return 1;
    }

    protected function getColumns(): int
    {
        return 1; // only one card inside this widget
    }

    protected function getCards(): array
    {
        $count = Organization::query()->count();

        return [
            Card::make('Organizations', (string) $count)
                ->description('Total organizations in the system')
                ->icon('heroicon-o-building-office')
                ->color('primary'),
        ];
    }

    public static function canView(): bool
    {
        // Show to all authenticated users; change to only super admins if needed
        return auth()->check() && auth()->user()->organization_id == null;
    }
}
