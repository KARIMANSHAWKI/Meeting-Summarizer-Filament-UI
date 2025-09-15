<?php

namespace Tests\Unit;

use App\Filament\Widgets\OrganizationsCountWidget;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationsCountWidgetTest extends TestCase
{
    use RefreshDatabase;



    /** @test */
    public function it_can_view_for_super_admin_like_users_without_org()
    {
        $user = User::factory()->create([
            'organization_id' => null,
        ]);
        $this->be($user);

        $this->assertTrue(OrganizationsCountWidget::canView());
    }

    /** @test */
    public function it_cannot_view_for_users_with_organization()
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create([
            'organization_id' => $org->id,
        ]);
        $this->be($user);

        $this->assertFalse(OrganizationsCountWidget::canView());
    }
}
