<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Administration';

    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();
        if (! $user) return false;
        // Show Users menu for super admins and organization users
        return ($user->is_super_admin ?? false) || ! empty($user->organization_id);
    }

    public static function form(Form $form): Form
    {
        $isEditing = request()->routeIs('filament.admin.resources.users.edit');

        return $form
            ->schema([
                Section::make('User Information')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('email')
                                ->email()
                                ->required()
                                ->maxLength(255),
                        ]),
                        Grid::make(2)->schema([
                            Select::make('organization_id')
                                ->label('Organization')
                                ->relationship('organization', 'name')
                                ->searchable()
                                ->preload(),
                        ]),
                    ]),
                Section::make('Security')
                    ->schema([
                        TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->required(fn () => ! $isEditing)
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? $state : null)
                            ->dehydrated(fn ($state) => filled($state))
                            ->helperText($isEditing ? 'Leave blank to keep current password.' : null),
                    ])->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('email')->searchable()->sortable(),
                TextColumn::make('organization.name')->label('Organization')->sortable(),
                BadgeColumn::make('is_super_admin')
                    ->label('Role')
                    ->colors([
                        'success' => fn ($state) => (bool) $state,
                        'gray' => fn ($state) => ! (bool) $state,
                    ])
                    ->formatStateUsing(fn ($state) => $state ? 'Super Admin' : 'User'),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();
        if ($user && !($user->is_super_admin ?? false)) {
            $query->where('organization_id', $user->organization_id);
        }
        return $query;
    }

    public static function canViewAny(): bool
    {
        // Allow super admins and organization users to view the Users list
        return Auth::check();
    }

    public static function canCreate(): bool
    {
        return Auth::user()?->is_super_admin ?? false;
    }

    public static function canEdit($record): bool
    {
        return Auth::user()?->is_super_admin ?? false;
    }

    public static function canDelete($record): bool
    {
        return Auth::user()?->is_super_admin ?? false;
    }
}
