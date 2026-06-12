<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Mail\OrderCancelled;
use App\Mail\OrderDelivered;
use App\Mail\OrderShipped;
use App\Models\OrderStatusHistory;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Mail;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    // Captured in beforeSave(), used in afterSave()
    protected ?string $statusBeforeSave = null;

    protected function getHeaderActions(): array
    {
        return [Actions\ViewAction::make()];
    }

    protected function beforeSave(): void
    {
        // Read the current DB value before Filament overwrites it
        $this->statusBeforeSave = $this->record->getOriginal('status') ?? $this->record->status;
    }

    protected function afterSave(): void
    {
        $order     = $this->record->fresh(['items']);
        $oldStatus = $this->statusBeforeSave;
        $newStatus = $order->status;

        // Recalculate each item's subtotal then rebuild order totals
        $subtotal = 0;
        foreach ($order->items as $item) {
            $itemSubtotal = round((float) $item->unit_price * (int) $item->quantity, 2);
            $item->updateQuietly(['subtotal' => $itemSubtotal]);
            $subtotal += $itemSubtotal;
        }

        $total = max(0, $subtotal - (float) $order->discount_amount + (float) $order->shipping_amount + (float) $order->tax_amount);
        $order->updateQuietly(['subtotal' => $subtotal, 'total_amount' => $total]);

        if ($oldStatus !== $newStatus) {
            OrderStatusHistory::create([
                'order_id'   => $order->id,
                'status'     => $newStatus,
                'notes'      => 'Status updated by admin.',
                'changed_by' => auth()->id(),
                'created_at' => now(),
            ]);

            try {
                $order->load(['items', 'user']);
                if ($newStatus === 'shipped') {
                    Mail::to($order->user->email)->queue(new OrderShipped($order));
                } elseif ($newStatus === 'delivered') {
                    Mail::to($order->user->email)->queue(new OrderDelivered($order));
                } elseif ($newStatus === 'cancelled') {
                    Mail::to($order->user->email)->queue(new OrderCancelled($order));
                }
            } catch (\Throwable) {
                // Non-fatal
            }
        }
    }
}
