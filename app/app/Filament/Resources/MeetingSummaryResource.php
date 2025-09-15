<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MeetingSummaryResource\Pages;
use App\Models\MeetingSummary;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;

class MeetingSummaryResource extends Resource
{
    protected static ?string $model = MeetingSummary::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Meetings';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('title')
                    ->label('Title')
                    ->required()
                    ->maxLength(255),
                Select::make('organization_id')
                    ->label('Organization')
                    ->relationship('organization', 'name')
                    ->searchable()
                    ->preload()
                    ->visible(fn () => auth()->user()?->is_super_admin)
                    ->helperText('Only visible to super admins. Leave empty for global summary.'),
                Radio::make('input_type')
                    ->options([
                        'text' => 'Paste Transcript Text',
                        'media' => 'Upload Audio/Video',
                    ])
                    ->default('text')
                    ->live(),
                Textarea::make('input_text')
                    ->label('Transcript Text')
                    ->visible(fn (callable $get) => $get('input_type') === 'text')
                    ->rows(10)
                    ->required(fn (callable $get) => $get('input_type') === 'text'),
                FileUpload::make('input_media_path')
                    ->label('Video/Audio')
                    ->disk('public')
                    ->directory('meeting-media')
                    ->acceptedFileTypes(['video/*','audio/*'])
                    ->maxSize(1024 * 200)
                    ->visibility('public')
                    ->preserveFilenames()
                    ->dehydrated(fn (callable $get) => $get('input_type') === 'media')
                    ->hidden(fn (callable $get) => $get('input_type') !== 'media')
                    ->required(fn (callable $get) => $get('input_type') === 'media')
                    ->getUploadedFileNameForStorageUsing(function ($file) {
                        \Log::info('Uploading file...', [
                            'originalName' => $file->getClientOriginalName(),
                            'mime' => $file->getClientMimeType(),
                            'size' => $file->getSize(),
                        ]);
                        return $file->getClientOriginalName();
                    })
                ,
                // Readonly outputs
                Textarea::make('summary')
                    ->rows(5)
                    ->readOnly()
                    ->dehydrated(true)
                    ->columnSpanFull(),
                \Filament\Forms\Components\Placeholder::make('decisions_list')
                    ->label('Decisions')
                    ->content(function ($record) {
                        if (! $record || empty($record->decisions)) return '—';
                        $items = is_array($record->decisions) ? $record->decisions : (json_decode($record->decisions, true) ?? []);
                        if (! $items) return '—';
                        $html = '<ul style="margin-left:1rem; padding-left:0; list-style: none;">';
                        foreach ($items as $item) {
                            $text = is_array($item) ? (json_encode($item, JSON_UNESCAPED_UNICODE)) : (string) $item;
                            $html .= '<li style="margin: .25rem 0;"><span style="display:inline-block; width:.6rem; height:.6rem; background:#16a34a; border-radius:50%; margin-right:.5rem; vertical-align:middle;"></span><span style="vertical-align:middle;">'.e($text).'</span></li>';
                        }
                        $html .= '</ul>';
                        return new \Illuminate\Support\HtmlString($html);
                    })
                    ->helperText('Filled automatically after processing')
                    ->columnSpanFull(),
                \Filament\Forms\Components\Placeholder::make('action_items_list')
                    ->label('Action Items')
                    ->content(function ($record) {
                        if (! $record || empty($record->action_items)) return '—';
                        $items = is_array($record->action_items) ? $record->action_items : (json_decode($record->action_items, true) ?? []);
                        if (! $items) return '—';
                        // Render as a responsive grid of cards (tabs/pills style)
                        $hasStructured = false;
                        foreach ($items as $it) { if (is_array($it) && (isset($it['owner']) || isset($it['task']) || isset($it['deadline']) || isset($it['due']) )) { $hasStructured = true; break; } }
                        $html = '<div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(260px, 1fr)); gap:.75rem;">';
                        $i = 1;
                        foreach ($items as $item) {
                            if (! is_array($item)) { $item = ['task' => (string) $item]; }
                            $owner = (string)($item['owner'] ?? '');
                            $task = (string)($item['task'] ?? json_encode($item, JSON_UNESCAPED_UNICODE));
                            $deadline = (string)($item['deadline'] ?? ($item['due'] ?? ''));
                            $html .= '<div style="border:1px solid #374151; border-radius:.5rem; padding:.75rem; background:#111827;">'
                                .'<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:.5rem;">'
                                    .'<span style="display:inline-flex; align-items:center; gap:.5rem; font-weight:600; color:#e5e7eb;"><span style="display:inline-block; width:.5rem; height:.5rem; background:#3b82f6; border-radius:50%"></span> Item #'.($i++).'</span>'
                                    .($deadline ? '<span title="Deadline" style="font-size:.75rem; color:#dbeafe; background:#1e3a8a; padding:.15rem .4rem; border-radius:.25rem;">🗓️ Deadline: '.e($deadline).'</span>' : '')
                                .'</div>'
                                .'<div style="font-size:.95rem; color:#f3f4f6; margin-bottom:.35rem;">'.e($task).'</div>'
                                .($owner ? '<div style="font-size:.85rem; color:#9ca3af;">Owner: <span style="color:#e5e7eb; font-weight:500;">'.e($owner).'</span></div>' : '')
                            .'</div>';
                        }
                        $html .= '</div>';
                        return new \Illuminate\Support\HtmlString($html);
                    })
                    ->helperText('Filled automatically after processing')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('title')->label('Title')->limit(60)->searchable()->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('processing_status')->badge()->label('Status'),
//                TextColumn::make('decisions')->label('Decisions Count')->badge()->formatStateUsing(fn ($state) => is_array($state) ? count($state) : (is_string($state) ? count(json_decode($state, true) ?? []) : 0)),
//                TextColumn::make('action_items')->label('Action Items Count')->badge()->formatStateUsing(fn ($state) => is_array($state) ? count($state) : (is_string($state) ? count(json_decode($state, true) ?? []) : 0)),
                TextColumn::make('summary')->limit(80),
            ])
            ->defaultSort('created_at','desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMeetingSummaries::route('/'),
            'create' => Pages\CreateMeetingSummary::route('/create'),
            'edit' => Pages\EditMeetingSummary::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();
        if ($user) {
            if (!($user->is_super_admin ?? false)) {
                $query->where('organization_id', $user->organization_id);
            }
        }
        return $query;
    }

    public static function canCreate(): bool
    {
        return Auth::check();
    }
}
