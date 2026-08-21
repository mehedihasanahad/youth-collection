@extends('layouts.app')

@php
    $productName = $currentTranslation?->name ?? $product->sku;
    $description = $currentTranslation?->description ?? '';
    $metaTitle   = $currentTranslation?->meta_title ?? $productName . ' — ' . config('app.name');
    $metaDesc    = $currentTranslation?->meta_description ?? $currentTranslation?->short_description ?? '';
    $categoryTranslation = $product->category?->getTranslation($locale) ?? $product->category?->getTranslation('en');
    $categorySlug = $categoryTranslation?->slug ?? '';
    $categoryName = $categoryTranslation?->name ?? '';
    $discountPct  = $product->is_on_sale
        ? round((($product->price - $product->sale_price) / $product->price) * 100)
        : 0;

    // Group variants by option_name for the selector UI
    $variantOptions = collect();
    if ($product->variants->isNotEmpty()) {
        foreach ($product->variants as $variant) {
            foreach ($variant->options as $opt) {
                $variantOptions->put($opt->option_name, $variantOptions->get($opt->option_name, collect())->push([
                    'variant_id'    => $variant->id,
                    'option_value'  => $opt->option_value,
                    'price'         => $variant->effective_price,
                    'stock'         => $variant->stock,
                    'is_active'     => $variant->is_active,
                ]));
            }
        }
    }

    // Merged image list: base product images + all variant images
    $allImages = $product->images->concat(
        $product->variants->flatMap(fn($v) => $v->images)->values()
    );
@endphp

@section('title', $metaTitle)
@section('meta_description', $metaDesc)
@if($currentTranslation?->meta_keywords)
@section('meta_keywords', $currentTranslation->meta_keywords)
@endif

@php
    $ogImage = $product->primaryImage?->image_path
        ? Storage::url($product->primaryImage->image_path)
        : asset('images/og-default.png');
    $productUrl = url()->current();
    $ogDesc = $metaDesc ?: ($currentTranslation?->short_description ?? '');
@endphp
@section('og_type', 'product')
@section('og_title', $metaTitle)
@section('og_description', $ogDesc)
@section('og_image', $ogImage)

@push('styles')
<style>
#thumb-strip::-webkit-scrollbar       { width: 3px; }
#thumb-strip::-webkit-scrollbar-track { background: transparent; }
#thumb-strip::-webkit-scrollbar-thumb { background: #ffffff; border-radius: 999px; }
</style>
<script type="application/ld+json">
{
    "@@context": "https://schema.org/",
    "@@type": "Product",
    "name": "{{ addslashes($productName) }}",
    "description": "{{ addslashes(strip_tags($ogDesc)) }}",
    "url": "{{ $productUrl }}",
    "sku": "{{ $product->sku }}",
    @if($product->primaryImage?->image_path)
    "image": "{{ $ogImage }}",
    @endif
    "offers": {
        "@@type": "Offer",
        "url": "{{ $productUrl }}",
        "priceCurrency": "{{ \App\Models\Setting::get('currency', 'BDT') }}",
        "price": "{{ number_format((float) $product->current_price, 2, '.', '') }}",
        "availability": "{{ $product->is_in_stock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock' }}",
        "seller": {
            "@@type": "Organization",
            "name": "{{ addslashes(config('app.name')) }}"
        }
    }
    @if($avgRating > 0)
    ,"aggregateRating": {
        "@@type": "AggregateRating",
        "ratingValue": "{{ number_format($avgRating, 1) }}",
        "reviewCount": "{{ $reviews->count() }}"
    }
    @endif
}
</script>
@endpush

@section('content')

{{-- ── Breadcrumb ──────────────────────────────────────────────────────────── --}}
<nav class="bg-gray-50 border-b border-gray-100 py-3">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <ol class="flex items-center gap-1.5 text-sm text-gray-500 flex-wrap">
            <li>
                <a href="{{ route('home') }}" class="hover:text-primary-600 transition-colors">
                    {{ __('front.home') }}
                </a>
            </li>
            <li><span class="text-gray-300">/</span></li>
            <li>
                <a href="{{ route('shop.category') }}" class="hover:text-primary-600 transition-colors">
                    {{ __('front.all_products') }}
                </a>
            </li>
            @if($categoryName)
                <li><span class="text-gray-300">/</span></li>
                <li>
                    <a href="{{ !empty($categorySlug) ? route('shop.category', ['category' => $categorySlug]) : route('shop.category') }}"
                       class="hover:text-primary-600 transition-colors">
                        {{ $categoryName }}
                    </a>
                </li>
            @endif
            <li><span class="text-gray-300">/</span></li>
            <li class="text-gray-800 font-medium truncate max-w-45 sm:max-w-xs">{{ $productName }}</li>
        </ol>
    </div>
</nav>

{{-- ── Flash: info (e.g. redirected from wishlist with variant products) ─── --}}
@if(session('info'))
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-5">
        <div class="flex items-center gap-3 bg-blue-50 border border-blue-200 text-blue-800 rounded-xl px-4 py-3 text-sm">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ session('info') }}
        </div>
    </div>
@endif

