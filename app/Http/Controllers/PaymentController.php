<?php

namespace App\Http\Controllers;

use App\Mail\OrderConfirmation;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\PaymentTransaction;
use App\Models\Setting;
use App\Services\SslCommerzService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class PaymentController extends Controller
{
    /**
     * Initiate SSLCommerz payment for a pending order.
     * Called after placeOrder() stores the order_id in session and redirects here.
     */
    public function initiate(Request $request)
    {
        $orderId = session('sslcommerz_order_id');

        if (! $orderId) {
            return redirect()->route('checkout.index')
                ->with('checkout_error', __('front.checkout_error'));
        }

        $order = Order::where('id', $orderId)
            ->where('user_id', auth()->id())
            ->where('payment_method', Order::METHOD_SSLCOMMERZ)
            ->firstOrFail();

        $ssl = new SslCommerzService;

        $payload = [
            'total_amount' => number_format((float) $order->total_amount, 2, '.', ''),
            'currency' => 'BDT',
            'tran_id' => $order->order_number,
            'success_url' => route('payment.success'),
            'fail_url' => route('payment.fail'),
            'cancel_url' => route('payment.cancel'),

            // Customer info
            'cus_name' => $order->ship_name,
            'cus_email' => $this->gatewayEmail($order),
            'cus_add1' => $order->ship_address,
            'cus_city' => $order->ship_city ?: $order->ship_district,
            'cus_state' => $order->ship_district,
            'cus_postcode' => $order->ship_zip ?: '0000',
            'cus_country' => 'Bangladesh',
            'cus_phone' => $order->ship_phone,

            // Shipping info
            'ship_name' => $order->ship_name,
            'ship_add1' => $order->ship_address,
            'ship_city' => $order->ship_city ?: $order->ship_district,
            'ship_state' => $order->ship_district,
            'ship_postcode' => $order->ship_zip ?: '0000',
            'ship_country' => 'Bangladesh',

            // Product info (summary)
            'product_name' => config('app.name').' Order '.$order->order_number,
            'product_category' => 'General',
            'product_profile' => 'general',
        ];

        $response = $ssl->initiate($payload);

        if (($response['status'] ?? '') !== 'SUCCESS' || empty($response['GatewayPageURL'])) {
            $order->update(['payment_status' => Order::PAYMENT_FAILED]);

            return redirect()->route('checkout.index')
                ->with('checkout_error', __('front.sslcommerz_initiate_failed'));
        }

        // Store transaction record
        PaymentTransaction::create([
            'order_id' => $order->id,
            'gateway' => PaymentTransaction::GATEWAY_SSLCOMMERZ,
            'tran_id' => $order->order_number,
            'amount' => $order->total_amount,
            'currency' => 'BDT',
            'status' => PaymentTransaction::STATUS_PENDING,
        ]);

        return redirect()->away($response['GatewayPageURL']);
    }

    /**
     * Retry payment for an existing failed/cancelled SSLCommerz order.
     * The order already exists — just re-initiate with SSLCommerz.
     */
    public function retry(Order $order)
    {
        // Only the order owner can retry
        abort_if($order->user_id !== auth()->id(), 403);
        abort_if($order->payment_method !== Order::METHOD_SSLCOMMERZ, 403);
        abort_if($order->payment_status === Order::PAYMENT_PAID, 403);

        $ssl = new SslCommerzService;

        $payload = [
            'total_amount' => number_format((float) $order->total_amount, 2, '.', ''),
            'currency' => 'BDT',
            'tran_id' => $order->order_number,
            'success_url' => route('payment.success'),
            'fail_url' => route('payment.fail'),
            'cancel_url' => route('payment.cancel'),

            'cus_name' => $order->ship_name,
            'cus_email' => $this->gatewayEmail($order),
            'cus_add1' => $order->ship_address,
            'cus_city' => $order->ship_city ?: $order->ship_district,
            'cus_state' => $order->ship_district,
            'cus_postcode' => $order->ship_zip ?: '0000',
            'cus_country' => 'Bangladesh',
            'cus_phone' => $order->ship_phone,

            'ship_name' => $order->ship_name,
            'ship_add1' => $order->ship_address,
            'ship_city' => $order->ship_city ?: $order->ship_district,
            'ship_state' => $order->ship_district,
            'ship_postcode' => $order->ship_zip ?: '0000',
            'ship_country' => 'Bangladesh',

            'product_name' => config('app.name').' Order '.$order->order_number,
            'product_category' => 'General',
            'product_profile' => 'general',
        ];

        $response = $ssl->initiate($payload);

        if (($response['status'] ?? '') !== 'SUCCESS' || empty($response['GatewayPageURL'])) {
            return redirect()->route('payment.result', $order->order_number)
                ->with('checkout_error', __('front.sslcommerz_initiate_failed'));
        }

        // Reset payment status to unpaid so success callback can update it
        $order->update(['payment_status' => Order::PAYMENT_UNPAID]);

        // Upsert transaction record
        PaymentTransaction::updateOrCreate(
            ['order_id' => $order->id, 'gateway' => PaymentTransaction::GATEWAY_SSLCOMMERZ],
            [
                'tran_id' => $order->order_number,
                'amount' => $order->total_amount,
                'currency' => 'BDT',
                'status' => PaymentTransaction::STATUS_PENDING,
            ]
        );

        return redirect()->away($response['GatewayPageURL']);
    }

    /**
     * SSLCommerz POSTs here after successful payment.
     * No user session — process and redirect to public result page.
     */
    public function success(Request $request)
    {
        $tranId = $request->input('tran_id');
        $valId = $request->input('val_id');

        $order = Order::where('order_number', $tranId)
            ->where('payment_method', Order::METHOD_SSLCOMMERZ)
            ->first();

        if (! $order) {
            return redirect()->route('home');
        }

        // Already processed (duplicate callback)
        if ($order->payment_status === Order::PAYMENT_PAID) {
            return redirect()->route('payment.result', $order->order_number);
        }

        // Re-validate with SSLCommerz
        $ssl = new SslCommerzService;
        $validation = $ssl->validate($valId);

        if (($validation['status'] ?? '') !== 'VALID' && ($validation['status'] ?? '') !== 'VALIDATED') {
            $this->handleFailure($order, 'Payment validation failed.');

            return redirect()->route('payment.result', $order->order_number);
        }

        // Confirm payment
        $order->update([
            'payment_status' => Order::PAYMENT_PAID,
            'status' => Order::STATUS_PROCESSING,
        ]);

        PaymentTransaction::where('order_id', $order->id)
            ->where('gateway', PaymentTransaction::GATEWAY_SSLCOMMERZ)
            ->update([
                'status' => PaymentTransaction::STATUS_SUCCESS,
                'val_id' => $valId,
                'bank_tran_id' => $request->input('bank_tran_id'),
                'card_type' => $request->input('card_type'),
                'raw_response' => $request->all(),
            ]);

        OrderStatusHistory::create([
            'order_id' => $order->id,
            'status' => Order::STATUS_PROCESSING,
            'notes' => 'Payment confirmed via SSLCommerz.',
            'changed_by' => $order->user_id,
            'created_at' => now(),
        ]);

        try {
            $order->load(['items', 'user']);
            if ($order->user?->hasEmail()) {
                Mail::to($order->user->email)->queue(new OrderConfirmation($order));
            }
        } catch (\Throwable) {
        }

        return redirect()->route('payment.result', $order->order_number);
    }

    /**
     * SSLCommerz POSTs here on payment failure.
     */
    public function fail(Request $request)
    {
        $order = Order::where('order_number', $request->input('tran_id'))
            ->where('payment_method', Order::METHOD_SSLCOMMERZ)
            ->first();

        if ($order) {
            $this->handleFailure($order, 'Payment failed.');
        }

        $orderNumber = $order?->order_number ?? '';

        return $orderNumber
            ? redirect()->route('payment.result', $orderNumber)
            : redirect()->route('home');
    }

    /**
     * SSLCommerz POSTs here when user cancels.
     */
    public function cancel(Request $request)
    {
        $order = Order::where('order_number', $request->input('tran_id'))
            ->where('payment_method', Order::METHOD_SSLCOMMERZ)
            ->first();

        if ($order) {
            $order->update(['payment_status' => Order::PAYMENT_FAILED]);
            PaymentTransaction::where('order_id', $order->id)
                ->where('gateway', PaymentTransaction::GATEWAY_SSLCOMMERZ)
                ->update(['status' => PaymentTransaction::STATUS_CANCELLED]);
        }

        $orderNumber = $order?->order_number ?? '';

        return $orderNumber
            ? redirect()->route('payment.result', $orderNumber)
            : redirect()->route('home');
    }

    /**
     * Result page — shown after SSLCommerz redirects back.
     * Requires either authenticated owner or a matching sslcommerz_order_id session
     * (set on checkout before the gateway redirect, cleared after display).
     */
    public function result(string $orderNumber)
    {
        $order = Order::where('order_number', $orderNumber)
            ->where('payment_method', Order::METHOD_SSLCOMMERZ)
            ->with(['items.product', 'items.variant'])
            ->firstOrFail();

        $isOwner = auth()->check() && auth()->id() === $order->user_id;
        $hasSession = session('sslcommerz_order_id') === $order->id;

        abort_unless($isOwner || $hasSession, 403);

        $newCredentials = session()->pull(CheckoutController::NEW_CREDENTIALS_KEY);

        return view('payment.result', compact('order', 'newCredentials'));
    }

    private function handleFailure(Order $order, string $reason): void
    {
        $order->update(['payment_status' => Order::PAYMENT_FAILED]);

        PaymentTransaction::where('order_id', $order->id)
            ->where('gateway', PaymentTransaction::GATEWAY_SSLCOMMERZ)
            ->update(['status' => PaymentTransaction::STATUS_FAILED]);
    }

    /**
     * SSLCommerz rejects requests without a customer email, but email is now
     * optional at checkout — fall back to the store's own contact address.
     */
    private function gatewayEmail(Order $order): string
    {
        return $order->user?->email
            ?: (Setting::get('contact_email', '') ?: 'noreply@'.request()->getHost());
    }
}
