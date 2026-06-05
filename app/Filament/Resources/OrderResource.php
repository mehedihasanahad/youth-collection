<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasResourcePermissions;
use App\Filament\Resources\OrderResource\Pages;
use App\Jobs\DispatchCourierOrderJob;
use App\Models\Order;
use App\Models\PathaoDistrictMapping;
use App\Services\CourierManager;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Support\HtmlString;

class OrderResource extends Resource
{
    use HasResourcePermissions;

    protected static string $viewPermission   = 'view_orders';
    protected static string $editPermission   = 'update_order_status';
    protected static string $createPermission = '';
    protected static string $deletePermission = 'cancel_orders';

    protected static ?string $model = Order::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $navigationGroup = 'Commerce';
    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return (string) Order::where('status', 'pending')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Order Status')->schema([
                Forms\Components\Select::make('status')
                    ->options([
                        'pending'    => 'Pending',
                        'processing' => 'Processing',
                        'shipped'    => 'Shipped',
                        'delivered'  => 'Delivered',
                        'cancelled'  => 'Cancelled',
                        'returned'   => 'Returned',
                    ])->required(),

                Forms\Components\Select::make('payment_status')
                    ->options([
                        'unpaid'               => 'Unpaid',
                        'pending_verification' => 'Pending Verification',
                        'paid'                 => 'Paid',
                        'refunded'             => 'Refunded',
                        'failed'               => 'Failed',
                    ])->required(),

