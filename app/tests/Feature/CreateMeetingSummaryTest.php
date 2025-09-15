<?php

namespace Tests\Feature;

use App\Filament\Resources\MeetingSummaryResource\Pages\CreateMeetingSummary;
use App\Models\MeetingSummary;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CreateMeetingSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_super_admin_organization_is_enforced_and_job_dispatched()
    {
        Bus::fake();

        $org = \App\Models\Organization::factory()->create();
        $user = User::factory()->create([
            'organization_id' => $org->id,
            'is_super_admin' => false,
        ]);

        $this->actingAs($user);

        $page = app(CreateMeetingSummary::class);
        $method = (new \ReflectionClass($page))->getMethod('mutateFormDataBeforeCreate');
        $method->setAccessible(true);
        $data = [
            'organization_id' => 999, // attempt to spoof
            'title' => 'T',
            'input_type' => 'text',
            'input_text' => 'hello',
        ];
        $out = $method->invoke($page, $data);

        $this->assertSame($org->id, $out['organization_id']);
        $this->assertSame('pending', $out['processing_status']);
        $this->assertNull($out['summary']);
        $this->assertNull($out['decisions']);
        $this->assertNull($out['action_items']);

        $record = MeetingSummary::create($out);

        $page->record = $record;
        $after = (new \ReflectionClass($page))->getMethod('afterCreate');
        $after->setAccessible(true);
        $after->invoke($page);

        Bus::assertDispatched(\App\Jobs\SummarizeMediaJob::class);
    }

    public function test_super_admin_can_set_any_org_and_leave_null_then_job_dispatched()
    {
        Bus::fake();
        $orgA = \App\Models\Organization::factory()->create();
        $orgB = \App\Models\Organization::factory()->create();
        $admin = User::factory()->create([
            'organization_id' => $orgA->id,
            'is_super_admin' => true,
        ]);
        $this->actingAs($admin);

        $page = app(CreateMeetingSummary::class);
        $ref = new \ReflectionClass($page);
        $mutate = $ref->getMethod('mutateFormDataBeforeCreate');
        $mutate->setAccessible(true);

        // Case 1: Explicit organization_id provided should be kept
        $out1 = $mutate->invoke($page, [
            'organization_id' => $orgB->id,
            'title' => 'T1',
            'input_type' => 'text',
            'input_text' => 'hello',
        ]);
        $this->assertSame($orgB->id, $out1['organization_id']);
        $this->assertSame('pending', $out1['processing_status']);

        $rec1 = MeetingSummary::create($out1);
        $page->record = $rec1;
        $after = $ref->getMethod('afterCreate');
        $after->setAccessible(true);
        $after->invoke($page);

        // Case 2: No organization_id provided should remain null for super admin
        $out2 = $mutate->invoke($page, [
            'title' => 'T2',
            'input_type' => 'text',
            'input_text' => 'world',
        ]);
        $this->assertArrayHasKey('organization_id', $out2);
        $this->assertNull($out2['organization_id']);
        $this->assertSame('pending', $out2['processing_status']);

        $rec2 = MeetingSummary::create($out2);
        $page->record = $rec2;
        $after->invoke($page);

        Bus::assertDispatched(\App\Jobs\SummarizeMediaJob::class);
    }

    public function test_media_input_sets_pending_and_dispatches_job_using_public_disk()
    {
        Bus::fake();
        Storage::fake('public');

        $org = \App\Models\Organization::factory()->create();
        $user = User::factory()->create([
            'organization_id' => $org->id,
            'is_super_admin' => false,
        ]);
        $this->actingAs($user);

        // Create a fake uploaded file on the public disk
        Storage::disk('public')->put('uploads/test.mp4', 'abc');

        $page = app(CreateMeetingSummary::class);
        $ref = new \ReflectionClass($page);
        $mutate = $ref->getMethod('mutateFormDataBeforeCreate');
        $mutate->setAccessible(true);

        $out = $mutate->invoke($page, [
            'organization_id' => 999, // should be overridden to user's org
            'title' => 'Media Title',
            'input_type' => 'media',
            'input_media_path' => 'uploads/test.mp4',
        ]);

        $this->assertSame($org->id, $out['organization_id']);
        $this->assertSame('pending', $out['processing_status']);
        $this->assertNull($out['summary']);
        $this->assertNull($out['decisions']);
        $this->assertNull($out['action_items']);

        $record = MeetingSummary::create($out);
        $page->record = $record;

        $after = $ref->getMethod('afterCreate');
        $after->setAccessible(true);
        $after->invoke($page);

        Bus::assertDispatched(\App\Jobs\SummarizeMediaJob::class);
    }
}
