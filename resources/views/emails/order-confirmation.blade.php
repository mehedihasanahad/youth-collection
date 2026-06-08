@extends('emails.layout')

@section('content')
<p class="greeting">Hi {{ $order->user->name }},</p>
<p class="lead">Thank you for your order! We've received it and it's currently being reviewed. Here's a summary:</p>

<div class="card">
    <div class="card-row">
        <span class="label">Order Number</span>
        <span class="value">{{ $order->order_number }}</span>
    </div>
    <div class="card-row">
        <span class="label">Order Date</span>
        <span class="value">{{ $order->created_at->format('d M Y, h:i A') }}</span>
    </div>
    <div class="card-row">
        <span class="label">Payment Method</span>
        <span class="value">
            @if($order->payment_method === 'cod') Cash on Delivery
            @elseif($order->payment_method === 'bkash') bKash
            @elseif($order->payment_method === 'nagad') Nagad
            @elseif($order->payment_method === 'sslcommerz') SSLCommerz (Online Payment)
            @else {{ ucfirst($order->payment_method) }}
            @endif
        </span>
    </div>
    <div class="card-row">
        <span class="label">Payment Status</span>
        <span class="value">
            @if($order->payment_status === 'paid') <span class="badge badge-success">Paid</span>
            @elseif($order->payment_status === 'pending_verification') <span class="badge badge-warning">Pending Verification</span>
            @elseif($order->payment_status === 'unpaid') <span class="badge badge-warning">Unpaid (Pay on Delivery)</span>
            @else <span class="badge badge-info">{{ ucfirst(str_replace('_', ' ', $order->payment_status)) }}</span>
            @endif
        </span>
    </div>
</div>

<p class="section-title" style="margin-top:24px;">Order Items</p>
<table class="table">
    <thead>
        <tr>
            <th>Product</th>
            <th style="text-align:right">Qty</th>
            <th style="text-align:right">Price</th>
        </tr>
    </thead>
    <tbody>
        @foreach($order->items as $item)
        <tr>
            <td>
                {{ $item->product_name }}
                @if($item->variant_label)
                    @php
                        $vlParts = array_map('trim', explode('/', $item->variant_label));
                        $vlHtml  = [];
                        foreach ($vlParts as $vlPart) {
                            $vlSep     = strpos($vlPart, ': ');
                            $vlKey     = $vlSep !== false ? substr($vlPart, 0, $vlSep) : null;
                            $vlVal     = $vlSep !== false ? substr($vlPart, $vlSep + 2) : $vlPart;
                            $vlIsColor = $vlKey && in_array(strtolower(trim($vlKey)), ['color', 'colour']);
                            if ($vlIsColor) {
                                $vlHtml[] = e(trim($vlKey)) . ': <span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:' . e(trim($vlVal)) . ';border:1px solid #d1d5db;vertical-align:middle;margin-left:2px;"></span>';
                            } else {
                                $vlHtml[] = e($vlKey ? trim($vlKey) . ': ' . $vlVal : $vlPart);
                            }
                        }
                    @endphp
                    <br><span style="font-size:12px;color:#9ca3af;">{!! implode('<span style="color:#d1d5db"> / </span>', $vlHtml) !!}</span>
                @endif
            </td>
            <td style="text-align:right">{{ $item->quantity }}</td>
            <td style="text-align:right">৳{{ number_format($item->subtotal, 2) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="card" style="margin-top:16px;">
    <div class="card-row">
        <span class="label">Subtotal</span>
        <span class="value">৳{{ number_format($order->subtotal, 2) }}</span>
    </div>
    @if($order->discount_amount > 0)
    <div class="card-row">
        <span class="label">Discount</span>
        <span class="value" style="color:#dc2626;">−৳{{ number_format($order->discount_amount, 2) }}</span>
    </div>
    @endif
    <div class="card-row">
        <span class="label">Shipping</span>
        <span class="value">{{ $order->shipping_amount > 0 ? '৳'.number_format($order->shipping_amount, 2) : 'Free' }}</span>
    </div>
    <div class="card-row">
        <span class="label">Total</span>
        <span class="value total">৳{{ number_format($order->total_amount, 2) }}</span>
    </div>
</div>

<p class="section-title" style="margin-top:24px;">Shipping To</p>
<div class="card">
    <p style="font-size:14px;line-height:1.9;padding:6px 0;">
        <strong>{{ $order->ship_name }}</strong><br>
        {{ $order->ship_phone }}<br>
        {{ $order->ship_address }}<br>
        {{ $order->ship_city }}, {{ $order->ship_district }}
        @if($order->ship_zip) – {{ $order->ship_zip }}@endif
    </p>
</div>

@if(in_array($order->payment_method, ['bkash', 'nagad']))
<div class="notice notice-warning" style="margin-top:16px;">
    <strong>Note:</strong> Your payment via {{ strtoupper($order->payment_method) }} is under review. We'll confirm and update your order status within 24 hours.
</div>
@endif

<div style="text-align:center;margin-top:24px;">
    <a href="{{ route('orders.show', $order) }}" class="btn">View My Order</a>
</div>

<hr class="divider">
<p class="help-text">If you have any questions, please contact our support team. We're happy to help!</p>
@endsection