{{-- ── Main Product Block ──────────────────────────────────────────────────── --}}
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 lg:py-14">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 lg:gap-10 xl:gap-16">

        {{-- ── LEFT: Image Gallery ──────────────────────────────────────────── --}}
        <div class="flex sm:flex-row gap-3">

            {{-- Thumbnail strip — vertical column on desktop (left), wrapping rows on mobile (bottom) --}}
            @if($allImages->count() > 1)
                <div class="hidden sm:flex sm:flex-col sm:flex-nowrap gap-2 sm:overflow-y-auto shrink-0"
                     id="thumb-strip">
                    @foreach($allImages as $image)
                        <button onclick="goToSlide({{ $loop->index }})"
                                data-index="{{ $loop->index }}"
                                data-variant-id="{{ $image->variant_id ?? 'null' }}"
                                class="thumb-btn w-16 h-16 rounded-xl overflow-hidden border-2 transition-all duration-200 shrink-0
                                       {{ $loop->first ? 'border-primary-500' : 'border-gray-200 hover:border-primary-300' }}
                                       focus:outline-none focus:border-primary-500">
                            <img src="{{ asset('storage/' . $image->path) }}"
                                 alt="{{ $image->alt_text ?: $productName }}"
                                 class="w-full h-full object-cover object-center">
                        </button>
                    @endforeach
                </div>
            @endif

            {{-- Main image — Swiper for swipe/slide support --}}
            <div class="relative rounded-2xl overflow-hidden bg-gray-50 border border-gray-100 aspect-square flex-1 self-start">

                {{-- Discount badge --}}
                @if($product->is_on_sale)
                    <span class="absolute top-3 left-3 z-10 bg-red-500 text-white text-xs font-bold px-3 py-1 rounded-full">
                        -{{ $discountPct }}%
                    </span>
                @endif

                @if($allImages->isNotEmpty())
                    <div class="swiper main-image-swiper w-full h-full">
                        <div class="swiper-wrapper">
                            @foreach($allImages as $image)
                                <div class="swiper-slide">
                                    <img src="{{ asset('storage/' . $image->path) }}"
                                         alt="{{ $image->alt_text ?: $productName }}"
                                         class="w-full h-full object-cover object-center">
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Prev / Next navigation --}}
                    @if($allImages->count() > 1)
                        <button class="main-prev absolute left-3 top-1/2 -translate-y-1/2 z-10
                                       w-9 h-9 rounded-full flex items-center justify-center
                                       bg-white/80 backdrop-blur-sm border border-gray-200 shadow-md
                                       text-gray-700 hover:bg-primary-600 hover:text-white hover:border-primary-600
                                       transition-all duration-150">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </button>
                        <button class="main-next absolute right-3 top-1/2 -translate-y-1/2 z-10
                                       w-9 h-9 rounded-full flex items-center justify-center
                                       bg-white/80 backdrop-blur-sm border border-gray-200 shadow-md
                                       text-gray-700 hover:bg-primary-600 hover:text-white hover:border-primary-600
                                       transition-all duration-150">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    @endif
                @else
                    <div class="w-full h-full flex items-center justify-center bg-gray-100">
                        <svg class="w-24 h-24 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                                  d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                @endif
            </div>

        </div>

        {{-- ── RIGHT: Product Info ──────────────────────────────────────────── --}}
        <div class="flex flex-col gap-3 lg:gap-2">

            {{-- Product name --}}
            <h1 class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-900 leading-tight">{{ $productName }}</h1>

            {{-- Star rating summary --}}
            <div class="flex items-center gap-2">
                <div class="flex gap-0.5">
                    @for($i = 1; $i <= 5; $i++)
                        <svg class="w-4 h-4 {{ $i <= round($avgRating) ? 'text-amber-400' : 'text-gray-200' }}"
                             fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0
                                     00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0
                                     00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54
                                     1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0
                                     00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0
                                     00.951-.69l1.07-3.292z"/>
                        </svg>
                    @endfor
                </div>
                @if($reviewCount > 0)
                    <span class="text-sm text-gray-500">{{ number_format($avgRating, 1) }}</span>
                    <a href="#reviews" class="text-sm text-primary-600 hover:underline">
                        ({{ $reviewCount }} {{ $reviewCount === 1 ? 'review' : 'reviews' }})
                    </a>
                @else
                    <span class="text-sm text-gray-400">{{ __('front.no_reviews') }}</span>
                @endif
            </div>

            {{-- Price block --}}
            <div id="price-block" class="flex items-baseline gap-3 flex-wrap">
                @if($product->is_on_sale)
                    <span id="current-price" class="text-2xl font-extrabold text-primary-700">
                        ৳{{ number_format($product->current_price, 0) }}
                    </span>
                    <span class="text-base text-gray-400 line-through">
                        ৳{{ number_format($product->price, 0) }}
                    </span>
                    <span class="bg-red-100 text-red-600 text-xs font-bold px-2.5 py-1 rounded-full">
                        {{ __('front.discount_off', ['percent' => $discountPct]) }}
                    </span>
                @else
                    <span id="current-price" class="text-2xl font-extrabold text-gray-900">
                        ৳{{ number_format($product->current_price, 0) }}
                    </span>
                @endif
            </div>

            {{-- Short description --}}
            @if($currentTranslation?->short_description)
                <p class="text-gray-600 text-sm leading-relaxed">{{ $currentTranslation->short_description }}</p>
            @endif

            {{-- Stock badge --}}
            <div id="stock-badge">
                @if(! $product->is_in_stock)
                    <span class="inline-flex items-center gap-1.5 text-sm font-semibold text-red-600 bg-red-50 px-3 py-1.5 rounded-full">
                        <span class="w-2 h-2 bg-red-500 rounded-full"></span>
                        {{ __('front.out_of_stock') }}
                    </span>
                @elseif($product->is_low_stock)
                    <span class="inline-flex items-center gap-1.5 text-sm font-semibold text-amber-700 bg-amber-50 px-3 py-1.5 rounded-full">
                        <span class="w-2 h-2 bg-amber-500 rounded-full"></span>
                        {{ __('front.low_stock_n', ['count' => $product->stock]) }}
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 text-sm font-semibold text-emerald-700 bg-emerald-50 px-3 py-1.5 rounded-full">
                        <span class="w-2 h-2 bg-emerald-500 rounded-full"></span>
                        {{ __('front.in_stock') }}
                    </span>
                @endif
            </div>

            {{-- Variant Selector --}}
            @if($variantOptions->isNotEmpty())
                <div class="flex flex-col gap-4" id="variant-selector">
                    {{-- Embed variant data for JS --}}
                    @php
                        $variantsJson = json_encode($product->variants->map(function($v) {
                            return [
                                'id'      => $v->id,
                                'price'   => $v->effective_price,
                                'stock'   => $v->stock,
                                'label'   => $v->label,
                                'options' => $v->options->map(function($o) {
                                    return [
                                        'name'      => $o->option_name,
                                        'value'     => $o->option_value,
                                        'size_note' => $o->size_note,
                                    ];
                                })->values(),
                                'images'  => $v->images->map(fn($img) => asset('storage/' . $img->path))->values(),
                            ];
                        })->values());
                    @endphp
                    <script id="variants-data" type="application/json">{!! $variantsJson !!}</script>

                    @foreach($variantOptions as $optionName => $values)
                        @php
                            $isColorOption = in_array(strtolower($optionName), ['color', 'colour']);
                            $isSizeOption  = strtolower($optionName) === 'size';
                        @endphp
                        <div>
                            <p class="text-sm font-semibold text-gray-700 mb-2">{{ $optionName }}</p>
                            <div class="flex flex-wrap gap-2" data-option="{{ $optionName }}">
                                @foreach($values->unique('option_value') as $val)
                                    @if($isColorOption)
                                        <button type="button"
                                                onclick="selectVariantOption('{{ $optionName }}', '{{ $val['option_value'] }}', this)"
                                                class="variant-color-swatch w-8 h-8 rounded-full ring-1 ring-gray-300 ring-offset-2 transition-all duration-150
                                                       {{ ! $val['is_active'] || $val['stock'] <= 0 ? 'opacity-40 cursor-not-allowed' : 'hover:ring-primary-500 hover:ring-2' }}"
                                                style="background-color: {{ $val['option_value'] }};"
                                                title="{{ $val['option_value'] }}"
                                                data-option-name="{{ $optionName }}"
                                                data-option-value="{{ $val['option_value'] }}"
                                                {{ ! $val['is_active'] || $val['stock'] <= 0 ? 'disabled' : '' }}>
                                        </button>
                                    @else
                                        <button type="button"
                                                onclick="selectVariantOption('{{ $optionName }}', '{{ $val['option_value'] }}', this)"
                                                class="variant-pill px-4 py-2 text-sm border-2 rounded-lg font-medium transition-all duration-150
                                                       border-gray-200 text-gray-700 hover:border-primary-400 hover:text-primary-700
                                                       {{ ! $val['is_active'] || $val['stock'] <= 0 ? 'opacity-40 cursor-not-allowed line-through' : '' }}"
                                                data-option-name="{{ $optionName }}"
                                                data-option-value="{{ $val['option_value'] }}"
                                                {{ ! $val['is_active'] || $val['stock'] <= 0 ? 'disabled' : '' }}>
                                            {{ $val['option_value'] }}
                                        </button>
                                    @endif
                                @endforeach

                                {{-- Custom Size button — only in the Size group when product has custom_size_enabled --}}
                                @if($isSizeOption && $product->custom_size_enabled)
                                    <button type="button"
                                            onclick="selectVariantOption('{{ $optionName }}', '__custom__', this)"
                                            class="variant-pill px-4 py-2 text-sm border-2 rounded-lg font-medium transition-all duration-150
                                                   border-primary-200 text-primary-700 hover:border-primary-400"
                                            data-option-name="{{ $optionName }}"
                                            data-option-value="__custom__">
                                        ✏ Custom Size
                                    </button>
                                @endif
                            </div>

                            {{-- Exact size note — shown when customer selects a size that has a note --}}
                            @if($isSizeOption)
                                <div id="size-exact-note"
                                     class="hidden mt-2 text-xs text-amber-600 leading-relaxed">
                                </div>
                            @endif

                            {{-- Custom size input — shown when customer clicks the Custom Size button --}}
                            @if($isSizeOption && $product->custom_size_enabled)
                                <div id="custom-size-box" class="mt-3 hidden">
                                    <label class="text-xs font-semibold text-gray-600 mb-1 block">Enter your measurements</label>
                                    <input type="text"
                                           id="custom-size-input"
                                           placeholder="e.g. Length-0,Body-0,Sleeve-0"
                                           class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500 transition-colors">
                                    <p class="text-[11px] text-gray-400 mt-1">We'll tailor this item to your measurements.</p>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Quantity + Actions --}}
            <div class="flex flex-col gap-3 pt-1">

                {{-- Row 1: Qty selector + Wishlist --}}
                <div class="flex items-center gap-3">
                    @if($product->is_in_stock)
                        <div class="flex items-center border border-gray-200 rounded-xl overflow-hidden">
                            <button type="button" onclick="changeQty(-1)"
                                    class="w-11 h-11 flex items-center justify-center text-gray-600 hover:bg-gray-100 transition-colors text-lg font-bold">
                                −
                            </button>
                            <input id="qty-input" type="number" value="1" min="1" max="{{ $product->stock }}"
                                   oninput="syncQty(this)"
                                   class="w-14 h-11 text-center text-sm font-semibold text-gray-900 border-x border-gray-200 focus:outline-none focus:ring-2 focus:ring-primary-500/30 bg-white">
                            <button type="button" onclick="changeQty(1)"
                                    class="w-11 h-11 flex items-center justify-center text-gray-600 hover:bg-gray-100 transition-colors text-lg font-bold">
                                +
                            </button>
                        </div>
                    @endif

                    {{-- Wishlist --}}
                    @auth
                        <button type="button"
                                id="wishlist-detail-btn"
                                onclick="toggleWishlist(this, {{ $product->id }})"
                                data-product-id="{{ $product->id }}"
                                data-wishlisted="{{ $isWishlisted ? 'true' : 'false' }}"
                                data-store-url="{{ route('wishlist.store') }}"
                                data-destroy-url="{{ route('wishlist.destroy', $product->id) }}"
                                title="{{ $isWishlisted ? __('front.remove_from_wishlist') : __('front.add_to_wishlist') }}"
                                class="wishlist-btn w-12 h-12 flex items-center justify-center border-2 rounded-xl transition-all duration-150
                                       {{ $isWishlisted ? 'border-red-400 text-red-500' : 'border-gray-200 text-gray-400 hover:border-red-400 hover:text-red-500' }}">
                            <svg class="w-5 h-5" fill="{{ $isWishlisted ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0
                                         00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                            </svg>
                        </button>
                    @else
                        <a href="{{ route('login') }}"
                           title="{{ __('front.add_to_wishlist') }}"
                           class="w-12 h-12 flex items-center justify-center border-2 border-gray-200 rounded-xl
                                  text-gray-400 hover:border-red-400 hover:text-red-500 transition-all duration-150">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0
                                         00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                            </svg>
                        </a>
                    @endauth
                </div>

                {{-- Row 2 & 3: Add to Cart + Buy Now --}}
                <form method="POST" action="{{ route('cart.store') }}" class="w-full" id="atc-form">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <input type="hidden" name="variant_id" id="selected-variant-id" value="">
                    <input type="hidden" name="quantity" id="form-quantity" value="1">
                    <input type="hidden" name="custom_size" id="form-custom-size" value="">
                    <input type="hidden" name="buy_now" id="buy-now-flag" value="0">

                    <div class="flex flex-col sm:flex-row gap-2">
                        {{-- Add to Cart --}}
                        <button type="submit"
                                id="atc-btn"
                                onclick="document.getElementById('buy-now-flag').value='0'"
                                @if($variantOptions->isNotEmpty() || ! $product->is_in_stock) disabled @endif
                                class="flex-1 flex items-center justify-center gap-2 bg-gray-900
                                       {{ $product->is_in_stock && $variantOptions->isEmpty()
                                            ? 'hover:bg-black active:bg-black cursor-pointer'
                                            : 'opacity-50 cursor-not-allowed' }}
                                       text-white font-semibold py-3 rounded-xl transition-all duration-150 text-sm">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184
                                         1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            {{ __('front.add_to_cart') }}
                        </button>

                        {{-- Buy Now --}}
                        <button type="submit"
                                id="buy-now-btn"
                                onclick="document.getElementById('buy-now-flag').value='1'"
                                @if($variantOptions->isNotEmpty() || ! $product->is_in_stock) disabled @endif
                                class="flex-1 flex items-center justify-center gap-2 bg-primary-600
                                       {{ $product->is_in_stock && $variantOptions->isEmpty()
                                            ? 'hover:bg-primary-700 active:bg-primary-800 cursor-pointer'
                                            : 'opacity-50 cursor-not-allowed' }}
                                       text-white font-semibold py-3 rounded-xl transition-colors duration-150 text-sm">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            {{ __('front.buy_now') }}
                        </button>
                    </div>

                    @if($variantOptions->isNotEmpty())
                        <p id="variant-hint" class="text-xs text-amber-600 mt-1.5 text-center sm:text-left">
                            {{ __('front.select_options_hint') }}
                        </p>
                    @endif
                </form>

                @include('partials.product-chat-buttons')

            </div>

            {{-- SKU / Weight meta --}}
            <div class="flex flex-wrap gap-x-6 gap-y-1 text-xs text-gray-400 pt-1 border-t border-gray-100">
                @if($product->sku)
                    <span>{{ __('front.sku') }}: <span class="text-gray-600 font-medium">{{ $product->sku }}</span></span>
                @endif
                @if($product->weight)
                    <span>{{ __('front.weight') }}: <span class="text-gray-600 font-medium">{{ $product->weight }} kg</span></span>
                @endif
                @if($categoryName)
                    <span>{{ __('front.filter_categories') }}: <span class="text-gray-600 font-medium">{{ $categoryName }}</span></span>
                @endif
            </div>

        </div>
    </div>
