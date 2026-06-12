<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Models\ProductVariant;
use App\Models\ProductVariantOption;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('generate_combinations')
                ->label('Generate Combinations')
                ->icon('heroicon-o-squares-plus')
                ->color('info')
                ->modalHeading('Generate Variant Combinations')
                ->modalDescription('Define option groups and all combinations will be created automatically.')
                ->modalSubmitActionLabel('Generate')
                ->form([
                    Forms\Components\Repeater::make('option_groups')
                        ->label('Option Groups')
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->label('Option Name')
                                ->placeholder('Size')
                                ->required(),
                            Forms\Components\TextInput::make('values')
                                ->label('Values (comma-separated)')
                                ->placeholder('S, M, L, XL')
                                ->required()
                                ->helperText('Separate each value with a comma.'),
                        ])
                        ->columns(2)
                        ->minItems(1)
                        ->defaultItems(2)
                        ->addActionLabel('Add Option Group'),

                    Forms\Components\Toggle::make('clear_existing')
                        ->label('Remove existing variants before generating')
                        ->default(false)
                        ->helperText('If off, new combinations are appended to existing variants.'),
                ])
                ->action(function (array $data): void {
                    $product = $this->record;

                    // Parse option groups into [ ['name' => 'Size', 'values' => ['S','M','L']], ... ]
                    $groups = collect($data['option_groups'])
                        ->map(fn ($g) => [
                            'name'   => trim($g['name']),
                            'values' => array_map('trim', explode(',', $g['values'])),
                        ])
                        ->filter(fn ($g) => $g['name'] !== '' && count($g['values']) > 0)
                        ->values()
                        ->toArray();

                    if (empty($groups)) {
                        Notification::make()->title('No valid option groups defined.')->warning()->send();
                        return;
                    }

                    // Cartesian product
                    $combinations = [[]];
                    foreach ($groups as $group) {
                        $append = [];
                        foreach ($combinations as $combo) {
                            foreach ($group['values'] as $value) {
                                $append[] = array_merge($combo, [['name' => $group['name'], 'value' => $value]]);
                            }
                        }
                        $combinations = $append;
                    }

                    DB::transaction(function () use ($product, $combinations, $data): void {
                        if ($data['clear_existing']) {
                            $product->variants()->each(function (ProductVariant $v): void {
                                $v->options()->delete();
                                $v->delete();
                            });
                        }

                        $sort = $product->variants()->max('sort_order') ?? 0;

                        foreach ($combinations as $combo) {
                            $variant = ProductVariant::create([
                                'product_id'     => $product->id,
                                'sku'            => null,
                                'price_modifier' => 0,
                                'stock'          => 0,
                                'sort_order'     => ++$sort,
                                'is_active'      => true,
                            ]);

                            foreach ($combo as $opt) {
                                ProductVariantOption::create([
                                    'variant_id'   => $variant->id,
                                    'option_name'  => $opt['name'],
                                    'option_value' => $opt['value'],
                                ]);
                            }
                        }
                    });

                    $count = count($combinations);
                    Notification::make()
                        ->title("{$count} variant combination" . ($count === 1 ? '' : 's') . ' generated.')
                        ->success()
                        ->send();

                    $this->refreshFormData(['variants']);
                }),

            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $data = $this->form->getRawState();

        $enData = [
            'name'              => $data['translation_name'] ?? '',
            'slug'              => $data['translation_slug'] ?? \Str::slug($data['translation_name'] ?? ''),
            'short_description' => $data['translation_short_description'] ?? null,
            'description'       => $data['translation_description'] ?? null,
            'meta_title'        => $data['translation_meta_title'] ?? null,
            'meta_description'  => $data['translation_meta_description'] ?? null,
            'meta_keywords'     => $data['translation_meta_keywords'] ?? null,
        ];

        $this->record->translations()->updateOrCreate(['locale' => 'en'], $enData);
        $this->record->translations()->updateOrCreate(['locale' => 'bn'], $enData);
    }
}
