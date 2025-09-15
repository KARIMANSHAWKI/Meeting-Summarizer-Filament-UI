<?php

namespace App\Filament\Resources\MeetingSummaryResource\Pages;

use App\Filament\Resources\MeetingSummaryResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListMeetingSummaries extends ListRecords
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
        // Simply refresh the table when any record in the organization updates
        $this->dispatch('$refresh');
    }
    protected static string $resource = MeetingSummaryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
