@extends('layouts.app')

@section('title', __('front.checkout') . ' — ' . config('app.name'))

@section('content')
@php $locale = app()->getLocale(); @endphp

{{-- Breadcrumb --}}
<nav class="bg-gray-50 border-b border-gray-100 py-3">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <ol class="flex items-center gap-1.5 text-sm text-gray-500 flex-wrap">
            <li><a href="{{ route('home') }}" class="hover:text-primary-600 transition-colors">{{ __('front.home') }}</a></li>
            <li><span class="text-gray-300">/</span></li>
            <li><a href="{{ route('cart.index') }}" class="hover:text-primary-600 transition-colors">{{ __('front.your_cart') }}</a></li>
            <li><span class="text-gray-300">/</span></li>
            <li class="text-gray-800 font-medium">{{ __('front.checkout') }}</li>
        </ol>
    </div>
</nav>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <h1 class="text-2xl font-bold text-gray-900 mb-8">{{ __('front.checkout') }}</h1>

    {{-- Errors --}}
    @if(session('checkout_error'))
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 rounded-xl px-5 py-4 text-sm">
            {{ session('checkout_error') }}
        </div>
    @endif

    {{-- Outer grid: left (form) + right (summary). Right column is OUTSIDE <form> to avoid nested forms. --}}
    <div class="lg:grid lg:grid-cols-3 lg:gap-10">

    <form method="POST" action="{{ route('checkout.place-order') }}" enctype="multipart/form-data" id="checkout-form" class="lg:col-span-2">
        {{-- Ties every resubmission of this form to the one order it creates. --}}
        <input type="hidden" name="idempotency_key" value="{{ $idempotencyKey }}">
        @csrf
            {{-- ── LEFT: Checkout Steps ────────────────────────────────────── --}}
            <div class="space-y-8 mb-10 lg:mb-0">

                {{-- ── STEP 1: Delivery Address ── --}}
                <div class="bg-white border border-gray-100 rounded-2xl p-6">
                    <h2 class="text-base font-bold text-gray-900 mb-5 flex items-center gap-2">
                        <span class="w-6 h-6 bg-primary-600 text-white rounded-full flex items-center justify-center text-xs font-bold shrink-0">1</span>
                        {{ __('front.delivery_address') }}
                    </h2>

                    {{-- Saved addresses --}}
                    @if($addresses->isNotEmpty())
                        <div class="mb-5">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">{{ __('front.address_saved') }}</p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                @foreach($addresses as $addr)
                                    <button type="button"
                                            onclick="fillAddress({{ json_encode(['name'=>$addr->recipient_name,'phone'=>$addr->phone,'address'=>$addr->address_line,'district'=>$addr->district]) }})"
                                            class="text-left border-2 border-gray-200 hover:border-primary-400 rounded-xl p-3 transition-all text-sm">
                                        @if($addr->label)
                                            <span class="text-xs font-semibold text-primary-600 uppercase">{{ $addr->label }}</span><br>
                                        @endif
                                        <span class="font-medium text-gray-800">{{ $addr->recipient_name }}</span><br>
                                        <span class="text-gray-500 text-xs">{{ $addr->address_line }}@if($addr->city), {{ $addr->city }}@endif, {{ $addr->district }}</span>
                                    </button>
                                @endforeach
                            </div>
                            <p class="text-xs text-gray-400 mt-2">{{ __('front.fill_from_saved') }}</p>
                        </div>
                        <div class="border-t border-gray-100 mb-5"></div>
                    @endif

                    {{-- Address form fields --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        {{-- Email — guests only, optional --}}
                        @guest
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                {{ __('front.email') }} <span class="text-gray-400 font-normal">({{ __('front.optional') }})</span>
                            </label>
                            <input type="email" name="email" value="{{ old('email') }}"
                                   placeholder="your@email.com"
                                   class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-400 @error('email') border-red-400 @enderror">
                            @error('email')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                            <p class="text-xs text-gray-400 mt-1">{{ __('front.checkout_email_hint') }}</p>
                        </div>
                        @endguest

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('front.ship_name') }} <span class="text-red-500">*</span></label>
                            <input type="text" name="ship_name" value="{{ old('ship_name', auth()->user()?->name) }}" id="field-ship_name"
                                   class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-400 @error('ship_name') border-red-400 @enderror">
                            @error('ship_name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('front.ship_phone') }} <span class="text-red-500">*</span></label>
                            <input type="text" name="ship_phone" value="{{ old('ship_phone', auth()->user()?->phone ?? '') }}" id="field-ship_phone"
                                   placeholder="017xxxxxxxx"
                                   class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-400 @error('ship_phone') border-red-400 @enderror">
                            @error('ship_phone')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                            @guest
                            <p class="text-xs text-gray-400 mt-1">{{ __('front.checkout_phone_hint') }}</p>
                            @endguest
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('front.ship_address') }} <span class="text-red-500">*</span></label>
                            <input type="text" name="ship_address" value="{{ old('ship_address') }}" id="field-ship_address"
                                   placeholder="House/Road/Area"
                                   class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-400 @error('ship_address') border-red-400 @enderror">
                            @error('ship_address')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('front.ship_district') }} <span class="text-red-500">*</span></label>
                            <select name="ship_district" id="district-select"
                                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-400 @error('ship_district') border-red-400 @enderror">
                                <option value="">{{ __('front.select_district') }}</option>
                                @foreach($districts as $district)
                                    <option value="{{ $district }}" {{ old('ship_district') === $district ? 'selected' : '' }}>
                                        {{ $district }}
                                    </option>
                                @endforeach
                            </select>
                            @error('ship_district')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('front.order_notes') }}</label>
                            <textarea name="notes" rows="2"
                                      class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-400 resize-none">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- ── STEP 2: Shipping Method ── --}}
                <div class="bg-white border border-gray-100 rounded-2xl p-6">
                    <h2 class="text-base font-bold text-gray-900 mb-5 flex items-center gap-2">
                        <span class="w-6 h-6 bg-primary-600 text-white rounded-full flex items-center justify-center text-xs font-bold shrink-0">2</span>
                        {{ __('front.shipping_method') }}
                    </h2>

                    <input type="hidden" name="shipping_rate_id" id="shipping-rate-id" value="{{ old('shipping_rate_id') }}">

                    {{-- Placeholder shown before district is selected --}}
                    <div id="shipping-placeholder" class="flex items-center gap-3 bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm text-gray-400">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>{{ __('front.select_district') }}</span>
                    </div>

                    {{-- Unavailable notice --}}
                    <div id="shipping-unavailable" class="hidden items-center gap-3 bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-sm text-red-600">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>{{ __('front.shipping_unavailable') }}</span>
                    </div>

                    {{-- Rate options (rendered by JS) --}}
                    <div id="shipping-rates" class="hidden space-y-3"></div>
                </div>

                {{-- ── STEP 3: Payment Method ── --}}
                <div class="bg-white border border-gray-100 rounded-2xl p-6">
                    <h2 class="text-base font-bold text-gray-900 mb-5 flex items-center gap-2">
                        <span class="w-6 h-6 bg-primary-600 text-white rounded-full flex items-center justify-center text-xs font-bold shrink-0">3</span>
                        {{ __('front.payment_method') }}
                    </h2>

                    <div class="space-y-3">

                        {{-- COD --}}
                        @if(in_array('cod', $paymentMethods))
                            <label class="payment-card flex items-start gap-4 border-2 border-gray-200 rounded-xl p-4 cursor-pointer transition-all hover:border-primary-300 has-[:checked]:border-primary-500 has-[:checked]:bg-primary-50">
                                <input type="radio" name="payment_method" value="cod" class="mt-0.5 accent-primary-600"
                                       {{ old('payment_method', 'cod') === 'cod' ? 'checked' : '' }}
                                       onchange="togglePaymentFields('cod')">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                        </svg>
                                        <span class="font-semibold text-gray-800 text-sm">{{ __('front.payment_method_cod') }}</span>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">{{ __('front.cod_description') }}</p>
                                </div>
                            </label>
                        @endif

                        {{-- bKash --}}
                        @if(in_array('bkash', $paymentMethods))
                            <label class="payment-card flex items-start gap-4 border-2 border-gray-200 rounded-xl p-4 cursor-pointer transition-all hover:border-primary-300 has-[:checked]:border-primary-500 has-[:checked]:bg-primary-50">
                                <input type="radio" name="payment_method" value="bkash" class="mt-0.5 accent-primary-600"
                                       {{ old('payment_method') === 'bkash' ? 'checked' : '' }}
                                       onchange="togglePaymentFields('bkash')">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="w-5 h-5 bg-pink-600 rounded-full flex items-center justify-center text-white text-[9px] font-bold shrink-0">b</span>
                                        <span class="font-semibold text-gray-800 text-sm">{{ __('front.payment_method_bkash') }} <span class="text-pink-600 font-normal text-xs">(Send Money)</span></span>
                                    </div>
                                    @if($bkashNumber)
                                        <p class="text-xs text-gray-500 mt-1">Send Money to: <span class="font-semibold text-gray-700">{{ $bkashNumber }}</span>{{ $bkashName ? ' (' . $bkashName . ')' : '' }}</p>
                                    @endif
                                </div>
                            </label>
                            {{-- bKash fields --}}
                            <div id="fields-bkash" class="{{ old('payment_method') === 'bkash' ? '' : 'hidden' }} bg-pink-50 border border-pink-100 rounded-xl p-4 space-y-4 -mt-1">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('front.sender_number', ['method' => 'bKash']) }} <span class="text-red-500">*</span></label>
                                        <input type="text" name="sender_number" value="{{ old('sender_number') }}"
                                               placeholder="01XXXXXXXXX"
                                               {{ old('payment_method') !== 'bkash' ? 'disabled' : '' }}
                                               class="w-full border {{ $errors->has('sender_number') ? 'border-red-400' : 'border-gray-200' }} rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-400">
                                        @error('sender_number')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('front.transaction_id') }} <span class="text-red-500">*</span></label>
                                        <input type="text" name="transaction_id" value="{{ old('transaction_id') }}"
                                               placeholder="TrxID"
                                               {{ old('payment_method') !== 'bkash' ? 'disabled' : '' }}
                                               class="w-full border {{ $errors->has('transaction_id') ? 'border-red-400' : 'border-gray-200' }} rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-400">
                                        @error('transaction_id')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('front.payment_screenshot') }}</label>
                                    <input type="file" name="screenshot" accept="image/*"
                                           class="w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100">
                                    @error('screenshot')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                                </div>
                            </div>
                        @endif

                        {{-- Nagad --}}
                        @if(in_array('nagad', $paymentMethods))
                            <label class="payment-card flex items-start gap-4 border-2 border-gray-200 rounded-xl p-4 cursor-pointer transition-all hover:border-primary-300 has-[:checked]:border-primary-500 has-[:checked]:bg-primary-50">
                                <input type="radio" name="payment_method" value="nagad" class="mt-0.5 accent-primary-600"
                                       {{ old('payment_method') === 'nagad' ? 'checked' : '' }}
                                       onchange="togglePaymentFields('nagad')">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="w-5 h-5 bg-orange-500 rounded-full flex items-center justify-center text-white text-[9px] font-bold shrink-0">N</span>
                                        <span class="font-semibold text-gray-800 text-sm">{{ __('front.payment_method_nagad') }} <span class="text-orange-600 font-normal text-xs">(Send Money)</span></span>
                                    </div>
                                    @if($nagadNumber)
                                        <p class="text-xs text-gray-500 mt-1">Send Money to: <span class="font-semibold text-gray-700">{{ $nagadNumber }}</span>{{ $nagadName ? ' (' . $nagadName . ')' : '' }}</p>
                                    @endif
                                </div>
                            </label>
                            {{-- Nagad fields --}}
                            <div id="fields-nagad" class="{{ old('payment_method') === 'nagad' ? '' : 'hidden' }} bg-orange-50 border border-orange-100 rounded-xl p-4 space-y-4 -mt-1">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('front.sender_number', ['method' => 'Nagad']) }} <span class="text-red-500">*</span></label>
                                        <input type="text" name="sender_number" value="{{ old('sender_number') }}"
                                               placeholder="01XXXXXXXXX"
                                               {{ old('payment_method') !== 'nagad' ? 'disabled' : '' }}
                                               class="nagad-field w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-400">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('front.transaction_id') }} <span class="text-red-500">*</span></label>
                                        <input type="text" name="transaction_id" value="{{ old('transaction_id') }}"
                                               placeholder="TrxID"
                                               {{ old('payment_method') !== 'nagad' ? 'disabled' : '' }}
                                               class="nagad-field w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-400">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('front.payment_screenshot') }}</label>
                                    <input type="file" name="screenshot" accept="image/*"
                                           class="w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100">
                                </div>
                            </div>
                        @endif

                        {{-- SSLCommerz --}}
                        @if(in_array('sslcommerz', $paymentMethods))
                            <label class="payment-card flex items-start gap-4 border-2 border-gray-200 rounded-xl p-4 cursor-pointer transition-all hover:border-primary-300 has-[:checked]:border-primary-500 has-[:checked]:bg-primary-50">
                                <input type="radio" name="payment_method" value="sslcommerz" class="mt-0.5 accent-primary-600"
                                       {{ old('payment_method') === 'sslcommerz' ? 'checked' : '' }}
                                       onchange="togglePaymentFields('sslcommerz')">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-5 h-5 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                        </svg>
                                        <span class="font-semibold text-gray-800 text-sm">{{ __('front.payment_method_sslcommerz') }}</span>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">{{ __('front.sslcommerz_description') }}</p>
                                </div>
                            </label>
                        @endif

                    </div>
                    @error('payment_method')
                        <p class="text-xs text-red-500 mt-2">{{ $message }}</p>
                    @enderror
                </div>

            </div>{{-- end left steps --}}

    </form>{{-- end #checkout-form --}}

            {{-- ── RIGHT: Order Summary ─────────────────────────────────────── --}}
            {{-- Intentionally outside <form> so coupon sub-forms are not nested --}}
            <div class="lg:col-span-1 mt-10 lg:mt-0">
                <div class="bg-white border border-gray-100 rounded-2xl p-6 sticky top-24 space-y-5">
                    <h2 class="text-base font-bold text-gray-900">{{ __('front.order_summary') }}</h2>

                    {{-- Cart items --}}
                    <div class="space-y-3 max-h-64 overflow-y-auto pr-1">
                        @foreach($cartItems as $item)
                            @php
                                $t = $item->product->getTranslation($locale) ?? $item->product->getTranslation('en');
                                $productName = $t?->name ?? $item->product->sku;
                                $unitPrice = $item->variant ? $item->variant->effective_price : $item->product->current_price;
                            @endphp
                            <div class="flex items-center gap-3">
                                @if($item->product->primaryImage)
                                    <img src="{{ asset('storage/' . $item->product->primaryImage->path) }}"
                                         class="w-12 h-12 rounded-xl object-cover border border-gray-100 shrink-0"
                                         alt="{{ $productName }}">
                                @else
                                    <div class="w-12 h-12 rounded-xl bg-gray-100 shrink-0"></div>
                                @endif
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-medium text-gray-800 line-clamp-1">{{ $productName }}</p>
                                    @if($item->variant && $item->variant->options->isNotEmpty())
                                        <div class="flex flex-wrap items-center gap-1.5 mt-0.5">
                                            @foreach($item->variant->options as $opt)
                                                @php $isColor = in_array(strtolower($opt->option_name), ['color', 'colour']); @endphp
                                                @if($isColor)
                                                    <span class="inline-flex items-center gap-1 text-[11px] text-gray-500">
                                                        {{ $opt->option_name }}:
                                                        <span class="w-3.5 h-3.5 rounded-full border border-gray-300 shrink-0"
                                                              style="background-color: {{ $opt->option_value }};"></span>
                                                    </span>
                                                @elseif($item->custom_size && strtolower($opt->option_name) === 'size')
                                                    <span class="text-[11px] text-gray-500">{{ $opt->option_name }}: {{ $item->custom_size }}</span>
                                                @else
                                                    <span class="text-[11px] text-gray-500">{{ $opt->option_name }}: {{ $opt->option_value }}</span>
                                                @endif
                                            @endforeach
                                        </div>
                                    @endif
                                    <p class="text-[11px] text-gray-400">{{ $item->quantity }} × ৳{{ number_format($unitPrice, 0) }}</p>
                                </div>
                                <span class="text-xs font-semibold text-gray-900 shrink-0">৳{{ number_format($item->line_total, 0) }}</span>
                            </div>
                        @endforeach
                    </div>

                    <div class="border-t border-gray-100"></div>

                    {{-- Coupon (standalone forms, outside checkout form) --}}
                    <div id="coupon-message">
                        @if(session('coupon_success'))
                            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-lg px-3 py-2 text-xs mb-2">{{ session('coupon_success') }}</div>
                        @endif
                        @if(session('coupon_error'))
                            <div class="bg-red-50 border border-red-200 text-red-600 rounded-lg px-3 py-2 text-xs mb-2">{{ session('coupon_error') }}</div>
                        @endif
                    </div>

                    @if($couponSession)
                        <div class="flex items-center justify-between bg-emerald-50 border border-emerald-200 rounded-xl px-3 py-2">
                            <span class="text-xs font-semibold text-emerald-700">{{ $couponSession['code'] }}</span>
                            <form method="POST" action="{{ route('checkout.remove-coupon') }}">
                                @csrf
                                <button type="submit" class="text-xs text-red-500 hover:text-red-700 font-medium transition-colors">
                                    {{ __('front.remove_coupon') }}
                                </button>
                            </form>
                        </div>
                    @else
                        <div class="flex gap-2">
                            <input type="text" id="coupon-input"
                                   placeholder="{{ __('front.coupon_code') }}"
                                   class="flex-1 border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-400">
                            <button type="button" id="coupon-apply-btn"
                                    class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold text-sm px-4 py-2 rounded-xl transition-colors shrink-0">
                                {{ __('front.apply_coupon') }}
                            </button>
                        </div>
                    @endif

                    <div class="border-t border-gray-100"></div>

                    {{-- Price breakdown --}}
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between text-gray-600">
                            <span>{{ __('front.subtotal') }}</span>
                            <span class="font-medium text-gray-900">৳{{ number_format($subtotal, 0) }}</span>
                        </div>

                        @if($couponSession)
                            <div class="flex justify-between text-emerald-600">
                                <span>{{ __('front.discount') }}</span>
                                <span class="font-medium">−৳{{ number_format($couponSession['discount'], 0) }}</span>
                            </div>
                        @endif

                        <div class="flex justify-between text-gray-600">
                            <span>{{ __('front.shipping') }}</span>
                            <span id="shipping-cost-display" class="text-gray-400 text-xs">{{ __('front.calculating') }}</span>
                        </div>
                    </div>

                    <div class="border-t border-gray-100"></div>

                    <div class="flex justify-between text-base font-bold text-gray-900">
                        <span>{{ __('front.total') }}</span>
                        <span id="total-display">
                            ৳{{ number_format($subtotal - ($couponSession ? $couponSession['discount'] : 0), 0) }}
                        </span>
                    </div>

                    {{-- Place order button (submits #checkout-form via JS) --}}
                    <button type="button" id="place-order-btn"
                            onclick="document.getElementById('checkout-form').requestSubmit()"
                            class="w-full bg-primary-600 hover:bg-primary-700 active:bg-primary-800 text-white font-semibold py-3.5 rounded-xl text-sm flex items-center justify-center gap-2 transition-colors">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        {{ __('front.place_order') }}
                    </button>

                    {{-- Trust badges --}}
                    <div class="flex items-center justify-center gap-4 pt-2 border-t border-gray-100">
                        <div class="flex items-center gap-1.5 text-xs text-gray-400">
                            <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                            Secure
                        </div>
                        <div class="flex items-center gap-1.5 text-xs text-gray-400">
                            <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            Safe Payment
                        </div>
                    </div>
                </div>
            </div>{{-- end right column --}}

    </div>{{-- end outer grid --}}
</div>{{-- end page container --}}
@endsection

@push('pixel-events')
<script>
{{-- Keyed to the cart contents: a reload after applying a coupon or picking a
     shipping method must not report a second InitiateCheckout. --}}
fbTrack('InitiateCheckout', {
    value:        {{ (float) ($subtotal - ($couponSession['discount'] ?? 0)) }},
    currency:     'BDT',
    num_items:    {{ $cartItems->sum('quantity') }},
    content_ids:  [{{ $cartItems->pluck('product_id')->filter()->implode(',') }}],
    content_type: 'product',
    @auth
    @if(auth()->user()->email)
    em:           '{{ hash("sha256", strtolower(trim(auth()->user()->email))) }}',
    @endif
    @if(auth()->user()->phone)
    ph:           '{{ hash("sha256", preg_replace("/\D/", "", auth()->user()->phone)) }}',
    @endif
    external_id:  '{{ hash("sha256", (string) auth()->id()) }}',
    @endauth
}, { eventId: '{{ $checkoutEventId }}', once: 'session' });
</script>
@endpush

@push('scripts')
<script>
const subtotalBase = {{ $subtotal - ($couponSession ? $couponSession['discount'] : 0) }};
let shippingCost = 0;

// Fill address from saved address button
function fillAddress(data) {
    document.getElementById('field-ship_name').value    = data.name    || '';
    document.getElementById('field-ship_phone').value   = data.phone   || '';
    document.getElementById('field-ship_address').value = data.address || '';

    const districtSelect = document.getElementById('district-select');
    for (let i = 0; i < districtSelect.options.length; i++) {
        if (districtSelect.options[i].value === data.district) {
            districtSelect.selectedIndex = i;
            break;
        }
    }
    // Trigger shipping rate fetch for the filled district
    fetchShippingRate(data.district);
}

// District change → fetch shipping rates
document.getElementById('district-select').addEventListener('change', function () {
    fetchShippingRate(this.value);
});

function showShippingPanel(which) {
    document.getElementById('shipping-placeholder').classList.toggle('hidden', which !== 'placeholder');
    document.getElementById('shipping-unavailable').classList.toggle('hidden', which !== 'unavailable');
    document.getElementById('shipping-rates').classList.toggle('hidden', which !== 'rates');
}

function selectRate(rateId, cost) {
    document.getElementById('shipping-rate-id').value = rateId;
    shippingCost = cost;

    // Highlight selected card
    document.querySelectorAll('.shipping-rate-card').forEach(card => {
        const selected = card.dataset.rateId == rateId;
        card.classList.toggle('border-primary-500', selected);
        card.classList.toggle('bg-primary-50', selected);
        card.classList.toggle('border-gray-200', !selected);
    });

    // Update summary
    const costEl  = document.getElementById('shipping-cost-display');
    const totalEl = document.getElementById('total-display');
    costEl.textContent  = cost > 0 ? '৳' + Math.round(cost).toLocaleString('en-US') : '{{ __('front.free_shipping') }}';
    costEl.classList.remove('text-gray-400');
    totalEl.textContent = '৳' + (subtotalBase + cost).toLocaleString('en-US', {maximumFractionDigits: 0});
}

function fetchShippingRate(district) {
    if (!district) return;

    // Reset
    document.getElementById('shipping-rate-id').value = '';
    shippingCost = 0;
    document.getElementById('shipping-cost-display').textContent = '{{ __('front.calculating') }}';
    document.getElementById('total-display').textContent = '৳' + subtotalBase.toLocaleString('en-US', {maximumFractionDigits: 0});
    showShippingPanel('placeholder');
    document.getElementById('shipping-placeholder').querySelector('span').textContent = '{{ __('front.calculating') }}';

    fetch('{{ route('checkout.shipping-rate') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ district }),
    })
    .then(r => r.json())
    .then(data => {
        if (!data.available || !data.rates.length) {
            showShippingPanel('unavailable');
            document.getElementById('shipping-cost-display').textContent = '—';
            return;
        }

        // Build rate cards
        const container = document.getElementById('shipping-rates');
        container.innerHTML = '';
        data.rates.forEach((rate, i) => {
            const costLabel = rate.cost > 0
                ? '৳' + Math.round(rate.cost).toLocaleString('en-US')
                : '{{ __('front.free_shipping') }}';
            const daysLabel = (rate.estimated_days_min !== null && rate.estimated_days_max !== null)
                ? (rate.estimated_days_min === rate.estimated_days_max
                    ? rate.estimated_days_min + ' day' + (rate.estimated_days_min !== 1 ? 's' : '')
                    : rate.estimated_days_min + '–' + rate.estimated_days_max + ' days')
                : '';

            const card = document.createElement('label');
            card.className = 'shipping-rate-card flex items-center justify-between border-2 border-gray-200 rounded-xl px-4 py-3 cursor-pointer transition-all hover:border-primary-300';
            card.dataset.rateId = rate.id;
            card.innerHTML = `
                <div class="flex items-center gap-3">
                    <input type="radio" name="_shipping_rate_radio" value="${rate.id}" class="accent-primary-600" ${i === 0 ? 'checked' : ''}>
                    <div>
                        <p class="text-sm font-semibold text-gray-800">${rate.method_name}</p>
                        ${daysLabel ? `<p class="text-xs text-gray-400">${daysLabel}</p>` : ''}
                    </div>
                </div>
                <span class="text-sm font-bold text-gray-900">${costLabel}</span>
            `;
            card.addEventListener('click', () => selectRate(rate.id, rate.cost));
            container.appendChild(card);
        });

        showShippingPanel('rates');
        // Auto-select first rate
        selectRate(data.rates[0].id, data.rates[0].cost);
    })
    .catch(() => {
        showShippingPanel('unavailable');
        document.getElementById('shipping-cost-display').textContent = '—';
    });
}

// Payment method toggle — show/hide panel AND enable/disable inputs to prevent duplicate name submission
function togglePaymentFields(method) {
    ['bkash', 'nagad', 'sslcommerz'].forEach(m => {
        const panel = document.getElementById('fields-' + m);
        if (!panel) return;
        const active = m === method;
        panel.classList.toggle('hidden', !active);
        panel.querySelectorAll('input[type="text"], input[type="file"]').forEach(input => {
            input.disabled = !active;
        });
    });
}

// Allow exactly one submission — covers double-clicks and Enter-key resubmits.
let checkoutSubmitted = false;
document.getElementById('checkout-form').addEventListener('submit', function (e) {
    if (checkoutSubmitted) {
        e.preventDefault();
        return;
    }
    checkoutSubmitted = true;

    const btn = document.getElementById('place-order-btn');
    btn.disabled = true;
    btn.innerHTML = '<svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> Processing...';
});

// Coupon apply via AJAX
const couponApplyBtn = document.getElementById('coupon-apply-btn');
if (couponApplyBtn) {
    couponApplyBtn.addEventListener('click', function () {
        const input = document.getElementById('coupon-input');
        const code  = input ? input.value.trim() : '';
        if (!code) return;

        couponApplyBtn.disabled = true;
        couponApplyBtn.textContent = '...';

        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        fetch('{{ route('checkout.apply-coupon') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ coupon_code: code }),
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Reload so the session-based coupon state reflects
                window.location.reload();
            } else {
                const msgDiv = document.getElementById('coupon-message');
                msgDiv.innerHTML = '<div class="bg-red-50 border border-red-200 text-red-600 rounded-lg px-3 py-2 text-xs mb-2">' + data.message + '</div>';
                couponApplyBtn.disabled = false;
                couponApplyBtn.textContent = '{{ __('front.apply_coupon') }}';
            }
        })
        .catch(() => {
            couponApplyBtn.disabled = false;
            couponApplyBtn.textContent = '{{ __('front.apply_coupon') }}';
        });
    });
}

// On page load: if district already selected (after validation error), fetch rate
document.addEventListener('DOMContentLoaded', function () {
    const districtSelect = document.getElementById('district-select');
    if (districtSelect.value) fetchShippingRate(districtSelect.value);
    // Show payment fields for old input
    const checkedMethod = document.querySelector('input[name="payment_method"]:checked');
    if (checkedMethod) togglePaymentFields(checkedMethod.value);
});
</script>
@endpush
