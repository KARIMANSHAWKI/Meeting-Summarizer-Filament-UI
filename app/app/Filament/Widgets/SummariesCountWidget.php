<?php

namespace App\Filament\Widgets;

use App\Models\MeetingSummary;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;

class SummariesCountWidget extends BaseWidget
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
            $count = MeetingSummary::query()->where('organization_id', auth()->user()->organization_id)->count();
        }else{
            $count = MeetingSummary::query()->count();
            $systemType = 'system';
        }

        return [
            Card::make('Summaries', (string) $count)
                ->description('Total summaries in the '. $systemType)
                ->icon('heroicon-o-rectangle-stack')
                ->color('warning'),
        ];
    }

    public static function canView(): bool
    {
        return auth()->check();
    }
}
