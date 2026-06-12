<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasResourcePermissions;
use App\Filament\Resources\ProductResource\Pages;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductTranslation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\Str;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    use HasResourcePermissions;

    protected static string $viewPermission   = 'view_products';
    protected static string $createPermission = 'create_products';
    protected static string $editPermission   = 'edit_products';
    protected static string $deletePermission = 'delete_products';

    protected static ?string $model = Product::class;
    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationGroup = 'Catalog';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Basic Info')->schema([
                Forms\Components\TextInput::make('sku')
                    ->label('SKU')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(100),

                Forms\Components\Select::make('category_id')
                    ->label('Category')
                    ->options(fn () => Category::active()->with('translations')
                        ->get()->mapWithKeys(fn ($c) => [$c->id => $c->getTranslation('en')?->name ?? 'Category #' . $c->id]))
                    ->searchable()
                    ->required(),
            ])->columns(2),

            Forms\Components\Section::make('Name & Description')->schema([
                Forms\Components\TextInput::make('translation_name')
                    ->label('Name')
                    ->required()
                    ->maxLength(191)
                    ->dehydrated(false)
                    ->live(onBlur: true)
                    ->afterStateHydrated(function ($state, $record, $set) {
                        $set('translation_name', $record?->getTranslation('en')?->name);
                    })
                    ->afterStateUpdated(function (string $operation, ?string $state, Set $set): void {
                        if ($operation === 'create') {
                            $set('translation_slug', Str::slug($state ?? ''));
                        }
                    }),

                Forms\Components\TextInput::make('translation_slug')
                    ->label('Slug')
                    ->maxLength(191)
                    ->required()
                    ->dehydrated(false)
                    ->afterStateHydrated(function ($state, $record, $set) {
                        $set('translation_slug', $record?->getTranslation('en')?->slug);
                    }),

                Forms\Components\Textarea::make('translation_short_description')
                    ->label('Short Description')
                    ->rows(2)
                    ->dehydrated(false)
                    ->afterStateHydrated(function ($state, $record, $set) {
                        $set('translation_short_description', $record?->getTranslation('en')?->short_description);
                    }),

                Forms\Components\RichEditor::make('translation_description')
                    ->label('Full Description')
                    ->columnSpanFull()
                    ->dehydrated(false)
                    ->afterStateHydrated(function ($state, $record, $set) {
                        $set('translation_description', $record?->getTranslation('en')?->description);
                    }),
            ])->columns(2),

            Forms\Components\Section::make('Pricing & Stock')->schema([
                Forms\Components\TextInput::make('price')
                    ->numeric()->prefix('৳')->required(),
                Forms\Components\TextInput::make('sale_price')
                    ->label('Discount Price')
                    ->numeric()->prefix('৳')->nullable()->minValue(1),
                Forms\Components\TextInput::make('stock')
                    ->numeric()->integer()->required()->default(0),
                Forms\Components\TextInput::make('low_stock_threshold')
                    ->numeric()->integer()->default(5),
                Forms\Components\TextInput::make('weight')
                    ->numeric()->suffix('kg')->nullable(),
            ])->columns(5),

            Forms\Components\Section::make('Status')->schema([
                Forms\Components\Toggle::make('is_active')->default(true)->inline(false),
                Forms\Components\Toggle::make('is_featured')->default(false)->inline(false),
                Forms\Components\TextInput::make('sort_order')->numeric()->integer()->default(0),
            ])->columns(3),

            Forms\Components\Section::make('Product Images')
                ->description('First image marked as primary will be used as the main thumbnail. Recommended: square images (1:1 ratio), minimum 800×800px.')
                ->schema([
                    Forms\Components\Repeater::make('images')
                        ->relationship('images')
                        ->schema([
                            Forms\Components\FileUpload::make('path')
                                ->label('Image')
                                ->image()
                                ->directory('products')
                                ->imageEditor()
                                ->imagePreviewHeight('120')
                                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                ->maxSize(2048)
                                ->required(),

                            Forms\Components\TextInput::make('alt_text')
                                ->label('Alt Text')
                                ->maxLength(191)
                                ->nullable()
                                ->placeholder('Describe the image for SEO'),

                            Forms\Components\TextInput::make('sort_order')
                                ->label('Order')
                                ->numeric()->integer()->default(0)->minValue(0),

                            Forms\Components\Toggle::make('is_primary')
                                ->label('Primary Image')
                                ->default(false)
                                ->inline(false)
                                ->helperText('Shown as the thumbnail in listings'),
                        ])
                        ->columns(4)
                        ->addActionLabel('Add Image')
                        ->reorderable('sort_order')
                        ->collapsible()
                        ->defaultItems(0),
                ]),

            Forms\Components\Section::make('Variants')
                ->description('Add size, colour, or any other variants. Each variant has its own SKU, price modifier, stock and named options.')
                ->schema([
                    Forms\Components\Toggle::make('custom_size_enabled')
                        ->label('Custom Size Enabled')
                        ->helperText('When on, a "Custom Size" button appears in the Size variant group on the product page — customers enter their own measurements.')
                        ->default(false)
                        ->inline(false),

                    Forms\Components\Repeater::make('variants')
                        ->relationship('variants')
                        ->schema([
                            Forms\Components\Section::make()
                                ->extraAttributes(['style' => 'border-left: 4px solid #cb7888; background-color: #fdf4f6; border-radius: 0 8px 8px 0;'])
                                ->schema([
                                    Forms\Components\TextInput::make('sku')
                                        ->label('Variant SKU')
                                        ->nullable()
                                        ->maxLength(100)
                                        ->unique('product_variants', 'sku', ignoreRecord: true),

                                    Forms\Components\TextInput::make('price_modifier')
                                        ->label('Price Modifier (৳)')
                                        ->numeric()
                                        ->default(0)
                                        ->prefix('৳')
                                        ->helperText('Added to base price. Use negative for a discount.'),

                                    Forms\Components\TextInput::make('stock')
                                        ->label('Stock')
                                        ->numeric()
                                        ->integer()
                                        ->required()
                                        ->default(0)
                                        ->minValue(0),

                                    Forms\Components\TextInput::make('sort_order')
                                        ->label('Order')
                                        ->numeric()
                                        ->integer()
                                        ->default(0),

                                    Forms\Components\Toggle::make('is_active')
                                        ->label('Active')
                                        ->default(true)
                                        ->inline(false),

                                    Forms\Components\Repeater::make('options')
                                        ->relationship('options')
                                        ->label('Options (e.g. Size: M, Colour: Red)')
                                        ->schema([
                                            Forms\Components\TextInput::make('option_name')
                                                ->label('Name')
                                                ->placeholder('Size')
                                                ->required()
                                                ->maxLength(50)
                                                ->live(onBlur: true),

                                            Forms\Components\ColorPicker::make('option_color_picker')
                                                ->label('Color')
                                                ->required(fn (Get $get): bool => \in_array(
                                                    strtolower((string) ($get('option_name') ?? '')),
                                                    ['color', 'colour'],
                                                ))
                                                ->visible(fn (Get $get): bool => \in_array(
                                                    strtolower((string) ($get('option_name') ?? '')),
                                                    ['color', 'colour'],
                                                ))
                                                ->dehydrated(false)
                                                ->live()
                                                ->afterStateUpdated(fn (?string $state, Set $set) => $set('option_value', $state ?? ''))
                                                ->afterStateHydrated(fn ($state, Get $get, Set $set) => $set('option_color_picker', $get('option_value'))),

                                            Forms\Components\TextInput::make('option_value')
                                                ->label('Value')
                                                ->placeholder('M')
                                                ->required()
                                                ->maxLength(100)
                                                ->hiddenLabel(fn (Get $get): bool =>
                                                    \in_array(strtolower((string) ($get('option_name') ?? '')), ['color', 'colour'])
                                                )
                                                ->extraAttributes(fn (Get $get): array =>
                                                    \in_array(strtolower((string) ($get('option_name') ?? '')), ['color', 'colour'])
                                                        ? ['style' => 'display:none']
                                                        : []
                                                )
                                                ->extraInputAttributes(fn (Get $get): array =>
                                                    \in_array(strtolower((string) ($get('option_name') ?? '')), ['color', 'colour'])
                                                        ? ['type' => 'hidden']
                                                        : []
                                                ),

                                            Forms\Components\Textarea::make('size_note')
                                                ->label('Exact Size Details')
                                                ->placeholder('e.g. Chest: 38–40 in, Length: 28 in')
                                                ->helperText('Shown to customers when they select this size on the product page.')
                                                ->rows(2)
                                                ->maxLength(500)
                                                ->nullable()
                                                ->columnSpanFull()
                                                ->visible(fn (Get $get): bool => strtolower((string) ($get('option_name') ?? '')) === 'size'),
                                        ])
                                        ->columns(2)
                                        ->addActionLabel('Add Option')
                                        ->defaultItems(1)
                                        ->minItems(1)
                                        ->columnSpanFull(),

                                    Forms\Components\Repeater::make('images')
                                        ->relationship('images')
                                        ->label('Variant Images')
                                        ->schema([
                                            Forms\Components\FileUpload::make('path')
                                                ->label('Image')
                                                ->image()
                                                ->directory('products')
                                                ->imageEditor()
                                                ->imagePreviewHeight('100')
                                                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                                ->maxSize(2048)
                                                ->required(),

                                            Forms\Components\TextInput::make('alt_text')
                                                ->label('Alt Text')
                                                ->maxLength(191)
                                                ->nullable()
                                                ->placeholder('Describe the image'),

                                            Forms\Components\TextInput::make('sort_order')
                                                ->label('Order')
                                                ->numeric()->integer()->default(0)->minValue(0),
                                        ])
                                        ->columns(3)
                                        ->addActionLabel('Add Variant Image')
                                        ->collapsible()
                                        ->defaultItems(0)
                                        ->columnSpanFull()
                                        ->mutateRelationshipDataBeforeCreateUsing(function (array $data, $record): array {
                                            $data['product_id'] = $record->product_id;
                                            return $data;
                                        }),
                                ])
                                ->columns(5)
                                ->columnSpanFull(),
                        ])
                        ->columns(1)
                        ->addActionLabel('Add Variant')
                        ->reorderable('sort_order')
                        ->collapsible()
                        ->defaultItems(0)
                        ->itemLabel(function (array $state): string {
                            $options = collect($state['options'] ?? [])
                                ->map(fn ($o) => trim(($o['option_name'] ?? '') . ': ' . ($o['option_value'] ?? '')))
                                ->filter()
                                ->implode(' / ');
                            return $options ?: 'New Variant';
                        }),
                ]),

            Forms\Components\Section::make('SEO')
                ->description('Controls how this product appears in search engines and when shared on social media.')
                ->collapsed()
                ->schema([
                    Forms\Components\TextInput::make('translation_meta_title')
                        ->label('Meta Title')
                        ->maxLength(70)
                        ->dehydrated(false)
                        ->helperText('Recommended: 50–70 characters.')
                        ->afterStateHydrated(function ($state, $record, $set) {
                            $set('translation_meta_title', $record?->getTranslation('en')?->meta_title);
                        })
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('translation_meta_description')
                        ->label('Meta Description')
                        ->rows(2)
                        ->maxLength(160)
                        ->dehydrated(false)
                        ->helperText('Recommended: 120–160 characters.')
                        ->afterStateHydrated(function ($state, $record, $set) {
                            $set('translation_meta_description', $record?->getTranslation('en')?->meta_description);
                        })
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('translation_meta_keywords')
                        ->label('Keywords')
                        ->maxLength(500)
                        ->dehydrated(false)
                        ->helperText('Comma-separated keywords, e.g. hijab, modest fashion, abaya')
                        ->afterStateHydrated(function ($state, $record, $set) {
                            $set('translation_meta_keywords', $record?->getTranslation('en')?->meta_keywords);
                        })
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('primaryImage.path')
                    ->label('Image')
                    ->square()
                    ->defaultImageUrl(fn () => null)
                    ->extraImgAttributes(['class' => 'rounded-lg object-cover']),

                Tables\Columns\TextColumn::make('sku')
                    ->searchable()->sortable()->copyable(),

                Tables\Columns\TextColumn::make('translation.name')
                    ->label('Name')
                    ->getStateUsing(fn ($record) => $record->getTranslation('en')?->name ?? '—')
                    ->searchable(query: fn ($query, $search) =>
                        $query->whereHas('translations', fn ($q) =>
                            $q->where('locale', 'en')->where('name', 'like', "%{$search}%")))
                    ->sortable(query: fn ($query, $direction) =>
                        $query->leftJoin('product_translations as pt_sort', fn ($j) =>
                            $j->on('pt_sort.product_id', '=', 'products.id')->where('pt_sort.locale', 'en'))
                        ->orderBy('pt_sort.name', $direction)),

                Tables\Columns\TextColumn::make('category.translations')
                    ->label('Category')
                    ->getStateUsing(fn ($record) => $record->category?->getTranslation('en')?->name ?? '—'),

                Tables\Columns\TextColumn::make('price')
                    ->money('BDT')->sortable(),

                Tables\Columns\TextColumn::make('sale_price')
                    ->money('BDT')->placeholder('—'),

                Tables\Columns\TextColumn::make('stock')->sortable()
                    ->color(fn ($state) => $state <= 5 ? 'danger' : ($state <= 20 ? 'warning' : 'success')),

                Tables\Columns\IconColumn::make('is_active')->boolean()->label('Active'),
                Tables\Columns\IconColumn::make('is_featured')->boolean()->label('Featured'),

                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Active'),
                Tables\Filters\TernaryFilter::make('is_featured')->label('Featured'),
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Category')
                    ->options(fn () => Category::active()->with('translations')
                        ->get()->mapWithKeys(fn ($c) => [$c->id => $c->getTranslation('en')?->name ?? '#' . $c->id])),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit'   => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
