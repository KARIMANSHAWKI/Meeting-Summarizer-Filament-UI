<?php

namespace App\Filament\Resources\MeetingSummaryResource\Pages;

use App\Filament\Resources\MeetingSummaryResource;
use Filament\Resources\Pages\EditRecord;

class EditMeetingSummary extends EditRecord
{
    protected function getListeners(): array
    {
        $orgId = auth()->user()?->organization_id ?? 'global';
        return [
            "echo:private(organization.$orgId),meeting-summary.updated" => 'onSummaryUpdated',
        ];
    }

    public function onSummaryUpdated(array $payload): void
    {
        if (! $this->record) return;
        if (($payload['id'] ?? null) !== $this->record->id) return;
        // Reload the record and refill the form so fields update live
        $this->record->refresh();
        $this->fillForm();
        $this->dispatch('$refresh');
        \Filament\Notifications\Notification::make()
            ->title('Processing ' . ($this->record->processing_status === 'done' ? 'completed' : 'failed'))
            ->success($this->record->processing_status === 'done')
            ->danger($this->record->processing_status === 'failed')
            ->send();
    }
    protected static string $resource = MeetingSummaryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // You can add actions like DeleteAction here.
        ];
    }
}
