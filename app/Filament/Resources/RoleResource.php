<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use App\Models\Permission;
use App\Models\Role;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;
    protected static ?string $navigationIcon  = 'heroicon-o-shield-check';
    protected static ?string $navigationGroup = 'Users & Access';
    protected static ?int    $navigationSort  = 2;
    protected static ?string $recordTitleAttribute = 'name';

    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function form(Form $form): Form
    {
        $grouped = Permission::orderBy('name')
            ->get()
            ->groupBy(function (Permission $p) {
                // Group by the first word before the underscore  e.g. "view_products" → "Products"
                $prefix = explode('_', $p->name, 2)[1] ?? $p->name;
                return ucfirst(str_replace('_', ' ', $prefix));
            })
            ->sortKeys();

        $sections = [];

        foreach ($grouped as $group => $perms) {
            $sections[] = Forms\Components\Section::make($group)
                ->schema([
                    Forms\Components\CheckboxList::make('permissions')
                        ->relationship('permissions', 'name')
                        ->options($perms->pluck('name', 'id')->map(
                            fn ($name) => ucwords(str_replace('_', ' ', explode('_', $name, 2)[0]))
                                . ' ' . ucwords(str_replace('_', ' ', explode('_', $name, 2)[1] ?? ''))
                        ))
                        ->label('')
                        ->columns(3)
                        ->gridDirection('row'),
                ])
                ->columns(1)
                ->collapsible()
                ->collapsed(false);
        }

        return $form->schema([
            Forms\Components\Section::make('Role Details')->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(100)
                    ->unique(Role::class, 'name', ignoreRecord: true)
                    ->helperText('Use snake_case, e.g. inventory_manager'),

                Forms\Components\Hidden::make('guard_name')->default('web'),
            ])->columns(1),

            Forms\Components\Section::make('Permissions')
                ->description('Select every permission this role should have.')
                ->schema($sections)
                ->collapsible(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => ucwords(str_replace('_', ' ', $state))),

                Tables\Columns\TextColumn::make('permissions_count')
                    ->label('Permissions')
                    ->counts('permissions')
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('users_count')
                    ->label('Users')
                    ->counts('users')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->since()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (Role $record) => $record->name !== 'super_admin'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->where('name', '!=', 'super_admin');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit'   => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
