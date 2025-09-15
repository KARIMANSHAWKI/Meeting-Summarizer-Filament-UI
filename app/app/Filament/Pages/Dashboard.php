<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    /**
     * Control the dashboard widgets grid columns.
     * Return 3 so three small widgets can appear on the same row on large screens.
     * Filament will stack them on smaller screens responsively.
     */
    public function getColumns(): int | string | array
    {
        return 3;
    }
}
