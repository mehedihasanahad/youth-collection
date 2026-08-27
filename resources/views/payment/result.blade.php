@extends('layouts.app')

@section('title', __('front.payment_result') . ' — ' . config('app.name'))

@section('content')
@php
    $paid      = $order->payment_status === 'paid';
    $txn       = \App\Models\PaymentTransaction::where('order_id', $order->id)
                    ->where('gateway', 'sslcommerz')->latest()->first();
    $cancelled = ! $paid && ($txn?->status === 'cancelled');
@endphp

<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <div class="bg-white border border-gray-100 rounded-2xl p-8 text-center shadow-sm">

        @if($paid)
            {{-- Success --}}
            <div class="w-16 h-16 bg-emerald-100 rounded-full flex items-center justify-center mx-auto mb-5">
                <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 mb-2">{{ __('front.payment_success_title') }}</h1>
            <p class="text-gray-500 text-sm mb-6">{{ __('front.payment_success_subtitle') }}</p>
        @else
            {{-- Failed / Cancelled --}}
            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-5">
                <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 mb-2">
                {{ $cancelled ? __('front.payment_cancelled_title') : __('front.payment_failed_title') }}
            </h1>
            <p class="text-gray-500 text-sm mb-6">
                {{ $cancelled ? __('front.payment_cancelled_subtitle') : __('front.payment_failed_subtitle') }}
            </p>
        @endif

        {{-- Order info card --}}
        <div class="bg-gray-50 rounded-xl px-5 py-4 text-left space-y-2 mb-6">
            <div class="flex justify-between text-sm">
                <span class="text-gray-500">{{ __('front.order_number') }}</span>
                <span class="font-semibold text-gray-800">{{ $order->order_number }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-gray-500">{{ __('front.total') }}</span>
                <span class="font-semibold text-gray-800">৳{{ number_format($order->total_amount, 0) }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-gray-500">{{ __('front.payment_status_label') }}</span>
                <span class="font-semibold {{ $paid ? 'text-emerald-600' : 'text-red-500' }}">
                    {{ $paid ? __('front.paid') : ($cancelled ? __('front.cancelled') : __('front.failed')) }}
                </span>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            @auth
                @if($paid)
                    <a href="{{ route('orders.show', $order) }}"
                       class="bg-primary-600 hover:bg-primary-700 text-white font-semibold px-6 py-2.5 rounded-xl text-sm transition-colors">
                        {{ __('front.view_order') }}
                    </a>
                @else
                    <a href="{{ route('payment.retry', $order) }}"
                       class="bg-primary-600 hover:bg-primary-700 text-white font-semibold px-6 py-2.5 rounded-xl text-sm transition-colors">
                        {{ __('front.try_again') }}
                    </a>
                    <a href="{{ route('orders.show', $order) }}"
                       class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold px-6 py-2.5 rounded-xl text-sm transition-colors">
                        {{ __('front.view_order') }}
                    </a>
                @endif
            @else
                <a href="{{ route('login') }}"
                   class="bg-primary-600 hover:bg-primary-700 text-white font-semibold px-6 py-2.5 rounded-xl text-sm transition-colors">
                    {{ __('front.sign_in') }}
                </a>
            @endauth
            <a href="{{ route('home') }}"
               class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold px-6 py-2.5 rounded-xl text-sm transition-colors">
                {{ __('front.continue_shopping') }}
            </a>
        </div>

    </div>
</div>

@include('partials.new-account-credentials-modal')
@endsection

@push('pixel-events')
@if($paid)
{{-- SSLCommerz orders never reach the confirmation screen, so Purchase is
     reported here. Keyed on the order number and stored once per browser, so
     reloading this page or returning to it cannot report a second Purchase. --}}
<script>
fbTrack('Purchase', {
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
    @if($order->ship_district)
    st:           '{{ hash("sha256", strtolower(trim($order->ship_district))) }}',
    @endif
}, { eventId: 'order_{{ $order->order_number }}', once: true });
</script>
@endif
@endpush
