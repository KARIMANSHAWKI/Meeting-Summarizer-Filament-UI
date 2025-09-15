<?php

namespace App\Filament\Widgets;

use App\Models\MeetingSummary;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;

class UsersCountWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '10s';

    public function getColumnSpan(): int|string|array
    {
        return 1;
    }

    protected function getColumns(): int
    {
        return 1;
    }

    protected function getCards(): array
    {

        if (auth()->user()->organization_id){
            $systemType = 'organization';
            $count = User::query()->where('organization_id', auth()->user()->organization_id)->count();
        }else{
            $count = User::query()->count();
            $systemType = 'system';
        }

        return [
            Card::make('Users', (string) $count)
                ->description('Total users in the ' . $systemType)
                ->icon('heroicon-o-user-group')
                ->color('success'),
        ];
    }

    public static function canView(): bool
    {
        return auth()->check();
    }
}