</section>

{{-- ── Tabs: Description / Reviews ─────────────────────────────────────────── --}}
<section id="reviews" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-14">
    <div class="border border-gray-200 rounded-2xl overflow-hidden">

        {{-- Tab nav --}}
        <div class="flex border-b border-gray-200 bg-gray-50">
            <button onclick="switchTab('description')"
                    id="tab-description"
                    class="tab-btn px-6 py-4 text-sm font-semibold border-b-2 border-primary-600 text-primary-700 bg-white transition-all">
                {{ __('front.tab_description') }}
            </button>
            <button onclick="switchTab('reviews')"
                    id="tab-reviews"
                    class="tab-btn px-6 py-4 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-gray-800 transition-all">
                {{ __('front.tab_reviews', ['count' => $reviewCount]) }}
            </button>
        </div>

        {{-- Description panel --}}
        <div id="panel-description" class="p-6 sm:p-8">
            @if($description)
                <div class="rich-text">
                    {!! $description !!}
                </div>
            @else
                <p class="text-gray-400 text-sm">{{ __('front.no_description') }}</p>
            @endif
        </div>

        {{-- Reviews panel --}}
        <div id="panel-reviews" class="p-6 sm:p-8 hidden">

            @if($reviewCount > 0)
                {{-- Rating summary --}}
                <div class="flex flex-col sm:flex-row gap-8 mb-10 pb-10 border-b border-gray-100">

                    {{-- Overall score --}}
                    <div class="flex flex-col items-center justify-center text-center shrink-0 min-w-30">
                        <p class="text-6xl font-extrabold text-gray-900">{{ number_format($avgRating, 1) }}</p>
                        <div class="flex gap-0.5 mt-2 mb-1">
                            @for($i = 1; $i <= 5; $i++)
                                <svg class="w-4 h-4 {{ $i <= round($avgRating) ? 'text-amber-400' : 'text-gray-200' }}"
                                     fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0
                                             00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0
                                             00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54
                                             1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0
                                             00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0
                                             00.951-.69l1.07-3.292z"/>
                                </svg>
                            @endfor
                        </div>
                        <p class="text-xs text-gray-400">{{ $reviewCount }} {{ __('front.reviews_out_of') }}</p>
                    </div>

                    {{-- Rating bars --}}
                    <div class="flex-1 flex flex-col gap-2 justify-center">
                        @foreach($ratingCounts as $star => $count)
                            @php $pct = $reviewCount > 0 ? round(($count / $reviewCount) * 100) : 0; @endphp
                            <div class="flex items-center gap-3">
                                <span class="text-xs text-gray-500 w-4 shrink-0">{{ $star }}</span>
                                <svg class="w-3 h-3 text-amber-400 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0
                                             00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0
                                             00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54
                                             1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0
                                             00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0
                                             00.951-.69l1.07-3.292z"/>
                                </svg>
                                <div class="flex-1 bg-gray-100 rounded-full h-2 overflow-hidden">
                                    <div class="bg-amber-400 h-2 rounded-full transition-all duration-500"
                                         style="width: {{ $pct }}%"></div>
                                </div>
                                <span class="text-xs text-gray-400 w-8 text-right shrink-0">{{ $count }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Review list --}}
                <div class="flex flex-col gap-8">
                    @foreach($reviews as $review)
                        <div class="flex gap-4">
                            {{-- Avatar --}}
                            <div class="shrink-0 w-10 h-10 rounded-full bg-primary-100 flex items-center justify-center
                                        text-primary-700 font-bold text-sm uppercase select-none">
                                {{ strtoupper(substr($review->user?->name ?? 'A', 0, 1)) }}
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex flex-wrap items-center gap-2 mb-1">
                                    <span class="text-sm font-semibold text-gray-900">
                                        {{ $review->user?->name ?? 'Anonymous' }}
                                    </span>
                                    @if($review->is_verified_purchase)
                                        <span class="inline-flex items-center gap-1 text-[11px] font-semibold
                                                     text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0
                                                     01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0
                                                     011.414 0z" clip-rule="evenodd"/>
                                            </svg>
                                            {{ __('front.verified_purchase') }}
                                        </span>
                                    @endif
                                    <span class="text-xs text-gray-400 ml-auto">
                                        {{ $review->created_at->format('M d, Y') }}
                                    </span>
                                </div>

                                {{-- Stars --}}
                                <div class="flex gap-0.5 mb-2">
                                    @for($i = 1; $i <= 5; $i++)
                                        <svg class="w-3.5 h-3.5 {{ $i <= $review->rating ? 'text-amber-400' : 'text-gray-200' }}"
                                             fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0
                                                     00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0
                                                     00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54
                                                     1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0
                                                     00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0
                                                     00.951-.69l1.07-3.292z"/>
                                        </svg>
                                    @endfor
                                </div>

                                @if($review->title)
                                    <p class="text-sm font-semibold text-gray-800 mb-1">{{ $review->title }}</p>
                                @endif
                                @if($review->body)
                                    <p class="text-sm text-gray-600 leading-relaxed mb-3">{{ $review->body }}</p>
                                @endif

                                {{-- Helpful vote --}}
                                @auth
                                    @php $voted = in_array($review->id, $userVotedReviewIds); @endphp
                                    <button type="button"
                                            onclick="voteHelpful(this, {{ $review->id }})"
                                            data-review-id="{{ $review->id }}"
                                            data-voted="{{ $voted ? 'true' : 'false' }}"
                                            data-url="{{ route('reviews.vote', $review->id) }}"
                                            class="inline-flex items-center gap-1.5 text-xs px-2.5 py-1 rounded-full border transition-colors
                                                   {{ $voted ? 'border-primary-400 text-primary-600 bg-primary-50' : 'border-gray-200 text-gray-500 hover:border-primary-300 hover:text-primary-600' }}">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/>
                                        </svg>
                                        <span class="helpful-label">{{ __('front.helpful') }}</span>
                                        <span class="helpful-count {{ $review->helpful_count > 0 ? '' : 'hidden' }}">({{ $review->helpful_count }})</span>
                                    </button>
                                @endauth
                            </div>
                        </div>
                    @endforeach
                </div>

            @else
                <div class="text-center py-12">
                    <svg class="w-12 h-12 text-gray-200 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                    <p class="text-gray-400 text-sm">{{ __('front.no_reviews') }}</p>
                </div>
            @endif

            {{-- ── Write a Review form ──────────────────────────────────────── --}}
            @auth
                @if(!$hasReviewed)
                    <div class="mt-10 pt-8 border-t border-gray-100">
                        <h3 class="text-base font-semibold text-gray-900 mb-5">{{ __('front.write_review') }}</h3>

                        {{-- Flash messages --}}
                        @if(session('review_success'))
                            <div class="mb-4 flex items-center gap-3 bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm">
                                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                {{ session('review_success') }}
                            </div>
                        @endif
                        @if(session('review_error'))
                            <div class="mb-4 flex items-center gap-3 bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm">
                                {{ session('review_error') }}
                            </div>
                        @endif

                        <form action="{{ route('reviews.store', $product->id) }}" method="POST" class="space-y-5">
                            @csrf

                            {{-- Star rating picker --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('front.review_rating') }} <span class="text-red-500">*</span></label>
                                <div class="flex gap-1" id="star-picker">
                                    @for($s = 1; $s <= 5; $s++)
                                        <button type="button"
                                                onclick="setRating({{ $s }})"
                                                data-star="{{ $s }}"
                                                class="star-btn text-gray-300 hover:text-amber-400 transition-colors">
                                            <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0
                                                         00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0
                                                         00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54
                                                         1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0
                                                         00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0
                                                         00.951-.69l1.07-3.292z"/>
                                            </svg>
                                        </button>
                                    @endfor
                                </div>
                                <input type="hidden" name="rating" id="rating-value" value="{{ old('rating') }}">
                                @error('rating')
                                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Title --}}
                            <div>
                                <label for="review-title" class="block text-sm font-medium text-gray-700 mb-1">
                                    {{ __('front.review_title') }}
                                </label>
                                <input type="text" name="title" id="review-title"
                                       value="{{ old('title') }}"
                                       placeholder="{{ __('front.review_title_placeholder') }}"
                                       maxlength="150"
                                       class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm text-gray-800
                                              focus:outline-none focus:ring-2 focus:ring-primary-400 focus:border-transparent transition">
                            </div>

                            {{-- Body --}}
                            <div>
                                <label for="review-body" class="block text-sm font-medium text-gray-700 mb-1">
                                    {{ __('front.review_body') }} <span class="text-red-500">*</span>
                                </label>
                                <textarea name="body" id="review-body" rows="4"
                                          placeholder="{{ __('front.review_body_placeholder') }}"
                                          maxlength="2000"
                                          class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm text-gray-800
                                                 focus:outline-none focus:ring-2 focus:ring-primary-400 focus:border-transparent
                                                 transition resize-none">{{ old('body') }}</textarea>
                                @error('body')
                                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <button type="submit"
                                    class="inline-flex items-center gap-2 bg-primary-600 hover:bg-primary-700 text-white
                                           font-semibold text-sm px-6 py-2.5 rounded-xl transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                {{ __('front.review_submit') }}
                            </button>
                        </form>
                    </div>
                @else
                    <div class="mt-8 pt-6 border-t border-gray-100 text-sm text-gray-500 text-center">
                        {{ __('front.review_already_submitted') }}
                    </div>
                @endif
            @else
                <div class="mt-8 pt-6 border-t border-gray-100 text-center">
                    <a href="{{ route('login') }}"
                       class="text-sm text-primary-600 hover:text-primary-700 font-medium">
                        {{ __('front.review_login_to_write') }}
                    </a>
                </div>
            @endauth

        </div>
    </div>
