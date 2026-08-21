<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasResourcePermissions;
use App\Filament\Resources\UserResource\Pages;
use App\Models\Role;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class UserResource extends Resource
{
    use HasResourcePermissions;

    protected static string $viewPermission = 'view_users';

    protected static string $createPermission = 'create_users';

    protected static string $editPermission = 'edit_users';

    protected static string $deletePermission = 'delete_users';

    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Users & Access';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('User Info')->schema([
                Forms\Components\TextInput::make('name')->required()->maxLength(191),
                Forms\Components\TextInput::make('phone')
                    ->label('Phone Number')
                    ->tel()
                    ->required()
                    ->maxLength(20)
                    ->unique(ignoreRecord: true)
                    ->helperText('Customers sign in with this number. Stored as 01XXXXXXXXX.'),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->nullable()
                    ->unique(ignoreRecord: true)
                    ->helperText('Optional. Used for order emails and password recovery.'),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $context) => $context === 'create')
                    ->label(fn (string $context) => $context === 'edit' ? 'New Password (leave blank to keep)' : 'Password'),
            ])->columns(2),

            Forms\Components\Section::make('Status & Role')->schema([
                Forms\Components\Toggle::make('is_active')->default(true)->inline(false),

                Forms\Components\Select::make('roles')
                    ->label('Role')
                    ->options(fn () => Role::orderBy('name')->pluck('name', 'id')
                        ->map(fn ($name) => ucwords(str_replace('_', ' ', $name))))
                    ->placeholder('Customer (no panel access)')
                    ->nullable()
                    ->helperText('Only super admin can assign roles.')
                    ->visible(fn () => auth()->user()?->isSuperAdmin())
                    ->afterStateHydrated(function (Forms\Components\Select $component, $record) {
                        $component->state($record?->roles()->first()?->id);
                    })
                    ->dehydrated(false),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('phone')->label('Phone')->searchable()->copyable()->placeholder('—'),
                Tables\Columns\TextColumn::make('email')->searchable()->copyable()->placeholder('—'),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Role')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'super_admin' => 'danger',
                        'admin' => 'warning',
                        'staff' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'super_admin' => 'Super Admin',
                        'admin' => 'Admin',
                        'staff' => 'Staff',
                        default => ucfirst($state),
                    })
                    ->placeholder('Customer'),
                Tables\Columns\IconColumn::make('is_active')->boolean()->label('Active'),
                Tables\Columns\TextColumn::make('orders_count')
                    ->label('Orders')->counts('orders'),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Active'),
                Tables\Filters\SelectFilter::make('role')
                    ->label('Role')
                    ->options([
                        'super_admin' => 'Super Admin',
                        'admin' => 'Admin',
                        'staff' => 'Staff',
                        'customer' => 'Customer (no role)',
                    ])
                    ->query(function ($query, $data) {
                        if (blank($data['value'])) {
                            return;
                        }
                        if ($data['value'] === 'customer') {
                            $query->whereDoesntHave('roles');
                        } else {
                            $query->whereHas('roles', fn ($q) => $q->where('name', $data['value']));
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (User $record) => ! $record->isSuperAdmin()),
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
}
