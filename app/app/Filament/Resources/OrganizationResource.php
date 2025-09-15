<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrganizationResource\Pages;
use App\Models\Organization;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class OrganizationResource extends Resource
{
    protected static ?string $model = Organization::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationGroup = 'Administration';

    public static function getRelations(): array
    {
        return [
            OrganizationResource\RelationManagers\UsersRelationManager::class,
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()?->is_super_admin ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')->required()->maxLength(255)->disabled(fn () => ! (auth()->user()?->is_super_admin ?? false)),
                TextInput::make('slug')->required()->unique(ignoreRecord: true)->maxLength(255)->disabled(fn () => ! (auth()->user()?->is_super_admin ?? false)),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('slug')->searchable()->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrganizations::route('/'),
            'create' => Pages\CreateOrganization::route('/create'),
            'edit' => Pages\EditOrganization::route('/{record}/edit'),
        ];
    }

    // Restrict all Organization management to super admins
    public static function canViewAny(): bool
    {
        return Auth::user()?->is_super_admin;
    }

    public static function canCreate(): bool
    {
        return Auth::user()?->is_super_admin;
    }

    public static function canEdit($record): bool
    {
        $user = Auth::user();
        if (! $user) return false;
        if ($user->is_super_admin ?? false) return true;
        // Allow organization users to access their own organization record (fields are read-only for them)
        return (string) $record->id === (string) $user->organization_id;
    }

    public static function canDelete($record): bool
    {
        return Auth::user()?->is_super_admin;
    }
}
