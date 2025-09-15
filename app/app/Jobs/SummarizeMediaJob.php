<?php

namespace App\Jobs;

use App\Models\MeetingSummary;
use App\Services\FastApiClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SummarizeMediaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 0; // no hard timeout; rely on worker/process timeouts
    public int $tries = 1;

    public function __construct(public int $meetingSummaryId)
    {
    }

    public function handle(FastApiClient $client): void
    {
        $summary = MeetingSummary::find($this->meetingSummaryId);
        if (!$summary) {
            return;
        }

        // mark processing
        $summary->processing_status = 'processing';
        $summary->save();
        try {
            if ($summary->input_type === 'media') {
                $path = $summary->input_media_path ? Storage::disk('public')->path($summary->input_media_path) : null;
                $payload = [
                    'type' => 'media',
                    'title' => $summary->title,
                    'file_path' => $path,
                    'filename' => $summary->input_media_path ? basename($summary->input_media_path) : null,
                ];
                $result = $client->summarizeMedia($payload);
            } else {
                $payload = [
                    'type' => 'text',
                    'title' => $summary->title,
                    'transcript' => $summary->input_text,
                ];
                $result = $client->summarize($payload);
            }

            $summary->summary = $result['summary'] ?? $summary->summary;

            // Normalize decisions/action_items for consistent storage
            $decisions = $result['decisions'] ?? null;
            if (is_string($decisions)) {
                $decoded = json_decode($decisions, true);
                $decisions = is_array($decoded) ? $decoded : [$decisions];
            }
            if (is_array($decisions)) {
                $decisions = array_values(array_filter($decisions, fn($d) => $d !== null && $d !== ''));
            }
            $summary->decisions = $decisions;

            $actionItems = $result['action_items'] ?? null;
            if (is_string($actionItems)) {
                $decoded = json_decode($actionItems, true);
                $actionItems = is_array($decoded) ? $decoded : [$actionItems];
            }
            if (is_array($actionItems)) {
                $actionItems = array_map(function ($item) {
                    if (!is_array($item)) {
                        return ['task' => (string) $item];
                    }
                    $owner = array_key_exists('owner', $item) ? (string) ($item['owner'] ?? '') : null;
                    $task = array_key_exists('task', $item) ? (string) ($item['task'] ?? '') : null;
                    $deadline = array_key_exists('deadline', $item) ? (string) ($item['deadline'] ?? '') : (array_key_exists('due', $item) ? (string) ($item['due'] ?? '') : null);
                    // Always include task; if missing/empty, set to empty string and ignore owner/deadline
                    if ($task === null || $task === '') {
                        return ['task' => ''];
                    }
                    $normalized = ['task' => $task];
                    if ($owner !== null && $owner !== '') {
                        $normalized['owner'] = $owner;
                    }
                    if ($deadline !== null && $deadline !== '') {
                        $normalized['deadline'] = $deadline;
                    }
                    return $normalized;
                }, $actionItems);
            }
            $summary->action_items = $actionItems;

            $summary->source = $result['source'] ?? $summary->source;
            $summary->azure_raw = $result['azure_raw'] ?? null;
            $summary->processing_status = 'done';
            $summary->error_message = null;
            $summary->save();
            event(new \App\Events\MeetingSummaryUpdated($summary));
        } catch (\Throwable $e) {
            Log::error('Summarize job failed', ['id' => $summary->id, 'error' => $e->getMessage()]);
            $summary->processing_status = 'failed';
            $summary->error_message = $e->getMessage();
            $summary->save();
            event(new \App\Events\MeetingSummaryUpdated($summary));
            $this->fail($e);
        }
    }
}