</section>

{{-- ── Related Products ─────────────────────────────────────────────────────── --}}
@if($relatedProducts->isNotEmpty())
<section class="bg-primary-50/50 py-14">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-end justify-between mb-8">
            <div>
                <p class="text-primary-600 text-sm font-semibold uppercase tracking-wider mb-1">{{ $categoryName }}</p>
                <h2 class="text-2xl sm:text-3xl font-bold text-gray-900">{{ __('front.related_products') }}</h2>
            </div>
            <div class="flex gap-2">
                <button class="related-prev w-9 h-9 bg-white border border-gray-200 rounded-full flex items-center justify-center
                               text-primary-600 hover:bg-primary-600 hover:text-white hover:border-primary-600 transition-all shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>
                <button class="related-next w-9 h-9 bg-white border border-gray-200 rounded-full flex items-center justify-center
                               text-primary-600 hover:bg-primary-600 hover:text-white hover:border-primary-600 transition-all shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>
        </div>

        <div class="swiper related-swiper">
            <div class="swiper-wrapper pb-2">
                @foreach($relatedProducts as $related)
                    <div class="swiper-slide h-auto">
                        @include('partials.product-card', ['product' => $related, 'locale' => $locale])
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>
@endif

@endsection

@push('pixel-events')
<script>
if (typeof fbq !== 'undefined') {
    fbq('track', 'ViewContent', {
        content_name:  '{{ addslashes($productName) }}',
        content_ids:   ['{{ $product->id }}'],
        content_type:  'product',
        value:         {{ (float) $product->current_price }},
        currency:      'BDT'
    });

    document.querySelectorAll('form[action="{{ route('cart.store') }}"]').forEach(function (form) {
        form.addEventListener('submit', function () {
            fbq('track', 'AddToCart', {
                content_name: '{{ addslashes($productName) }}',
                content_ids:  ['{{ $product->id }}'],
                content_type: 'product',
                value:        {{ (float) $product->current_price }},
                currency:     'BDT'
            });
        });
    });
}
</script>
@endpush