                Forms\Components\TextInput::make('tracking_number')->maxLength(100)->nullable(),
            ])->columns(3),
        ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Order Details')->schema([
                Infolists\Components\TextEntry::make('order_number')->label('Order #')->copyable(),
                Infolists\Components\TextEntry::make('status')->badge()
                    ->color(fn ($state) => match ($state) {
                        'pending'    => 'warning',
                        'processing' => 'info',
                        'shipped'    => 'primary',
                        'delivered'  => 'success',
                        'cancelled', 'returned' => 'danger',
                        default      => 'gray',
                    }),
                Infolists\Components\TextEntry::make('payment_status')->badge()
                    ->color(fn ($state) => match ($state) {
                        'paid'      => 'success',
                        'unpaid'    => 'danger',
                        'refunded'  => 'info',
                        default     => 'warning',
                    }),
                Infolists\Components\TextEntry::make('payment_method'),
                Infolists\Components\TextEntry::make('total_amount')->money('BDT'),
                Infolists\Components\TextEntry::make('created_at')->dateTime(),
            ])->columns(3),

            Infolists\Components\Section::make('Customer')->schema([
                Infolists\Components\TextEntry::make('user.name')->label('Name'),
                Infolists\Components\TextEntry::make('user.email')->label('Email'),
            ])->columns(2),

            Infolists\Components\Section::make('Shipping Address')->schema([
                Infolists\Components\TextEntry::make('ship_name'),
                Infolists\Components\TextEntry::make('ship_phone'),
                Infolists\Components\TextEntry::make('ship_address'),
                Infolists\Components\TextEntry::make('ship_city'),
                Infolists\Components\TextEntry::make('ship_district'),
            ])->columns(3),

            Infolists\Components\Section::make('Order Items')->schema([
                Infolists\Components\RepeatableEntry::make('items')
                    ->label('')
                    ->schema([
                        Infolists\Components\ImageEntry::make('product.primaryImage.path')
                            ->label('')
                            ->disk('public')
                            ->height(56)
                            ->width(56)
                            ->extraImgAttributes(['style' => 'border-radius:8px; object-fit:cover;']),

                        Infolists\Components\TextEntry::make('product_name')
                            ->label('Product')
                            ->weight('semibold')
                            ->columnSpan(2),

                        Infolists\Components\TextEntry::make('variant_label')
                            ->label('Variant')
                            ->html()
                            ->placeholder('—')
                            ->formatStateUsing(function (?string $state): string {
                                if (! $state) {
                                    return '';
                                }
                                $parts = array_map('trim', explode('/', $state));
                                $html  = [];
                                foreach ($parts as $part) {
                                    $sep = strpos($part, ': ');
                                    if ($sep !== false) {
                                        $key = substr($part, 0, $sep);
                                        $val = substr($part, $sep + 2);
                                        if (in_array(strtolower(trim($key)), ['color', 'colour'])) {
                                            $html[] = e($key) . ': <span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:' . e(trim($val)) . ';border:1px solid #d1d5db;vertical-align:middle;margin-left:2px;"></span>';
                                        } else {
                                            $html[] = e($key) . ': ' . e($val);
                                        }
                                    } else {
                                        $html[] = e($part);
                                    }
                                }
                                return implode('<span style="color:#d1d5db"> / </span>', $html);
                            })
                            ->columnSpan(2),

                        Infolists\Components\TextEntry::make('quantity')
                            ->label('Qty')
                            ->badge()
                            ->color('info'),

                        Infolists\Components\TextEntry::make('unit_price')
                            ->label('Unit Price')
                            ->money('BDT'),

                        Infolists\Components\TextEntry::make('subtotal')
                            ->label('Subtotal')
                            ->money('BDT')
                            ->weight('bold')
                            ->color('primary'),
                    ])
                    ->columns(8)
                    ->contained(false),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->searchable()->sortable()->copyable()->weight('bold'),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Customer')->searchable(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->money('BDT')->sortable(),

                Tables\Columns\TextColumn::make('status')->badge()
                    ->color(fn ($state) => match ($state) {
                        'pending'    => 'warning',
                        'processing' => 'info',
                        'shipped'    => 'primary',
                        'delivered'  => 'success',
                        'cancelled', 'returned' => 'danger',
                        default      => 'gray',
                    }),

                Tables\Columns\TextColumn::make('payment_status')->badge()
                    ->color(fn ($state) => match ($state) {
                        'paid'      => 'success',
                        'unpaid', 'failed' => 'danger',
                        'pending_verification' => 'warning',
                        'refunded'  => 'info',
                        default     => 'gray',
                    }),

                Tables\Columns\TextColumn::make('payment_method'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending'    => 'Pending',
                        'processing' => 'Processing',
                        'shipped'    => 'Shipped',
                        'delivered'  => 'Delivered',
                        'cancelled'  => 'Cancelled',
                        'returned'   => 'Returned',
                    ]),
                Tables\Filters\SelectFilter::make('payment_status')
                    ->options([
                        'unpaid'               => 'Unpaid',
                        'pending_verification' => 'Pending Verification',
                        'paid'                 => 'Paid',
                        'refunded'             => 'Refunded',
                        'failed'               => 'Failed',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('invoice')
                    ->label('Invoice')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('warning')
                    ->action(function (Order $record) {
                        $record->load(['items', 'manualPayment', 'coupon']);
                        $pdf = Pdf::loadView('orders.invoice', ['order' => $record])->setPaper('a4', 'portrait');
                        return response()->streamDownload(
                            fn () => print($pdf->output()),
                            'Invoice-' . $record->order_number . '.pdf',
                            ['Content-Type' => 'application/pdf']
                        );
                    }),

                Tables\Actions\Action::make('dispatch_courier')
                    ->label(fn (Order $record) => $record->courierOrder ? 'Courier: ' . ucfirst($record->courierOrder->status) : 'Send to Courier')
                    ->icon('heroicon-o-truck')
                    ->color(fn (Order $record) => match (true) {
                        $record->courierOrder?->isFailed()     => 'danger',
                        $record->courierOrder?->isDispatched() => 'success',
                        (bool) $record->courierOrder           => 'warning',
                        default                                => 'gray',
                    })
                    ->form([
                        Forms\Components\Select::make('courier')
                            ->label('Courier Provider')
                            ->options(fn () => app(CourierManager::class)->available())
                            ->required()
                            ->default('steadfast')
                            ->live(),
                        Forms\Components\Placeholder::make('order_info')
                            ->label('Order')
                            ->content(fn (Order $record) => "{$record->order_number} — {$record->ship_name} ({$record->ship_phone})"),
                        Forms\Components\Placeholder::make('cod_info')
                            ->label('COD Amount')
                            ->content(fn (Order $record) => $record->payment_method === Order::METHOD_COD
                                ? '৳' . number_format((float) $record->total_amount, 2)
                                : 'No COD (prepaid)'),
                        Forms\Components\Section::make('Pathao — District Mapping')
                            ->description('The customer\'s district will be auto-mapped to Pathao city/zone IDs.')
                            ->visible(fn (Forms\Get $get) => $get('courier') === 'pathao')
                            ->schema([
                                Forms\Components\Placeholder::make('district_info')
                                    ->label('Customer District')
                                    ->content(fn (Order $record) => $record->ship_district ?: '—'),
                                Forms\Components\Placeholder::make('mapping_status')
                                    ->label('Mapping Status')
                                    ->content(function (Order $record) {
                                        $mapping = PathaoDistrictMapping::findByDistrict($record->ship_district ?? '');
                                        if ($mapping) {
                                            return new HtmlString(
                                                '<span class="text-success-600 font-medium">'
                                                . '&#10003; Mapped — City ID: ' . $mapping->pathao_city_id
                                                . ', Zone ID: ' . $mapping->pathao_zone_id
                                                . ($mapping->pathao_area_id ? ', Area ID: ' . $mapping->pathao_area_id : '')
                                                . '</span>'
                                            );
                                        }
                                        return new HtmlString(
                                            '<span class="text-danger-600 font-medium">'
                                            . '&#10007; No mapping for &quot;' . htmlspecialchars($record->ship_district ?? '') . '&quot;'
                                            . ' — add it in Courier &rarr; Pathao District Mappings.'
                                            . '</span>'
                                        );
                                    }),
                            ])->columns(2),
                    ])
                    ->modalHeading('Send Order to Courier')
                    ->modalSubmitActionLabel('Dispatch')
                    ->action(function (Order $record, array $data): void {
                        if ($data['courier'] === 'pathao') {
                            $mapping = PathaoDistrictMapping::findByDistrict($record->ship_district ?? '');
                            if ($mapping === null) {
                                Notification::make()
                                    ->title('No district mapping')
                                    ->body('District "' . ($record->ship_district ?? '') . '" is not mapped to Pathao IDs. Add it in Courier → Pathao District Mappings.')
                                    ->danger()
                                    ->send();
                                return;
                            }
                        }

                        DispatchCourierOrderJob::dispatch($record->id, $data['courier']);

                        Notification::make()
                            ->title('Courier dispatch queued')
                            ->body("Order {$record->order_number} queued for {$data['courier']}.")
                            ->success()
                            ->send();
                    })
                    ->visible(fn (Order $record) =>
                        ! $record->courierOrder?->isDispatched()
                        || $record->courierOrder?->isFailed()
                    ),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListOrders::route('/'),
            'view'   => Pages\ViewOrder::route('/{record}'),
            'edit'   => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
