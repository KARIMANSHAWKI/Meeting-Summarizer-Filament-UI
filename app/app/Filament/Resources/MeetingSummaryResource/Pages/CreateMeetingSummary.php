<?php

namespace App\Filament\Resources\MeetingSummaryResource\Pages;

use App\Filament\Resources\MeetingSummaryResource;
use App\Models\MeetingSummary;
use App\Services\FastApiClient;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CreateMeetingSummary extends CreateRecord
{
    protected static string $resource = MeetingSummaryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();
        if ($user && ($user->is_super_admin ?? false)) {
            // Super admin may optionally choose an organization, otherwise leave null for global
            $data['organization_id'] = $data['organization_id'] ?? null;
        } else {
            // Force to the authenticated user's organization
            $data['organization_id'] = $user?->organization_id;
            unset($data['organization_id']); // ensure no spoofing via form for non-super admins
            $data['organization_id'] = $user?->organization_id;
        }

        $client = app(FastApiClient::class);

        // Prepare payload based on input type
        if ($data['input_type'] === 'text') {
            $payload = [
                'type' => 'text',
                'title' => $data['title'] ?? null,
                'transcript' => $data['input_text'] ?? '',
            ];
        } else {
            $payload = [
                'type' => 'media',
                'title' => $data['title'] ?? null,
                // pass absolute filesystem path for multipart upload
                'file_path' => $data['input_media_path'] ? Storage::disk('public')->path($data['input_media_path']) : null,
                'filename' => $data['input_media_path'] ? basename($data['input_media_path']) : null,
            ];
        }

        // Do not call FastAPI synchronously for either type; queue a background job after create
        $data['processing_status'] = 'pending';
        // Ensure summary fields are null until job populates them
        $data['summary'] = null;
        $data['decisions'] = null;
        $data['action_items'] = null;
        $data['source'] = null;
        $data['azure_raw'] = null;

        return $data;
    }

    protected function afterCreate(): void
    {
        // Always dispatch background job to process without blocking the request
        if ($this->record) {
            \App\Jobs\SummarizeMediaJob::dispatch($this->record->id);
            Notification::make()
                ->title('Request received - processing started')
                ->body('We are processing your request in the background. You can refresh later to see the results.')
                ->success()
                ->send();
            return;
        }

        Notification::make()
            ->title('Meeting summary created')
            ->success()
            ->send();
    }
}
