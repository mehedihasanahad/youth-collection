@extends('layouts.app')

@section('title', __('front.order_number') . $order->order_number . ' — ' . config('app.name'))

@section('content')
@php
    $locale = app()->getLocale();

    $statusColors = [
        'pending'    => 'bg-amber-100 text-amber-700',
        'processing' => 'bg-blue-100 text-blue-700',
        'shipped'    => 'bg-indigo-100 text-indigo-700',
        'delivered'  => 'bg-emerald-100 text-emerald-700',
        'cancelled'  => 'bg-red-100 text-red-700',
        'returned'   => 'bg-gray-100 text-gray-600',
    ];
    $paymentColors = [
        'unpaid'               => 'bg-gray-100 text-gray-600',
        'pending_verification' => 'bg-amber-100 text-amber-700',
        'paid'                 => 'bg-emerald-100 text-emerald-700',
        'refunded'             => 'bg-purple-100 text-purple-700',
        'failed'               => 'bg-red-100 text-red-700',
    ];
    $historyIcons = [
        'pending'    => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
        'processing' => 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15',
        'shipped'    => 'M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0',
        'delivered'  => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        'cancelled'  => 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
        'returned'   => 'M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6',
    ];
@endphp

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    <h1 class="text-2xl font-bold text-gray-900 mb-8">{{ __('front.my_account') }}</h1>

    <div class="flex flex-col lg:flex-row gap-8">

        {{-- Sidebar --}}
        @include('account.partials.sidebar')

        {{-- Main --}}
        <div class="flex-1 min-w-0">

            {{-- Order header --}}
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
                <div>
                    <h2 class="text-lg font-bold text-gray-900">{{ __('front.order_number') }}{{ $order->order_number }}</h2>
                    <p class="text-sm text-gray-400 mt-0.5">{{ $order->created_at->format('d M Y, h:i A') }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs px-3 py-1 rounded-full font-semibold {{ $statusColors[$order->status] ?? 'bg-gray-100 text-gray-600' }}">
                        {{ __('front.status_' . $order->status) }}
                    </span>
                    <span class="text-xs px-3 py-1 rounded-full font-semibold {{ $paymentColors[$order->payment_status] ?? 'bg-gray-100 text-gray-600' }}">
                        {{ __('front.payment_' . $order->payment_status) }}
                    </span>
                </div>
            </div>

            {{-- Payment info banner --}}
            @if($order->payment_method !== 'cod' && $order->payment_status === 'pending_verification')
                <div class="bg-amber-50 border border-amber-200 rounded-2xl px-5 py-4 mb-6 flex items-start gap-3">
                    <svg class="w-5 h-5 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <p class="text-sm font-semibold text-amber-800">{{ __('front.payment_method_' . $order->payment_method) }}</p>
                        <p class="text-sm text-amber-700 mt-0.5">{{ __('front.manual_pay_pending') }}</p>
                        @if($order->manualPayment)
                            <p class="text-xs text-amber-600 mt-1">{{ __('front.transaction_id') }}: <span class="font-mono font-medium">{{ $order->manualPayment->transaction_id }}</span></p>
                        @endif
                    </div>
                </div>
            @elseif($order->payment_method === 'cod' && in_array($order->status, ['pending','processing']))
                <div class="bg-blue-50 border border-blue-200 rounded-2xl px-5 py-4 mb-6 flex items-start gap-3">
                    <svg class="w-5 h-5 text-blue-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <div>
                        <p class="text-sm font-semibold text-blue-800">{{ __('front.payment_method_cod') }}</p>
                        <p class="text-sm text-blue-700 mt-0.5">{{ __('front.cod_pending') }}</p>
                    </div>
                </div>
            @endif

            <div class="bg-white border border-gray-100 rounded-2xl overflow-hidden mb-6">

                {{-- Order items --}}
                <div class="p-6 border-b border-gray-100">
                    <h3 class="text-sm font-bold text-gray-900 mb-4">{{ __('front.order_items') }}</h3>
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

                {{-- Shipping address + Payment info --}}
                <div class="grid sm:grid-cols-2 divide-y sm:divide-y-0 sm:divide-x divide-gray-100">
                    <div class="p-6">
                        <h3 class="text-sm font-bold text-gray-900 mb-3">{{ __('front.shipping_address') }}</h3>
                        <div class="text-sm text-gray-600 space-y-0.5">
                            <p class="font-medium text-gray-800">{{ $order->ship_name }}</p>
                            <p>{{ $order->ship_phone }}</p>
                            <p>{{ $order->ship_address }}</p>
                            <p>{{ $order->ship_district }}</p>
                        </div>
                    </div>
                    <div class="p-6">
                        <h3 class="text-sm font-bold text-gray-900 mb-3">{{ __('front.payment_info') }}</h3>
                        <div class="text-sm text-gray-600 space-y-1">
                            <div class="flex justify-between">
                                <span class="text-gray-400">{{ __('front.payment_method') }}</span>
                                <span class="font-medium text-gray-800">{{ __('front.payment_method_' . $order->payment_method) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-400">{{ __('front.payment_status') }}</span>
                                <span class="font-medium {{ $paymentColors[$order->payment_status] ?? '' }} px-2 py-0.5 rounded-full text-xs">
                                    {{ __('front.payment_' . $order->payment_status) }}
                                </span>
                            </div>
                            @if($order->manualPayment)
                                <div class="flex justify-between">
                                    <span class="text-gray-400">{{ __('front.transaction_id') }}</span>
                                    <span class="font-mono text-xs text-gray-700">{{ $order->manualPayment->transaction_id }}</span>
                                </div>
                            @endif
                            @if($order->tracking_number)
                                <div class="flex justify-between">
                                    <span class="text-gray-400">{{ __('front.tracking_number') }}</span>
                                    <span class="font-mono text-xs text-gray-700">{{ $order->tracking_number }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Status timeline --}}
            @if($order->statusHistory->count() > 0)
                <div class="bg-white border border-gray-100 rounded-2xl p-6 mb-6">
                    <h3 class="text-sm font-bold text-gray-900 mb-5">{{ __('front.order_timeline') }}</h3>
                    <div class="space-y-4">
                        @foreach($order->statusHistory as $history)
                            @php $icon = $historyIcons[$history->status] ?? 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'; @endphp
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-full {{ $statusColors[$history->status] ?? 'bg-gray-100 text-gray-600' }} flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}"/>
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-gray-800">{{ __('front.status_' . $history->status) }}</p>
                                    @if($history->notes)
                                        <p class="text-xs text-gray-500 mt-0.5">{{ $history->notes }}</p>
                                    @endif
                                    @if($history->created_at)
                                        <p class="text-xs text-gray-400 mt-0.5">{{ $history->created_at->format('d M Y, h:i A') }}</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Actions --}}
            <div class="flex flex-col sm:flex-row gap-3">
                <a href="{{ route('orders.index') }}"
                   class="flex-1 flex items-center justify-center gap-2 border-2 border-gray-200 hover:border-primary-400 text-gray-700 hover:text-primary-700 font-semibold py-3 rounded-xl transition-all text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    {{ __('front.my_orders') }}
                </a>
                <button type="button"
                        onclick="downloadInvoice('{{ route('orders.invoice', $order) }}', 'Invoice-{{ $order->order_number }}.pdf', this)"
                        class="flex-1 flex items-center justify-center gap-2 border-2 border-accent-400 hover:bg-accent-50 text-accent-700 hover:text-accent-800 font-semibold py-3 rounded-xl transition-all text-sm cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    {{ __('front.download_invoice') }}
                </button>
                <a href="{{ route('home') }}"
                   class="flex-1 flex items-center justify-center gap-2 bg-primary-600 hover:bg-primary-700 text-white font-semibold py-3 rounded-xl transition-colors text-sm">
                    {{ __('front.continue_shopping') }}
                </a>
            </div>

        </div>
    </div>
</div>
@endsection
