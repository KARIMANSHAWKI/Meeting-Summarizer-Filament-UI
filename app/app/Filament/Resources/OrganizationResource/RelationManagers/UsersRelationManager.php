<?php

namespace App\Filament\Resources\OrganizationResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Auth;

class UsersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    protected static ?string $title = 'Users';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('email')->searchable()->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->headerActions([
                // Only super admins can create users from here; hide for org users
                Tables\Actions\CreateAction::make()->visible(fn () => (bool) (Auth::user()?->is_super_admin)),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->visible(fn () => (bool) (Auth::user()?->is_super_admin)),
                Tables\Actions\DeleteAction::make()->visible(fn () => (bool) (Auth::user()?->is_super_admin)),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()->visible(fn () => (bool) (Auth::user()?->is_super_admin)),
            ]);
    }

    public static function canViewForRecord($ownerRecord, string $pageClass): bool
    {
        $user = Auth::user();
        if (! $user) return false;
        // Super admins can view for any organization
        if ($user->is_super_admin ?? false) return true;
        // Organization users can view only their own organization's users
        return (string) $ownerRecord->id === (string) $user->organization_id;
    }
}