@push('scripts')
<script>
let mainSwiper = null;

document.addEventListener('DOMContentLoaded', function () {

    // ── Sync thumbnail strip height to main image ──
    var strip   = document.getElementById('thumb-strip');
    var imgWrap = strip ? strip.nextElementSibling : null;
    if (strip && imgWrap) {
        function syncThumbHeight() {
            strip.style.maxHeight = imgWrap.offsetHeight + 'px';
        }
        syncThumbHeight();
        window.addEventListener('resize', syncThumbHeight);
    }

    // ── Main image Swiper ──
    @if($allImages->isNotEmpty())
    mainSwiper = new Swiper('.main-image-swiper', {
        loop: false,
        allowTouchMove: true,
        speed: 400,
        navigation: {
            nextEl: '.main-next',
            prevEl: '.main-prev',
        },
        on: {
            slideChange: function () {
                updateThumbActive(this.activeIndex);
            },
        },
    });
    @endif

    // ── Related Products Swiper ──
    @if($relatedProducts->isNotEmpty())
    new Swiper('.related-swiper', {
        slidesPerView: 1.2,
        spaceBetween: 16,
        grabCursor: true,
        navigation: {
            nextEl: '.related-next',
            prevEl: '.related-prev',
        },
        breakpoints: {
            480:  { slidesPerView: 2,   spaceBetween: 16 },
            768:  { slidesPerView: 3,   spaceBetween: 20 },
            1024: { slidesPerView: 4,   spaceBetween: 20 },
        },
    });
    @endif

});

