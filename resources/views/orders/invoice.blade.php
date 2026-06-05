<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Invoice #{{ $order->order_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #1f2937;
            background: #ffffff;
            line-height: 1.5;
        }

        .page { padding: 44px 52px; }

        /* ── Header ── */
        .header { margin-bottom: 32px; }
        .header table { width: 100%; border-collapse: collapse; }
        .header table td { vertical-align: top; }

        .logo-img { max-height: 52px; max-width: 180px; }
        .brand-name { font-size: 22px; font-weight: bold; color: #93394b; }
        .brand-tagline { font-size: 10px; color: #9ca3af; margin-top: 2px; letter-spacing: 0.3px; }

        .invoice-badge {
            display: inline-block;
            background: #93394b;
            color: #ffffff;
            font-size: 11px;
            font-weight: bold;
            letter-spacing: 2px;
            padding: 3px 14px;
            border-radius: 20px;
            text-align: right;
            float: right;
            margin-bottom: 6px;
        }
        .invoice-num { font-size: 20px; font-weight: bold; color: #1f2937; text-align: right; }
        .invoice-meta { font-size: 10px; color: #6b7280; text-align: right; margin-top: 3px; }
        .invoice-meta strong { color: #374151; }

        /* ── Divider ── */
        .divider { height: 2px; background: #93394b; margin-bottom: 24px; border-radius: 2px; }
        .divider-light { height: 1px; background: #f3e8eb; margin: 0 0 24px; }

        /* ── Info boxes ── */
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 28px; }
        .info-table td { width: 50%; vertical-align: top; padding: 16px 18px; }
        .info-table .ship-box {
            background: #fdf4f6;
            border: 1px solid #f0d0d8;
            border-radius: 8px 0 0 8px;
        }
        .info-table .pay-box {
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-left: none;
            border-radius: 0 8px 8px 0;
        }

        .box-label {
            font-size: 8.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #93394b;
            margin-bottom: 8px;
        }
        .box-name { font-size: 13px; font-weight: bold; color: #111827; margin-bottom: 3px; }
        .box-line { font-size: 10.5px; color: #4b5563; margin-bottom: 2px; }

        /* ── Items table ── */
        .section-title {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #93394b;
            margin-bottom: 10px;
        }

        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        .items-table thead tr { background: #93394b; }
        .items-table thead th {
            padding: 10px 12px;
            text-align: left;
            font-size: 9.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #ffffff;
        }
        .items-table tbody tr { border-bottom: 1px solid #f3e8eb; }
        .items-table tbody tr.even { background: #fdf4f6; }
        .items-table tbody td { padding: 10px 12px; font-size: 11px; vertical-align: middle; }
        .product-name { font-weight: 600; color: #111827; }
        .variant-label { font-size: 10px; color: #9ca3af; margin-top: 2px; }
        .color-swatch {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            border: 1px solid #d1d5db;
            vertical-align: -6px;
            margin-left: 2px;
        }

        /* ── Totals ── */
        .totals-wrap { width: 100%; margin-bottom: 28px; }
        .totals-table {
            width: 260px;
            float: right;
            border-collapse: collapse;
        }
        .totals-table td { padding: 5px 10px; font-size: 11px; }
        .totals-table .lbl { color: #6b7280; }
        .totals-table .amt { text-align: right; color: #374151; font-weight: 500; }
        .totals-table .disc-lbl { color: #bb4e64; }
        .totals-table .disc-amt { text-align: right; color: #bb4e64; font-weight: 500; }
        .totals-table .grand-row td {
            border-top: 2px solid #93394b;
            padding-top: 10px;
            font-size: 14px;
            font-weight: bold;
            color: #93394b;
        }

        .clearfix:after { content: ""; display: table; clear: both; }

        /* ── Status badge ── */
        .status-paid   { color: #065f46; background: #d1fae5; padding: 2px 8px; border-radius: 20px; font-size: 10px; font-weight: bold; }
        .status-unpaid { color: #92400e; background: #fef3c7; padding: 2px 8px; border-radius: 20px; font-size: 10px; font-weight: bold; }
        .status-other  { color: #1e40af; background: #dbeafe; padding: 2px 8px; border-radius: 20px; font-size: 10px; font-weight: bold; }

        /* ── Notes ── */
        .notes-box {
            background: #f9fafb;
            border-left: 3px solid #cb7888;
            border-radius: 0 6px 6px 0;
            padding: 10px 14px;
            margin-bottom: 28px;
            font-size: 11px;
            color: #4b5563;
            clear: both;
        }

        /* ── Footer ── */
        .footer {
            border-top: 1px solid #f3e8eb;
            padding-top: 14px;
            text-align: center;
            font-size: 10px;
            color: #9ca3af;
            clear: both;
        }
        .footer strong { color: #93394b; }
        .footer .thank-you { font-size: 13px; font-weight: bold; color: #93394b; margin-bottom: 4px; }
    </style>
</head>
<body>
<div class="page">

    {{-- ── HEADER ── --}}
    <div class="header">
        <table>
            <tr>
                <td style="width:55%;">
                    @php $logo = \App\Models\Setting::get('site_logo', ''); @endphp
                    @if($logo)
                        <img src="{{ public_path('storage/' . $logo) }}" class="logo-img" alt="{{ config('app.name') }}">
                    @else
                        <div class="brand-name">{{ \App\Models\Setting::get('site_name', config('app.name')) }}</div>
                        <div class="brand-tagline">{{ \App\Models\Setting::get('site_tagline', '') }}</div>
                    @endif
                </td>
                <td style="width:45%; text-align:right;">
                    <div class="invoice-badge">INVOICE</div>
                    <div class="invoice-num" style="clear:right;">#{{ $order->order_number }}</div>
                    <div class="invoice-meta">
                        <div>Date: <strong>{{ $order->created_at->format('d M Y') }}</strong></div>
                        <div>Order Status: <strong>{{ ucfirst($order->status) }}</strong></div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="divider"></div>

    {{-- ── SHIP TO / PAYMENT INFO ── --}}
    <table class="info-table">
        <tr>
            <td class="ship-box">
                <div class="box-label">Ship To</div>
                <div class="box-name">{{ $order->ship_name }}</div>
                <div class="box-line">{{ $order->ship_phone }}</div>
                <div class="box-line">{{ $order->ship_address }}</div>
                <div class="box-line">
                    {{ $order->ship_city }}@if($order->ship_district), {{ $order->ship_district }}@endif@if($order->ship_zip) — {{ $order->ship_zip }}@endif
                </div>
            </td>
            <td class="pay-box">
                <div class="box-label">Payment Details</div>
                <div class="box-line"><strong>Method:</strong> {{ ucwords(str_replace('_', ' ', $order->payment_method)) }}</div>
                <div class="box-line">
                    <strong>Status:</strong>
                    @php
                        $ps = $order->payment_status;
                        $cls = $ps === 'paid' ? 'status-paid' : ($ps === 'unpaid' ? 'status-unpaid' : 'status-other');
                    @endphp
                    <span class="{{ $cls }}">{{ ucwords(str_replace('_', ' ', $ps)) }}</span>
                </div>
                @if($order->manualPayment?->transaction_id)
                    <div class="box-line"><strong>TxID:</strong> {{ $order->manualPayment->transaction_id }}</div>
                @endif
                @if($order->tracking_number)
                    <div class="box-line"><strong>Tracking #:</strong> {{ $order->tracking_number }}</div>
                @endif
                @if($order->coupon)
                    <div class="box-line"><strong>Coupon:</strong> {{ $order->coupon->code }}</div>
                @endif
            </td>
        </tr>
    </table>

    {{-- ── ORDER ITEMS ── --}}
    <div class="section-title">Order Items</div>
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:46%;">Product</th>
                <th style="width:14%; text-align:center;">Qty</th>
                <th style="width:20%; text-align:right;">Unit Price</th>
                <th style="width:20%; text-align:right;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $i => $item)
                <tr class="{{ $i % 2 === 1 ? 'even' : '' }}">
                    <td>
                        <div class="product-name">{{ $item->product_name }}</div>
                        @if($item->variant_label)
                            @php
                                $vlParts = array_map('trim', explode('/', $item->variant_label));
                            @endphp
                            <div class="variant-label">
                                @foreach($vlParts as $vlIdx => $vlPart)
                                    @if($vlIdx > 0)<span style="color:#d1d5db;"> / </span>@endif
                                    @php
                                        $vlSep = strpos($vlPart, ': ');
                                        $vlKey = $vlSep !== false ? substr($vlPart, 0, $vlSep) : null;
                                        $vlVal = $vlSep !== false ? substr($vlPart, $vlSep + 2) : $vlPart;
                                        $vlIsColor = $vlKey && in_array(strtolower(trim($vlKey)), ['color', 'colour']);
                                    @endphp
                                    @if($vlKey){{ trim($vlKey) }}: @endif
                                    @if($vlIsColor)
                                        <span class="color-swatch" style="background-color: {{ trim($vlVal) }};"></span>
                                    @else
                                        {{ $vlVal }}
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    </td>
                    <td style="text-align:center;">{{ $item->quantity }}</td>
                    <td style="text-align:right;">Tk. {{ number_format($item->unit_price, 0) }}</td>
                    <td style="text-align:right;">Tk. {{ number_format($item->subtotal, 0) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- ── TOTALS ── --}}
    <div class="clearfix">
        <table class="totals-table">
            <tr>
                <td class="lbl">Subtotal</td>
                <td class="amt">Tk. {{ number_format($order->subtotal, 0) }}</td>
            </tr>
            @if($order->discount_amount > 0)
                <tr>
                    <td class="lbl disc-lbl">Discount</td>
                    <td class="disc-amt">-Tk. {{ number_format($order->discount_amount, 0) }}</td>
                </tr>
            @endif
            <tr>
                <td class="lbl">Shipping</td>
                <td class="amt">{{ $order->shipping_amount > 0 ? 'Tk. ' . number_format($order->shipping_amount, 0) : 'Free' }}</td>
            </tr>
            @if($order->tax_amount > 0)
                <tr>
                    <td class="lbl">Tax</td>
                    <td class="amt">Tk. {{ number_format($order->tax_amount, 0) }}</td>
                </tr>
            @endif
            <tr class="grand-row">
                <td class="lbl">Total</td>
                <td class="amt">Tk. {{ number_format($order->total_amount, 0) }}</td>
            </tr>
        </table>
    </div>

    {{-- ── NOTES ── --}}
    @if($order->notes)
        <div class="notes-box">
            <strong>Order Notes:</strong> {{ $order->notes }}
        </div>
    @endif

    {{-- ── FOOTER ── --}}
    <div class="footer">
        <div class="thank-you">Thank you for your order!</div>
        @php $contactEmail = \App\Models\Setting::get('contact_email', ''); @endphp
        @if($contactEmail)
            <p>Questions? Contact us at <strong>{{ $contactEmail }}</strong></p>
        @endif
        <p style="margin-top:4px; color:#d1d5db;">Generated {{ now()->format('d M Y, h:i A') }} &nbsp;·&nbsp; {{ config('app.name') }}</p>
    </div>

</div>
</body>
</html>
