@extends('emails.layout')

@section('content')
<p class="greeting">Hi {{ $order->user->name }},</p>
<p class="lead">Your order is on its way! Here are the shipping details for order <strong>{{ $order->order_number }}</strong>.</p>

<div class="card">
    <div class="card-row">
        <span class="label">Order Number</span>
        <span class="value">{{ $order->order_number }}</span>
    </div>
    <div class="card-row">
        <span class="label">Status</span>
        <span class="value"><span class="badge badge-info">Shipped</span></span>
    </div>
    @if($order->tracking_number)
    <div class="card-row">
        <span class="label">Tracking Number</span>
        <span class="value" style="font-family:monospace;letter-spacing:.5px;">{{ $order->tracking_number }}</span>
    </div>
    @endif
    <div class="card-row">
        <span class="label">Shipping To</span>
        <span class="value">{{ $order->ship_district }}@if($order->ship_city), {{ $order->ship_city }}@endif</span>
    </div>
</div>

<p class="section-title" style="margin-top:24px;">Items Shipped</p>
<table class="table">
    <thead>
        <tr>
            <th>Product</th>
            <th style="text-align:right">Qty</th>
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
        </tr>
        @endforeach
    </tbody>
</table>

<div style="text-align:center;margin-top:24px;">
    <a href="{{ route('orders.show', $order) }}" class="btn">Track My Order</a>
</div>

<hr class="divider">
<p class="help-text">Estimated delivery: please allow a few business days depending on your location. Thank you for your patience!</p>
@endsection