// ── Gallery: navigate to slide by index ──
function goToSlide(index) {
    if (mainSwiper) mainSwiper.slideTo(index);
}

// ── Gallery: sync thumbnail highlight with active slide ──
function updateThumbActive(activeIndex) {
    document.querySelectorAll('#thumb-strip .thumb-btn').forEach((btn, i) => {
        if (i === activeIndex) {
            btn.classList.add('border-primary-500');
            btn.classList.remove('border-gray-200');
        } else {
            btn.classList.remove('border-primary-500');
            btn.classList.add('border-gray-200');
        }
    });
}

// ── Qty: clamp typed value and sync to hidden form input ──
function syncQty(input) {
    const min = parseInt(input.min) || 1;
    const max = parseInt(input.max) || 999;
    const val = Math.min(max, Math.max(min, parseInt(input.value) || min));
    input.value = val;
    const formQty = document.getElementById('form-quantity');
    if (formQty) formQty.value = val;
}

// ── Qty: increment/decrement ──
function changeQty(delta) {
    const input = document.getElementById('qty-input');
    if (!input) return;
    const min = parseInt(input.min) || 1;
    const max = parseInt(input.max) || 999;
    const val = Math.min(max, Math.max(min, parseInt(input.value || 1) + delta));
    input.value = val;
    const formQty = document.getElementById('form-quantity');
    if (formQty) formQty.value = val;
}

