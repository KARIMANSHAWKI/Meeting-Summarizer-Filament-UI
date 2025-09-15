<?php

namespace Tests\Unit;

use App\Jobs\SummarizeMediaJob;
use App\Models\MeetingSummary;
use App\Services\FastApiClient;
use Database\Factories\MeetingSummaryFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SummarizeMediaJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // sqlite in-memory schema comes from migrations when running artisan test
    }

    public function test_job_normalizes_and_updates_record()
    {
        $summary = MeetingSummary::factory()->create([
            'input_type' => 'text',
            'input_text' => 'hello',
            'processing_status' => 'pending',
        ]);

        // Fake FastApiClient
        $this->app->bind(FastApiClient::class, function () {
            return new class extends FastApiClient {
                public function summarize(array $payload): array
                {
                    return [
                        'summary' => 'S',
                        'decisions' => ['A', null, ''],
                        'action_items' => [
                            'Do X',
                            ['task' => 'Do Y', 'owner' => 'Alice', 'due' => 'tomorrow'],
                            ['owner' => 'Bob'],
                        ],
                    ];
                }
            };
        });

        Event::fake();

        (new SummarizeMediaJob($summary->id))->handle(app(FastApiClient::class));

        $summary->refresh();
        $this->assertSame('done', $summary->processing_status);
        $this->assertSame('S', $summary->summary);
        $this->assertEquals(['A'], $summary->decisions);
        $this->assertEquals([
            ['task' => 'Do X'],
            ['task' => 'Do Y', 'owner' => 'Alice', 'deadline' => 'tomorrow'],
            ['task' => ''],
        ], $summary->action_items);
    }

    public function test_job_handles_failure_and_sets_failed_status()
    {
        $summary = MeetingSummary::factory()->create([
            'input_type' => 'text',
            'input_text' => 'hello',
            'processing_status' => 'pending',
        ]);

        $this->app->bind(FastApiClient::class, function () {
            return new class extends FastApiClient {
                public function summarize(array $payload): array
                {
                    throw new \RuntimeException('boom');
                }
            };
        });

        try {
            (new SummarizeMediaJob($summary->id))->handle(app(FastApiClient::class));
        } catch (\Throwable $e) {
            // the job calls fail($e); ignore in test runtime
        }

        $summary->refresh();
        $this->assertSame('failed', $summary->processing_status);
        $this->assertSame('boom', $summary->error_message);
    }
}
