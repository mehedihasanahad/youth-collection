<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function resolveRecord(int | string $key): Order
    {
        return Order::with([
            'items.product.primaryImage',
            'user',
            'coupon',
            'manualPayment',
            'shippingRate',
        ])->findOrFail($key);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),

            Actions\Action::make('invoice')
                ->label('Download Invoice')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('warning')
                ->action(function () {
                    /** @var Order $order */
                    $order = $this->record;
                    $order->load(['items', 'manualPayment', 'coupon']);
                    $pdf = Pdf::loadView('orders.invoice', compact('order'))->setPaper('a4', 'portrait');
                    return response()->streamDownload(
                        fn () => print($pdf->output()),
                        'Invoice-' . $order->order_number . '.pdf',
                        ['Content-Type' => 'application/pdf']
                    );
                }),
        ];
    }
}
