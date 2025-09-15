<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Filament\Pages\Dashboard;
use PHPUnit\Framework\Attributes\Test;

class DashboardTest extends TestCase
{
    #[Test]
    public function it_returns_three_columns_for_dashboard(): void
    {
        $dashboard = new Dashboard();

        $this->assertSame(3, $dashboard->getColumns());
    }
}
