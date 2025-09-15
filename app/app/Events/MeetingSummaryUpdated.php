<?php

namespace App\Events;

use App\Models\MeetingSummary;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MeetingSummaryUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public MeetingSummary $summary)
    {
    }

    public function broadcastOn(): Channel
    {
        $orgId = $this->summary->organization_id ?: 'global';
        return new PrivateChannel('organization.' . $orgId);
    }

    public function broadcastAs(): string
    {
        return 'meeting-summary.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->summary->id,
            'organization_id' => $this->summary->organization_id,
            'processing_status' => $this->summary->processing_status,
            'error_message' => $this->summary->error_message,
            'summary' => $this->summary->summary,
        ];
    }
}