// ── Star rating picker ──
function setRating(value) {
    document.getElementById('rating-value').value = value;
    document.querySelectorAll('#star-picker .star-btn').forEach(btn => {
        const star = parseInt(btn.dataset.star);
        btn.classList.toggle('text-amber-400', star <= value);
        btn.classList.toggle('text-gray-300',  star > value);
    });
}

// ── Helpful vote ──
function voteHelpful(btn, reviewId) {
    fetch(btn.dataset.url, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        },
    })
    .then(res => res.json())
    .then(data => {
        const voted = data.voted;
        btn.dataset.voted = voted ? 'true' : 'false';
        if (voted) {
            btn.classList.add('border-primary-400', 'text-primary-600', 'bg-primary-50');
            btn.classList.remove('border-gray-200', 'text-gray-500');
        } else {
            btn.classList.remove('border-primary-400', 'text-primary-600', 'bg-primary-50');
            btn.classList.add('border-gray-200', 'text-gray-500');
        }
        const countEl = btn.querySelector('.helpful-count');
        if (countEl) {
            if (data.helpful_count > 0) {
                countEl.textContent = '(' + data.helpful_count + ')';
                countEl.classList.remove('hidden');
            } else {
                countEl.classList.add('hidden');
            }
        }
    });
}

// ── Tab switching ──
function switchTab(tab) {
    ['description', 'reviews'].forEach(t => {
        const panel = document.getElementById('panel-' + t);
        const btn   = document.getElementById('tab-' + t);
        if (t === tab) {
            panel.classList.remove('hidden');
            btn.classList.add('border-primary-600', 'text-primary-700', 'bg-white');
            btn.classList.remove('border-transparent', 'text-gray-500');
        } else {
            panel.classList.add('hidden');
            btn.classList.remove('border-primary-600', 'text-primary-700', 'bg-white');
            btn.classList.add('border-transparent', 'text-gray-500');
        }
    });
}

// Auto-open reviews tab if session flash or #reviews hash is present
document.addEventListener('DOMContentLoaded', () => {
    const openReviews = @json(session('review_success') || session('review_error'))
        || window.location.hash === '#reviews';
    if (openReviews) switchTab('reviews');
});

// Also handle the rating link click — switch tab before scrolling
document.querySelectorAll('a[href="#reviews"]').forEach(link => {
    link.addEventListener('click', () => switchTab('reviews'));
});

// ── Variant selection ──
@if($variantOptions->isNotEmpty())
const variantsData = JSON.parse(document.getElementById('variants-data').textContent);
const selectedOptions = {};
const totalOptionGroups = {{ $variantOptions->count() }};

function findBestVariant() {
    // Exclude __custom__ — it has no real variant row to match
    const effectiveOptions = Object.fromEntries(
        Object.entries(selectedOptions).filter(([, v]) => v !== '__custom__')
    );
    const effectiveKeys = Object.keys(effectiveOptions);

    if (effectiveKeys.length === 0) {
        return variantsData.find(v => v.stock > 0) || variantsData[0];
    }

    // 1. Exact match on non-custom options
    const exact = variantsData.find(v =>
        effectiveKeys.every(name =>
            v.options.some(o => o.name === name && o.value === effectiveOptions[name])
        )
    );
    if (exact) return exact;

    // 2. Partial match — variant satisfying the most non-custom selected options
    let best = null;
    let bestScore = 0;
    for (const v of variantsData) {
        const score = effectiveKeys.filter(name =>
            v.options.some(o => o.name === name && o.value === effectiveOptions[name])
        ).length;
        if (score > 0 && score > bestScore) {
            bestScore = score;
            best = v;
        }
    }
    return best;
}

