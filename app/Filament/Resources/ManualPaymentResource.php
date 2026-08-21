<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasResourcePermissions;
use App\Filament\Resources\ManualPaymentResource\Pages;
use App\Mail\PaymentRejected;
use App\Mail\PaymentVerified;
use App\Models\ManualPayment;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Mail;

class ManualPaymentResource extends Resource
{
    use HasResourcePermissions;

    protected static string $viewPermission = 'view_payments';

    protected static string $editPermission = 'verify_payments';

    protected static string $createPermission = '';

    protected static string $deletePermission = '';

    protected static ?string $model = ManualPayment::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Commerce';

    protected static ?string $navigationLabel = 'Manual Payments';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        return (string) ManualPayment::where('status', 'pending')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->schema([
                Forms\Components\Select::make('order_id')
                    ->label('Order')
                    ->options(Order::pluck('order_number', 'id'))
                    ->searchable()->required(),

                Forms\Components\Select::make('method')
                    ->options(['bkash' => 'bKash', 'nagad' => 'Nagad'])
                    ->required(),

                Forms\Components\TextInput::make('sender_number')
                    ->label('Sender Number')->maxLength(20)->required(),

                Forms\Components\TextInput::make('transaction_id')
                    ->label('Transaction ID (TrxID)')->maxLength(100)->required(),

                Forms\Components\TextInput::make('amount')
                    ->numeric()->prefix('৳')->required(),

                Forms\Components\FileUpload::make('screenshot_path')
                    ->label('Payment Screenshot')->image()
                    ->directory('payment-screenshots')->nullable(),
            ])->columns(2),
        ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Payment Details')->schema([
                Infolists\Components\TextEntry::make('order.order_number')->label('Order #')->copyable(),
                Infolists\Components\TextEntry::make('method')->badge()
                    ->color(fn ($state) => $state === 'bkash' ? 'success' : 'warning'),
                Infolists\Components\TextEntry::make('sender_number'),
                Infolists\Components\TextEntry::make('transaction_id')->label('TrxID')->copyable(),
                Infolists\Components\TextEntry::make('amount')->money('BDT'),
                Infolists\Components\TextEntry::make('status')->badge()
                    ->color(fn ($state) => match ($state) {
                        'verified' => 'success',
                        'rejected' => 'danger',
                        default => 'warning',
                    }),
                Infolists\Components\TextEntry::make('created_at')->dateTime()->label('Submitted'),
            ])->columns(3),

            Infolists\Components\Section::make('Screenshot')
                ->schema([
                    Infolists\Components\ImageEntry::make('screenshot_path')
                        ->label('')->height(300)->visible(fn ($record) => $record->screenshot_path),
                ])
                ->visible(fn ($record) => $record->screenshot_path),

            Infolists\Components\Section::make('Verification')->schema([
                Infolists\Components\TextEntry::make('verifiedBy.name')->label('Verified By')->placeholder('—'),
                Infolists\Components\TextEntry::make('verified_at')->dateTime()->placeholder('—'),
                Infolists\Components\TextEntry::make('rejection_reason')->placeholder('—'),
            ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order.order_number')
                    ->label('Order #')->searchable()->sortable()->copyable(),

                Tables\Columns\BadgeColumn::make('method')
                    ->colors(['success' => 'bkash', 'warning' => 'nagad'])
                    ->formatStateUsing(fn ($state) => strtoupper($state)),

                Tables\Columns\TextColumn::make('sender_number')->label('Sender'),

                Tables\Columns\TextColumn::make('transaction_id')
                    ->label('TrxID')->searchable()->copyable(),

                Tables\Columns\TextColumn::make('amount')->money('BDT'),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'verified',
                        'danger' => 'rejected',
                    ]),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()->label('Submitted')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'verified' => 'Verified',
                        'rejected' => 'Rejected',
                    ]),
                Tables\Filters\SelectFilter::make('method')
                    ->options(['bkash' => 'bKash', 'nagad' => 'Nagad']),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\Action::make('verify')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->label('Verify')
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'verified',
                            'verified_by' => auth()->id(),
                            'verified_at' => now(),
                        ]);
                        $record->order->update([
                            'payment_status' => 'paid',
                            'status' => 'processing',
                        ]);
                        try {
                            $order = $record->order->load(['items', 'user']);
                            if ($order->user?->hasEmail()) {
                                Mail::to($order->user->email)->queue(new PaymentVerified($order));
                            }
                        } catch (\Throwable) {
                        }
                        Notification::make()->title('Payment verified & order moved to Processing')->success()->send();
                    }),

                Tables\Actions\Action::make('reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->label('Reject')
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Reason for rejection')->required()->rows(2),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status' => 'rejected',
                            'verified_by' => auth()->id(),
                            'verified_at' => now(),
                            'rejection_reason' => $data['rejection_reason'],
                        ]);
                        $record->order->update(['payment_status' => 'failed']);
                        try {
                            $order = $record->order->load(['items', 'user']);
                            if ($order->user?->hasEmail()) {
                                Mail::to($order->user->email)->queue(new PaymentRejected($order, $record));
                            }
                        } catch (\Throwable) {
                        }
                        Notification::make()->title('Payment rejected')->danger()->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListManualPayments::route('/'),
            'view' => Pages\ViewManualPayment::route('/{record}'),
        ];
    }
}
