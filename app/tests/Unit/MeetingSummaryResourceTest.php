<?php

namespace Tests\Unit;

use App\Filament\Resources\MeetingSummaryResource;
use App\Models\MeetingSummary;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class MeetingSummaryResourceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_filters_query_for_normal_user(): void
    {
        $org = Organization::factory()->create();

        $user = User::factory()->create([
            'organization_id' => $org->id,
            'is_super_admin' => false,
        ]);
        Auth::login($user);

        MeetingSummary::factory()->create(['organization_id' => $org->id]);
        MeetingSummary::factory()->create(['organization_id' => Organization::factory()->create()->id]);

        $query = MeetingSummaryResource::getEloquentQuery()->get();
        $this->assertCount(1, $query);
        $this->assertEquals($org->id, $query->first()->organization_id);
    }

    #[Test]
    public function it_does_not_filter_query_for_super_admin(): void
    {
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();

        $user = User::factory()->create(['is_super_admin' => true, 'organization_id' => $org1->id]);
        Auth::login($user);

        MeetingSummary::factory()->create(['organization_id' => $org1->id]);
        MeetingSummary::factory()->create(['organization_id' => $org2->id]);

        $query = MeetingSummaryResource::getEloquentQuery()->get();
        $this->assertCount(2, $query);
    }

    #[Test]
    public function it_can_create_when_authenticated(): void
    {
        $user = User::factory()->create();
        Auth::login($user);

        $this->assertTrue(MeetingSummaryResource::canCreate());
    }

    #[Test]
    public function it_cannot_create_when_not_authenticated(): void
    {
        Auth::logout();
        $this->assertFalse(MeetingSummaryResource::canCreate());
    }
}