function applyVariant(variant, allSelected = true) {
    const inStock = variant.stock > 0;

    // Update price
    const priceEl = document.getElementById('current-price');
    if (priceEl) {
        priceEl.textContent = '৳' + Math.round(variant.price).toLocaleString('en-US');
    }

    // Update stock badge
    const stockBadge = document.getElementById('stock-badge');
    if (stockBadge) {
        if (!inStock) {
            stockBadge.innerHTML = `<span class="inline-flex items-center gap-1.5 text-sm font-semibold text-red-600 bg-red-50 px-3 py-1.5 rounded-full"><span class="w-2 h-2 bg-red-500 rounded-full"></span>{{ __('front.out_of_stock') }}</span>`;
        } else if (variant.stock <= 5) {
            stockBadge.innerHTML = `<span class="inline-flex items-center gap-1.5 text-sm font-semibold text-amber-700 bg-amber-50 px-3 py-1.5 rounded-full"><span class="w-2 h-2 bg-amber-500 rounded-full"></span>{{ __('front.low_stock_n', ['count' => '${variant.stock}']) }}</span>`;
        } else {
            stockBadge.innerHTML = `<span class="inline-flex items-center gap-1.5 text-sm font-semibold text-emerald-700 bg-emerald-50 px-3 py-1.5 rounded-full"><span class="w-2 h-2 bg-emerald-500 rounded-full"></span>{{ __('front.in_stock') }}</span>`;
        }
    }

    // Update qty input: clamp current value to new max, reset to 1 if out of stock
    const qtyInput = document.getElementById('qty-input');
    if (qtyInput) {
        if (!inStock) {
            qtyInput.max = 1;
            qtyInput.value = 1;
            qtyInput.disabled = true;
        } else {
            qtyInput.max = variant.stock;
            qtyInput.value = Math.min(parseInt(qtyInput.value) || 1, variant.stock);
            qtyInput.disabled = false;
        }
        const formQty = document.getElementById('form-quantity');
        if (formQty) formQty.value = qtyInput.value;
    }

    // Store selected variant id
    const variantIdInput = document.getElementById('selected-variant-id');
    if (variantIdInput) variantIdInput.value = variant.id;

    // Disable/enable the ATC and Buy Now buttons — only enable when all groups selected AND in stock
    const canAdd = allSelected && inStock;

    const atcBtn = document.getElementById('atc-btn');
    if (atcBtn) {
        atcBtn.disabled = !canAdd;
        atcBtn.classList.toggle('opacity-50', !canAdd);
        atcBtn.classList.toggle('cursor-not-allowed', !canAdd);
        atcBtn.classList.toggle('hover:bg-black', canAdd);
        atcBtn.classList.toggle('active:bg-black', canAdd);
        atcBtn.classList.toggle('cursor-pointer', canAdd);
    }

    const buyNowBtn = document.getElementById('buy-now-btn');
    if (buyNowBtn) {
        buyNowBtn.disabled = !canAdd;
        buyNowBtn.classList.toggle('opacity-50', !canAdd);
        buyNowBtn.classList.toggle('cursor-not-allowed', !canAdd);
        buyNowBtn.classList.toggle('hover:bg-primary-700', canAdd);
        buyNowBtn.classList.toggle('active:bg-primary-800', canAdd);
        buyNowBtn.classList.toggle('cursor-pointer', canAdd);
    }

    // Hide the "select options" hint only when all groups are selected
    const hint = document.getElementById('variant-hint');
    if (hint) hint.style.display = allSelected ? 'none' : '';

    // Update main image when variant selected
    switchMainImageForVariant(variant.id, variant.images);
}

function switchMainImageForVariant(variantId, variantImages) {
    if (!mainSwiper || !variantImages || variantImages.length === 0) return;

    const targetSrc = variantImages[0];
    const slides = mainSwiper.slides;
    for (let i = 0; i < slides.length; i++) {
        const img = slides[i].querySelector('img');
        if (img && img.src === targetSrc) {
            mainSwiper.slideTo(i);
            return;
        }
    }
}

function selectVariantOption(optionName, optionValue, btn) {
    selectedOptions[optionName] = optionValue;

    // Reset all buttons in this group, then highlight the selected one
    document.querySelectorAll(`[data-option-name="${optionName}"]`).forEach(b => {
        if (b.classList.contains('variant-color-swatch')) {
            b.classList.remove('ring-primary-600', 'ring-2');
            b.classList.add('ring-gray-300', 'ring-1');
        } else {
            b.classList.remove('border-primary-600', 'bg-primary-50', 'text-primary-700');
            b.classList.add('border-gray-200', 'text-gray-700');
        }
    });

    if (btn.classList.contains('variant-color-swatch')) {
        btn.classList.remove('ring-gray-300', 'ring-1');
        btn.classList.add('ring-primary-600', 'ring-2');
    } else {
        btn.classList.add('border-primary-600', 'bg-primary-50', 'text-primary-700');
        btn.classList.remove('border-gray-200', 'text-gray-700');
    }

    // Show/hide exact size note
    if (optionName.toLowerCase() === 'size') {
        const noteEl = document.getElementById('size-exact-note');
        if (noteEl) {
            let note = null;
            if (optionValue !== '__custom__') {
                for (const v of variantsData) {
                    const opt = v.options.find(o => o.name === optionName && o.value === optionValue && o.size_note);
                    if (opt) { note = opt.size_note; break; }
                }
            }
            if (note) {
                noteEl.textContent = note;
                noteEl.classList.remove('hidden');
            } else {
                noteEl.classList.add('hidden');
            }
        }
    }

    // Show/hide custom size input box.
    // Only react when the selection is within the size group — clicking colour or
    // other options must NOT hide the box once custom size is active.
    const customBox = document.getElementById('custom-size-box');
    if (customBox && optionName.toLowerCase() === 'size') {
        if (optionValue === '__custom__') {
            customBox.classList.remove('hidden');
        } else {
            customBox.classList.add('hidden');
            const formCustomSize = document.getElementById('form-custom-size');
            if (formCustomSize) formCustomSize.value = '';
        }
    }

    const allSelected = Object.keys(selectedOptions).length >= totalOptionGroups;
    const matched = findBestVariant();
    if (matched) applyVariant(matched, allSelected);
}

// Sync custom size text to hidden field; require it on submit when __custom__ is chosen
document.addEventListener('DOMContentLoaded', function () {
    const customInput   = document.getElementById('custom-size-input');
    const formCustomSize = document.getElementById('form-custom-size');

    if (customInput && formCustomSize) {
        customInput.addEventListener('input', function () {
            formCustomSize.value = customInput.value.trim();
        });
    }

    const atcForm = document.getElementById('atc-form');
    if (atcForm) {
        atcForm.addEventListener('submit', function (e) {
            const hasCustom = Object.values(selectedOptions).includes('__custom__');
            if (hasCustom && !formCustomSize?.value.trim()) {
                e.preventDefault();
                if (customInput) {
                    customInput.focus();
                    customInput.classList.add('border-red-400', 'ring-1', 'ring-red-400');
                    setTimeout(() => customInput.classList.remove('border-red-400', 'ring-1', 'ring-red-400'), 2000);
                }
            }
        });
    }
});

@endif
</script>
@endpush
