@extends('layouts.app')

@section('title', __('front.order_placed') . ' — ' . config('app.name'))

@section('content')
@php
    $locale = app()->getLocale();
    $isManual = in_array($order->payment_method, ['bkash', 'nagad']);
    $isCod    = $order->payment_method === 'cod';

    $statusColors = [
        'pending'    => 'bg-amber-100 text-amber-700',
        'processing' => 'bg-blue-100 text-blue-700',
        'shipped'    => 'bg-indigo-100 text-indigo-700',
        'delivered'  => 'bg-emerald-100 text-emerald-700',
        'cancelled'  => 'bg-red-100 text-red-700',
    ];
    $paymentColors = [
        'unpaid'               => 'bg-gray-100 text-gray-600',
        'pending_verification' => 'bg-amber-100 text-amber-700',
        'paid'                 => 'bg-emerald-100 text-emerald-700',
        'failed'               => 'bg-red-100 text-red-700',
    ];
@endphp

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-14">

    {{-- Success header --}}
    <div class="text-center mb-10">
        <div class="w-20 h-20 bg-emerald-100 rounded-full flex items-center justify-center mx-auto mb-5">
            <svg class="w-10 h-10 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-2">{{ __('front.order_placed') }}</h1>
        <p class="text-gray-500 text-sm">{{ __('front.order_confirmed_msg') }}</p>
        <div class="mt-4 inline-flex items-center gap-2 bg-gray-100 rounded-xl px-5 py-2.5">
            <span class="text-sm text-gray-500">{{ __('front.order_number') }}</span>
            <span class="text-base font-bold text-gray-900">{{ $order->order_number }}</span>
        </div>
    </div>

    {{-- Payment instruction banner --}}
    @if($isManual)
        <div class="bg-amber-50 border border-amber-200 rounded-2xl px-5 py-4 mb-8 flex items-start gap-3">
            <svg class="w-5 h-5 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
                <p class="text-sm font-semibold text-amber-800">{{ __('front.payment_method_' . $order->payment_method) }}</p>
                <p class="text-sm text-amber-700 mt-0.5">{{ __('front.manual_pay_pending') }}</p>
                @if($order->manualPayment)
                    <p class="text-xs text-amber-600 mt-1">TxID: <span class="font-mono font-medium">{{ $order->manualPayment->transaction_id }}</span></p>
                @endif
            </div>
        </div>
    @elseif($isCod)
        <div class="bg-blue-50 border border-blue-200 rounded-2xl px-5 py-4 mb-8 flex items-start gap-3">
            <svg class="w-5 h-5 text-blue-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <div>
                <p class="text-sm font-semibold text-blue-800">{{ __('front.payment_method_cod') }}</p>
                <p class="text-sm text-blue-700 mt-0.5">{{ __('front.cod_pending') }}</p>
            </div>
        </div>
    @endif

    {{-- Order status row --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-8">
        <div class="bg-white border border-gray-100 rounded-xl p-4 text-center">
            <p class="text-xs text-gray-400 mb-1">{{ __('front.order_status') }}</p>
            <span class="text-xs font-semibold px-2 py-1 rounded-full {{ $statusColors[$order->status] ?? 'bg-gray-100 text-gray-600' }}">
                {{ __('front.status_' . $order->status) }}
            </span>
        </div>
        <div class="bg-white border border-gray-100 rounded-xl p-4 text-center">
            <p class="text-xs text-gray-400 mb-1">{{ __('front.payment_status') }}</p>
            <span class="text-xs font-semibold px-2 py-1 rounded-full {{ $paymentColors[$order->payment_status] ?? 'bg-gray-100 text-gray-600' }}">
                {{ __('front.payment_' . $order->payment_status) }}
            </span>
        </div>
        <div class="bg-white border border-gray-100 rounded-xl p-4 text-center">
            <p class="text-xs text-gray-400 mb-1">{{ __('front.payment_method') }}</p>
            <p class="text-xs font-semibold text-gray-700">{{ __('front.payment_method_' . $order->payment_method) }}</p>
        </div>
        <div class="bg-white border border-gray-100 rounded-xl p-4 text-center">
            <p class="text-xs text-gray-400 mb-1">{{ __('front.order_date') }}</p>
            <p class="text-xs font-semibold text-gray-700">{{ $order->created_at->format('d M Y') }}</p>
        </div>
    </div>

    <div class="bg-white border border-gray-100 rounded-2xl overflow-hidden mb-6">

        {{-- Order items --}}
        <div class="p-6 border-b border-gray-100">
            <h2 class="text-sm font-bold text-gray-900 mb-4">{{ __('front.order_items') }}</h2>
            <div class="space-y-4">
                @foreach($order->items as $item)
                    <div class="flex items-center gap-4">
                        @php $img = $item->product?->primaryImage; @endphp
                        @if($img)
                            <img src="{{ asset('storage/' . $img->path) }}"
                                 class="w-14 h-14 rounded-xl object-cover border border-gray-100 shrink-0"
                                 alt="{{ $item->product_name }}">
                        @else
                            <div class="w-14 h-14 rounded-xl bg-gray-100 shrink-0"></div>
                        @endif
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-800">{{ $item->product_name }}</p>
                            @if($item->variant_label)
                                <p class="text-xs text-gray-500">{{ $item->variant_label }}</p>
                            @endif
                            <p class="text-xs text-gray-400">{{ $item->quantity }} × ৳{{ number_format($item->unit_price, 0) }}</p>
                        </div>
                        <span class="text-sm font-semibold text-gray-900 shrink-0">৳{{ number_format($item->subtotal, 0) }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Totals --}}
        <div class="p-6 border-b border-gray-100">
            <div class="space-y-2 text-sm max-w-xs ml-auto">
                <div class="flex justify-between text-gray-600">
                    <span>{{ __('front.subtotal') }}</span>
                    <span>৳{{ number_format($order->subtotal, 0) }}</span>
                </div>
                @if($order->discount_amount > 0)
                    <div class="flex justify-between text-emerald-600">
                        <span>{{ __('front.discount') }}</span>
                        <span>−৳{{ number_format($order->discount_amount, 0) }}</span>
                    </div>
                @endif
                <div class="flex justify-between text-gray-600">
                    <span>{{ __('front.shipping') }}</span>
                    <span>{{ $order->shipping_amount > 0 ? '৳' . number_format($order->shipping_amount, 0) : __('front.free_shipping') }}</span>
                </div>
                <div class="border-t border-gray-100 pt-2 flex justify-between font-bold text-gray-900 text-base">
                    <span>{{ __('front.total') }}</span>
                    <span>৳{{ number_format($order->total_amount, 0) }}</span>
                </div>
            </div>
        </div>

        {{-- Shipping address --}}
        <div class="p-6">
            <h2 class="text-sm font-bold text-gray-900 mb-3">{{ __('front.shipping_address') }}</h2>
            <div class="text-sm text-gray-600 space-y-0.5">
                <p class="font-medium text-gray-800">{{ $order->ship_name }}</p>
                <p>{{ $order->ship_phone }}</p>
                <p>{{ $order->ship_address }}</p>
                <p>{{ $order->ship_city }}, {{ $order->ship_district }}{{ $order->ship_zip ? ' - ' . $order->ship_zip : '' }}</p>
            </div>
        </div>

    </div>

    {{-- CTA buttons --}}
    <div class="flex flex-col sm:flex-row gap-3">
        <a href="{{ route('home') }}"
           class="flex-1 flex items-center justify-center gap-2 border-2 border-gray-200 hover:border-primary-400 text-gray-700 hover:text-primary-700 font-semibold py-3 rounded-xl transition-all text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            {{ __('front.continue_shopping') }}
        </a>
        <button type="button"
                onclick="downloadInvoice('{{ route('orders.invoice', $order) }}', 'Invoice-{{ $order->order_number }}.pdf', this)"
                class="flex-1 flex items-center justify-center gap-2 border-2 border-accent-400 hover:bg-accent-50 text-accent-700 hover:text-accent-800 font-semibold py-3 rounded-xl transition-all text-sm cursor-pointer">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            {{ __('front.download_invoice') }}
        </button>
        <a href="{{ route('orders.index') }}"
           class="flex-1 flex items-center justify-center gap-2 bg-primary-600 hover:bg-primary-700 text-white font-semibold py-3 rounded-xl transition-colors text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            {{ __('front.my_orders') }}
        </a>
    </div>

</div>
@endsection

@push('pixel-events')
<script>
if (typeof fbq !== 'undefined') {
    fbq('track', 'Purchase', {
        value:        {{ (float) $order->total_amount }},
        currency:     'BDT',
        content_ids:  [{{ $order->items->pluck('product_id')->filter()->implode(',') }}],
        content_type: 'product',
        num_items:    {{ $order->items->sum('quantity') }},
        order_id:     '{{ $order->order_number }}',
        @if($order->ship_name)
        fn:           '{{ hash("sha256", strtolower(explode(" ", trim($order->ship_name), 2)[0])) }}',
        ln:           '{{ hash("sha256", strtolower(explode(" ", trim($order->ship_name), 2)[1] ?? "")) }}',
        @endif
        @if($order->ship_phone)
        ph:           '{{ hash("sha256", preg_replace("/\D/", "", $order->ship_phone)) }}',
        @endif
        @if($order->ship_city)
        ct:           '{{ hash("sha256", strtolower(trim($order->ship_city))) }}',
        @endif
        @if($order->ship_district)
        st:           '{{ hash("sha256", strtolower(trim($order->ship_district))) }}',
        @endif
        @if($order->ship_zip)
        zp:           '{{ hash("sha256", trim($order->ship_zip)) }}',
        @endif
        @auth
        em:           '{{ hash("sha256", strtolower(trim(auth()->user()->email))) }}',
        external_id:  '{{ hash("sha256", (string) auth()->id()) }}',
        @endauth
    });
}
</script>
@endpush
