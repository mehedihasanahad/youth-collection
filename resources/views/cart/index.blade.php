@extends('layouts.app')

@section('title', __('front.your_cart') . ' — ' . config('app.name', 'ShopZone'))

@section('content')
@php $locale = app()->getLocale(); @endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    {{-- Page heading --}}
    <div class="flex items-center gap-3 mb-8">
        <h1 class="text-2xl font-bold text-gray-900">{{ __('front.your_cart') }}</h1>
        @if($cartItems->count() > 0)
            <span class="text-sm text-gray-500 font-normal">
                ({{ trans_choice('front.items_count', $cartItems->sum('quantity'), ['count' => $cartItems->sum('quantity')]) }})
            </span>
        @endif
    </div>

    @if($cartItems->isEmpty())
        {{-- ── Empty state ── --}}
        <div class="text-center py-20">
            <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-5">
                <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </div>
            <h2 class="text-xl font-semibold text-gray-800 mb-2">{{ __('front.cart_empty') }}</h2>
            <p class="text-gray-500 text-sm mb-8">{{ __('front.cart_empty_sub') }}</p>
            <a href="{{ route('home') }}"
               class="inline-flex items-center gap-2 bg-primary-600 hover:bg-primary-700 text-white font-semibold px-6 py-3 rounded-xl transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                {{ __('front.continue_shopping') }}
            </a>
        </div>
    @else
        <div class="lg:grid lg:grid-cols-3 lg:gap-10">

            {{-- ── Cart items (left 2/3) ── --}}
            <div class="lg:col-span-2 space-y-4 mb-8 lg:mb-0">

                {{-- Desktop table header --}}
                <div class="hidden md:grid grid-cols-12 text-xs font-semibold text-gray-400 uppercase tracking-wide px-4 pb-2 border-b border-gray-100">
                    <div class="col-span-5">Product</div>
                    <div class="col-span-2 text-center">Price</div>
                    <div class="col-span-3 text-center">Quantity</div>
                    <div class="col-span-2 text-right">Total</div>
                </div>

                @foreach($cartItems as $item)
                    @php
                        $t = $item->product->getTranslation($locale) ?? $item->product->getTranslation('en');
                        $productName = $t?->name ?? $item->product->sku;
                        $productSlug = $t?->slug ?? ($item->product->getTranslation('en')?->slug ?? $item->product->sku);
                        $unitPrice = $item->variant ? $item->variant->effective_price : $item->product->current_price;
                        $maxStock = $item->variant ? $item->variant->stock : $item->product->stock;
                    @endphp

                    <div class="bg-white border border-gray-100 rounded-2xl p-4">
                        {{-- Mobile: stacked, Desktop: grid --}}
                        <div class="flex gap-4 md:grid md:grid-cols-12 md:items-center">

                            {{-- Product info --}}
                            <div class="flex items-center gap-4 md:col-span-5 min-w-0">
                                {{-- Image --}}
                                <a href="{{ route('product.show', $productSlug) }}" class="shrink-0">
                                    @if($item->product->primaryImage)
                                        <img src="{{ asset('storage/' . $item->product->primaryImage->path) }}"
                                             alt="{{ $productName }}"
                                             class="w-16 h-16 object-cover rounded-xl border border-gray-100">
                                    @else
                                        <div class="w-16 h-16 bg-gray-100 rounded-xl flex items-center justify-center">
                                            <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                        </div>
                                    @endif
                                </a>
                                <div class="min-w-0">
                                    <a href="{{ route('product.show', $productSlug) }}"
                                       class="text-sm font-semibold text-gray-900 hover:text-primary-600 transition-colors line-clamp-2">
                                        {{ $productName }}
                                    </a>
                                    @if($item->variant && $item->variant->options->isNotEmpty())
                                        <div class="flex flex-wrap items-center gap-1.5 mt-0.5">
                                            @foreach($item->variant->options as $opt)
                                                @php $isColor = in_array(strtolower($opt->option_name), ['color', 'colour']); @endphp
                                                @if($isColor)
                                                    <span class="inline-flex items-center gap-1 text-xs text-gray-500">
                                                        {{ $opt->option_name }}:
                                                        <span class="w-4 h-4 rounded-full border border-gray-300 shrink-0"
                                                              style="background-color: {{ $opt->option_value }};"></span>
                                                    </span>
                                                @elseif($item->custom_size && strtolower($opt->option_name) === 'size')
                                                    <span class="text-xs text-gray-500">{{ $opt->option_name }}: {{ $item->custom_size }}</span>
                                                @else
                                                    <span class="text-xs text-gray-500">{{ $opt->option_name }}: {{ $opt->option_value }}</span>
                                                @endif
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>

                            {{-- Unit price (desktop) --}}
                            <div class="hidden md:block md:col-span-2 text-center text-sm font-medium text-gray-700">
                                ৳{{ number_format($unitPrice, 0) }}
                            </div>

                            {{-- Quantity + remove --}}
                            <div class="md:col-span-3 flex items-center justify-center gap-2">
                                <form method="POST" action="{{ route('cart.update', $item) }}" class="flex items-center border border-gray-200 rounded-xl overflow-hidden">
                                    @csrf
                                    @method('PATCH')
                                    <button type="button"
                                            onclick="const i=this.nextElementSibling;const v=Math.max(1,parseInt(i.value)-1);i.value=v;this.closest('form').submit()"
                                            class="w-9 h-9 flex items-center justify-center text-gray-600 hover:bg-gray-100 transition-colors font-bold text-base">
                                        −
                                    </button>
                                    <input type="number" name="quantity" value="{{ $item->quantity }}" min="1" max="{{ $maxStock }}"
                                           class="w-12 h-9 text-center text-sm font-semibold text-gray-900 border-x border-gray-200 focus:outline-none bg-white">
                                    <button type="button"
                                            onclick="const i=this.previousElementSibling;const v=Math.min({{ $maxStock }},parseInt(i.value)+1);i.value=v;this.closest('form').submit()"
                                            class="w-9 h-9 flex items-center justify-center text-gray-600 hover:bg-gray-100 transition-colors font-bold text-base">
                                        +
                                    </button>
                                </form>

                                {{-- Remove --}}
                                <form method="POST" action="{{ route('cart.destroy', $item) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            title="{{ __('front.remove') }}"
                                            onclick="return confirm('Remove this item?')"
                                            class="w-9 h-9 flex items-center justify-center text-gray-300 hover:text-red-500 transition-colors rounded-lg hover:bg-red-50">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>

                            {{-- Line total --}}
                            <div class="md:col-span-2 text-right">
                                <span class="text-sm font-bold text-gray-900">
                                    ৳{{ number_format($item->line_total, 0) }}
                                </span>
                                {{-- Mobile: show unit price --}}
                                <p class="text-xs text-gray-400 md:hidden">৳{{ number_format($unitPrice, 0) }} each</p>
                            </div>
                        </div>
                    </div>
                @endforeach

                {{-- Continue shopping link --}}
                <div class="pt-2">
                    <a href="{{ route('home') }}"
                       class="inline-flex items-center gap-2 text-sm text-primary-600 hover:text-primary-700 font-medium transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        {{ __('front.continue_shopping') }}
                    </a>
                </div>
            </div>

            {{-- ── Order summary (right 1/3) ── --}}
            <div class="lg:col-span-1">
                <div class="bg-white border border-gray-100 rounded-2xl p-6 sticky top-24">
                    <h2 class="text-base font-bold text-gray-900 mb-5">{{ __('front.order_summary') }}</h2>

                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between text-gray-600">
                            <span>{{ __('front.subtotal') }}</span>
                            <span class="font-medium text-gray-900">৳{{ number_format($subtotal, 0) }}</span>
                        </div>
                        <div class="flex justify-between text-gray-600">
                            <span>{{ __('front.shipping') }}</span>
                            <span class="text-gray-400 text-xs">{{ __('front.shipping_calc') }}</span>
                        </div>
                    </div>

                    <div class="border-t border-gray-100 my-4"></div>

                    <div class="flex justify-between text-base font-bold text-gray-900 mb-6">
                        <span>{{ __('front.total') }}</span>
                        <span>৳{{ number_format($subtotal, 0) }}</span>
                    </div>

                    <a href="{{ route('checkout.index') }}"
                       class="w-full bg-primary-600 hover:bg-primary-700 text-white font-semibold py-3.5 rounded-xl
                              text-sm flex items-center justify-center gap-2 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        {{ __('front.proceed_checkout') }}
                    </a>

                    {{-- Trust badges --}}
                    <div class="flex items-center justify-center gap-4 mt-5 pt-5 border-t border-gray-100">
                        <div class="flex items-center gap-1.5 text-xs text-gray-400">
                            <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                            Secure
                        </div>
                        <div class="flex items-center gap-1.5 text-xs text-gray-400">
                            <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                            </svg>
                            Easy Returns
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
