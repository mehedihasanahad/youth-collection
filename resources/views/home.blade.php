@extends('layouts.app')

@section('title', config('app.name') . ' — Modest Fashion')
@section('meta_description', 'Discover modest fashion that reflects your beauty and confidence. Shop hijab, abaya, dresses, shrug and more.')

@section('content')

{{-- ============================================================
    1. HERO SLIDER
============================================================ --}}
<section>
    <div class="swiper" id="heroSwiper">
        <div class="swiper-wrapper">

            @forelse($banners as $banner)
                <div class="swiper-slide">
                    <div class="hero-slide">
                        <img src="{{ asset('storage/' . $banner->image) }}"
                             alt="{{ $banner->title }}"
                             loading="{{ $loop->first ? 'eager' : 'lazy' }}">
                        @if($banner->title || $banner->subtitle || ($banner->button_text && $banner->button_url))
                            <div class="hero-slide-overlay"></div>
                            <div class="hero-slide-content">
                                @if($banner->title)
                                    <h1>{!! nl2br(e($banner->title)) !!}</h1>
                                @endif
                                @if($banner->subtitle)
                                    <p>{{ $banner->subtitle }}</p>
                                @endif
                                @if($banner->button_text && $banner->button_url)
                                    <a href="{{ $banner->button_url }}">{{ $banner->button_text }}</a>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="swiper-slide">
                    <div class="hero-slide" style="background: linear-gradient(135deg, #2d1b2e 0%, #1a0a14 100%);">
                        <div class="hero-slide-content">
                            <h1>Timeless<br>Elegance</h1>
                            <p>Modest fashion that reflects your beauty and confidence.</p>
                            <a href="{{ route('shop.category') }}">Explore Collection</a>
                        </div>
                    </div>
                </div>
            @endforelse

        </div>
        <div class="swiper-button-prev"></div>
        <div class="swiper-button-next"></div>
        <div class="swiper-pagination"></div>
    </div>
</section>

{{-- ============================================================
    2. VALUE PROPOSITIONS BAR
============================================================ --}}
<section class="bg-white border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 lg:grid-cols-4 divide-x divide-gray-100">

            @foreach([
                ['icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'title' => 'Premium Quality', 'sub' => 'Fabric You Can Trust'],
                ['icon' => 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15', 'title' => 'Easy Exchange', 'sub' => 'Hassle Free Returns'],
                ['icon' => 'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8l1 10a2 2 0 002 2h8a2 2 0 002-2L19 8', 'title' => 'Fast Delivery', 'sub' => 'Across Bangladesh'],
                ['icon' => 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z', 'title' => 'Secure Payment', 'sub' => '100% Safe & Secure'],
            ] as $prop)
                <div class="flex items-center gap-3 py-5 px-4 lg:px-6 justify-center lg:justify-start">
                    <div class="w-10 h-10 border border-primary-200 rounded-full flex items-center justify-center shrink-0">
                        <svg class="w-4.5 h-4.5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $prop['icon'] }}"/>
                        </svg>
                    </div>
                    <div class="hidden sm:block">
                        <p class="text-[11px] font-bold uppercase tracking-widest text-gray-800">{{ $prop['title'] }}</p>
                        <p class="text-[11px] text-gray-500 mt-0.5">{{ $prop['sub'] }}</p>
                    </div>
                    <p class="sm:hidden text-[10px] font-bold uppercase tracking-wide text-gray-800 text-center">{{ $prop['title'] }}</p>
                </div>
            @endforeach

        </div>
    </div>
</section>


{{-- ============================================================
    3. SHOP BY CATEGORY — grid
============================================================ --}}
@if($categories->isNotEmpty())
<section id="categories" class="py-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="text-left lg:text-center mb-6">
            <h2 class="text-lg font-black text-gray-900 uppercase leading-none">Shop By Category</h2>
            <div class="hidden lg:block w-10 h-0.5 bg-primary-600 mt-3 lg:mx-auto"></div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
            @foreach($categories as $category)
                @php
                    $t = $category->getTranslation(app()->getLocale()) ?? $category->getTranslation('en');
                    $catSlug = $t?->slug;
                @endphp
                <a href="{{ !empty($catSlug) ? route('shop.category', ['category' => $catSlug]) : '#' }}"
                   class="group relative overflow-hidden rounded-xl aspect-square block">
                    @if($category->image)
                        <img src="{{ asset('storage/' . $category->image) }}"
                             alt="{{ $t?->name ?? 'Category' }}"
                             class="absolute inset-0 w-full h-full object-cover transition-transform duration-300 group-hover:scale-105">
                    @else
                        <div class="absolute inset-0 bg-gray-200"></div>
                    @endif
                    <div class="absolute inset-0 bg-gradient-to-t from-black/75 via-black/20 to-transparent group-hover:from-primary-900/80 transition-colors duration-300"></div>
                    <div class="absolute bottom-0 left-0 right-0 p-3 text-center">
                        <p class="font-bold text-white text-xs uppercase tracking-widest leading-tight">{{ $t?->name ?? 'Category' }}</p>
                        <p class="text-primary-200 text-[11px] mt-1 italic font-light">Explore Now</p>
                    </div>
                </a>
            @endforeach
        </div>

    </div>
</section>
@endif

{{-- ============================================================
    4. NEW ARRIVALS — 2-column layout
============================================================ --}}
<section id="new-arrivals" class="py-10 bg-gray-50/60">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col lg:flex-row gap-10 items-start">

            {{-- Left: text + CTA --}}
            <div class="lg:w-56 shrink-0 lg:sticky lg:top-24 w-full">

                {{-- Mobile: one-line header row --}}
                <div class="flex items-center justify-between lg:hidden mb-4">
                    <div>
                        <p class="text-primary-600 text-[10px] font-bold uppercase tracking-widest leading-none mb-0.5">New</p>
                        <h2 class="text-lg font-black text-gray-900 uppercase leading-none">Arrivals</h2>
                    </div>
                    <a href="{{ route('shop.category', ['sort' => 'newest']) }}"
                       class="text-primary-600 text-[11px] font-bold uppercase tracking-widest hover:text-primary-700 transition-colors">
                        View All
                    </a>
                </div>

                {{-- Desktop: stacked column --}}
                <div class="hidden lg:block">
                    <p class="text-primary-600 text-xs font-bold uppercase tracking-widest mb-1">New</p>
                    <h2 class="text-5xl font-black text-gray-900 uppercase leading-none mb-4">Arrivals</h2>
                    <p class="text-sm text-gray-500 leading-relaxed mb-7">Discover our latest modest fashion pieces, designed for comfort and elegance.</p>
                    <a href="{{ route('shop.category', ['sort' => 'newest']) }}"
                       class="inline-block border border-gray-900 text-gray-900 text-[11px] font-bold uppercase tracking-widest px-5 py-2.5 hover:bg-gray-900 hover:text-white transition-colors duration-200">
                        View All
                    </a>
                </div>

            </div>

            {{-- Right: 1×4 product grid --}}
            @if($newArrivals->isNotEmpty())
                <div class="flex-1 new-arrivals-grid">
                    @foreach($newArrivals as $product)
                        @php
                            $pLocale       = app()->getLocale();
                            $pT            = $product->getTranslation($pLocale) ?? $product->getTranslation('en');
                            $pName         = $pT?->name ?? $product->sku;
                            $pSlug         = $pT?->slug ?? ($product->getTranslation('en')?->slug ?? $product->sku);
                            $pHasDiscount  = $product->is_on_sale;
                            $pCurrentPrice = $product->current_price;
                            $pIsWl         = auth()->check()
                                ? auth()->user()->wishlist()->where('product_id', $product->id)->exists()
                                : false;
                        @endphp
                        <div class="home-product-card group">
                            {{-- Image --}}
                            <a href="{{ route('product.show', $pSlug) }}"
                               class="home-product-card-img">
                                @if($product->primaryImage)
                                    <img src="{{ asset('storage/' . $product->primaryImage->path) }}"
                                         alt="{{ $pName }}"
                                         class="absolute inset-0 w-full h-full object-cover object-center group-hover:scale-105 transition-transform duration-500">
                                @else
                                    <div class="absolute inset-0 flex items-center justify-center bg-gray-100">
                                        <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                @endif

                                {{-- Badge top-left --}}
                                <div class="absolute top-2.5 left-2.5">
                                    @if($pHasDiscount)
                                        @php $pPct = round((($product->price - $product->sale_price) / $product->price) * 100); @endphp
                                        <span class="bg-red-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full">-{{ $pPct }}%</span>
                                    @else
                                        <span class="bg-primary-600 text-white text-[10px] font-bold px-2 py-0.5 rounded-full">NEW</span>
                                    @endif
                                </div>
                            </a>

                            {{-- Info --}}
                            <div class="p-3">
                                <a href="{{ route('product.show', $pSlug) }}" class="block">
                                    <h4 class="text-[13px] font-semibold text-gray-800 leading-snug mb-1.5"
                                        style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;min-height:2.4rem;">
                                        {{ $pName }}
                                    </h4>
                                </a>
                                <div class="flex items-center justify-between gap-1">
                                    <div>
                                        <span class="text-sm font-bold text-gray-900">৳{{ number_format($pCurrentPrice, 0) }}</span>
                                        @if($pHasDiscount)
                                            <span class="text-xs text-gray-400 line-through ml-1">৳{{ number_format($product->price, 0) }}</span>
                                        @endif
                                    </div>
                                    {{-- Wishlist --}}
                                    @auth
                                        <button type="button"
                                                onclick="toggleWishlist(this, {{ $product->id }})"
                                                data-product-id="{{ $product->id }}"
                                                data-wishlisted="{{ $pIsWl ? 'true' : 'false' }}"
                                                data-store-url="{{ route('wishlist.store') }}"
                                                data-destroy-url="{{ route('wishlist.destroy', $product->id) }}"
                                                class="shrink-0 w-7 h-7 flex items-center justify-center rounded-full border border-gray-200 hover:border-red-400 transition-colors {{ $pIsWl ? 'text-red-500 border-red-300' : 'text-gray-400' }}">
                                            <svg class="w-3.5 h-3.5" fill="{{ $pIsWl ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                            </svg>
                                        </button>
                                    @else
                                        <a href="{{ route('login') }}"
                                           class="shrink-0 w-7 h-7 flex items-center justify-center rounded-full border border-gray-200 text-gray-400 hover:text-red-500 hover:border-red-400 transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                            </svg>
                                        </a>
                                    @endauth
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="flex-1 text-center py-16 text-gray-400 text-sm">No new arrivals yet.</div>
            @endif

        </div>
    </div>
</section>

{{-- ============================================================
    5. PROMO BANNER — contained, admin-configurable
============================================================ --}}
@if($promoBanner['enabled'])
<section class="py-5">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="relative bg-gray-900 overflow-hidden rounded-xl" style="aspect-ratio:1200/450;">

            @php
                $promoHasContent = $promoBanner['headline'] || $promoBanner['label'] || $promoBanner['subtext'] || $promoBanner['button_text'];
            @endphp
            @if($promoBanner['image'])
                <img src="{{ $promoBanner['image'] }}"
                     alt=""
                     class="absolute inset-0 w-full h-full object-cover object-center">
                @if($promoHasContent)
                    <div class="absolute inset-0 bg-gradient-to-r from-gray-900/80 via-gray-900/50 to-transparent"></div>
                @endif
            @elseif($banners->isNotEmpty())
                <div class="absolute right-0 top-0 bottom-0 w-1/2 overflow-hidden pointer-events-none">
                    <img src="{{ asset('storage/' . $banners->first()->image) }}"
                         alt=""
                         class="absolute right-0 top-0 h-full w-auto object-cover object-top opacity-20">
                    <div class="absolute inset-0 bg-gradient-to-l from-transparent to-gray-900"></div>
                </div>
            @else
                <div class="absolute right-0 top-0 bottom-0 w-1/2 overflow-hidden pointer-events-none">
                    <div class="absolute -right-20 top-1/2 -translate-y-1/2 w-96 h-96 bg-primary-900/30 rounded-full blur-3xl"></div>
                </div>
            @endif

            @if($promoHasContent)
                <div class="absolute inset-0 flex flex-col justify-center px-6 sm:px-10 lg:px-16">
                    @if($promoBanner['label'])
                        <p class="text-gray-300 text-[9px] sm:text-[10px] uppercase tracking-widest font-semibold mb-0.5 sm:mb-1">{{ $promoBanner['label'] }}</p>
                    @endif
                    @if($promoBanner['headline'])
                        <h2 class="text-xl sm:text-3xl lg:text-5xl font-black text-white leading-none mb-0.5 sm:mb-2">{{ $promoBanner['headline'] }}</h2>
                    @endif
                    @if($promoBanner['subtext'])
                        <p class="text-gray-300 text-[9px] sm:text-[10px] uppercase tracking-widest font-semibold mb-2 sm:mb-5">{{ $promoBanner['subtext'] }}</p>
                    @endif
                    @if($promoBanner['button_text'])
                        @php $promoUrl = $promoBanner['button_url'] ?: route('shop.category', ['sort' => 'discount']); @endphp
                        <a href="{{ $promoUrl }}"
                           class="inline-block bg-primary-600 hover:bg-primary-700 text-white text-[8px] sm:text-[10px] font-bold uppercase tracking-widest px-3 sm:px-6 py-1.5 sm:py-2.5 transition-colors duration-200 self-start">
                            {{ $promoBanner['button_text'] }}
                        </a>
                    @endif
                </div>
            @endif

        </div>
    </div>
</section>
@endif

{{-- ============================================================
    6. BEST DEALS
============================================================ --}}
<section class="py-10 bg-gray-50/60">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Mobile: title left + View All right --}}
        <div class="flex items-center justify-between lg:hidden mb-6">
            <h2 class="text-lg font-black text-gray-900 uppercase leading-none">Best Deals</h2>
            <a href="{{ route('shop.category', ['sort' => 'discount']) }}"
               class="text-primary-600 text-[11px] font-bold uppercase tracking-widest hover:text-primary-700 transition-colors">
                View All
            </a>
        </div>

        {{-- Desktop: centered heading --}}
        <div class="hidden lg:block text-center mb-6">
            <h2 class="text-lg font-bold text-gray-900 uppercase tracking-widest">Best Deals</h2>
            <div class="w-10 h-0.5 bg-primary-600 mx-auto mt-3"></div>
        </div>

        @if($onSaleProducts->isNotEmpty())
            <div class="swiper best-deals-swiper">
                <div class="swiper-wrapper">
                    @foreach($onSaleProducts as $product)
                        @php
                            $dLocale = app()->getLocale();
                            $dT      = $product->getTranslation($dLocale) ?? $product->getTranslation('en');
                            $dName   = $dT?->name ?? $product->sku;
                            $dSlug   = $dT?->slug ?? ($product->getTranslation('en')?->slug ?? $product->sku);
                            $dPct    = round((($product->price - $product->sale_price) / $product->price) * 100);
                            $dIsWl   = auth()->check()
                                ? auth()->user()->wishlist()->where('product_id', $product->id)->exists()
                                : false;
                        @endphp
                        <div class="swiper-slide">
                            <div class="best-deals-card group border border-gray-100 rounded-xl overflow-hidden">
                                {{-- Image --}}
                                <a href="{{ route('product.show', $dSlug) }}" class="block relative overflow-hidden" style="aspect-ratio:1/1;">
                                    @if($product->primaryImage)
                                        <img src="{{ asset('storage/' . $product->primaryImage->path) }}"
                                             alt="{{ $dName }}"
                                             class="absolute inset-0 w-full h-full object-cover object-center">
                                    @else
                                        <div class="absolute inset-0 flex items-center justify-center bg-gray-100">
                                            <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                        </div>
                                    @endif
                                    <span class="absolute top-2.5 left-2.5 bg-red-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full">-{{ $dPct }}%</span>
                                </a>
                                {{-- Info --}}
                                <div class="p-3">
                                    <a href="{{ route('product.show', $dSlug) }}" class="block">
                                        <h4 class="text-[13px] font-semibold text-gray-800 leading-snug mb-1.5"
                                            style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;min-height:2.4rem;">
                                            {{ $dName }}
                                        </h4>
                                    </a>
                                    <div class="flex items-center justify-between gap-1">
                                        <div>
                                            <span class="text-sm font-bold text-gray-900">৳{{ number_format($product->sale_price, 0) }}</span>
                                            <span class="text-xs text-gray-400 line-through ml-1">৳{{ number_format($product->price, 0) }}</span>
                                        </div>
                                        @auth
                                            <button type="button"
                                                    onclick="toggleWishlist(this, {{ $product->id }})"
                                                    data-product-id="{{ $product->id }}"
                                                    data-wishlisted="{{ $dIsWl ? 'true' : 'false' }}"
                                                    data-store-url="{{ route('wishlist.store') }}"
                                                    data-destroy-url="{{ route('wishlist.destroy', $product->id) }}"
                                                    class="shrink-0 w-7 h-7 flex items-center justify-center rounded-full border border-gray-200 hover:border-red-400 transition-colors {{ $dIsWl ? 'text-red-500 border-red-300' : 'text-gray-400' }}">
                                                <svg class="w-3.5 h-3.5" fill="{{ $dIsWl ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                                </svg>
                                            </button>
                                        @else
                                            <a href="{{ route('login') }}"
                                               class="shrink-0 w-7 h-7 flex items-center justify-center rounded-full border border-gray-200 text-gray-400 hover:text-red-500 hover:border-red-400 transition-colors">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                                </svg>
                                            </a>
                                        @endauth
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="swiper-button-prev best-deals-swiper-prev"></div>
                <div class="swiper-button-next best-deals-swiper-next"></div>
            </div>

            <div class="hidden lg:block text-center mt-8">
                <a href="{{ route('shop.category', ['sort' => 'discount']) }}"
                   class="inline-block border border-gray-900 text-gray-900 text-[11px] font-bold uppercase tracking-widest px-5 py-2.5 hover:bg-gray-900 hover:text-white transition-colors duration-200">
                    View All
                </a>
            </div>
        @else
            <p class="text-center text-gray-400 py-12">No deals available right now.</p>
        @endif

    </div>
</section>

{{-- ============================================================
    7. CUSTOMER REVIEWS
============================================================ --}}
@if($testimonials->isNotEmpty())
<section class="py-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Mobile: title left --}}
        <div class="flex items-center justify-between lg:hidden mb-6">
            <h2 class="text-lg font-black text-gray-900 uppercase leading-none">Reviews</h2>
        </div>

        {{-- Desktop: centered heading --}}
        <div class="hidden lg:block text-center mb-6">
            <h2 class="text-lg font-bold text-gray-900 uppercase tracking-widest">Customer Reviews</h2>
            <div class="w-10 h-0.5 bg-primary-600 mx-auto mt-3"></div>
        </div>

        <div class="swiper reviews-swiper">
            <div class="swiper-wrapper">
                @foreach($testimonials as $review)
                    @php
                        $rPt = $review->product?->getTranslation(app()->getLocale())
                            ?? $review->product?->getTranslation('en');
                    @endphp
                    <div class="swiper-slide">
                        <div class="bg-white rounded-xl border border-gray-100 p-4 flex flex-col gap-3 h-full">

                            {{-- Stars --}}
                            <div class="flex gap-0.5">
                                @for($i = 1; $i <= 5; $i++)
                                    <svg class="w-3 h-3 {{ $i <= $review->rating ? 'text-amber-400' : 'text-gray-200' }}"
                                         fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                @endfor
                            </div>

                            {{-- Review text --}}
                            <p class="text-gray-600 text-[12px] leading-relaxed flex-1"
                               style="display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;">
                                {{ $review->comment }}
                            </p>

                            {{-- User + Product --}}
                            <div class="flex items-center gap-2.5 mt-auto pt-3 border-t border-gray-50">
                                <div class="w-7 h-7 rounded-full bg-primary-100 text-primary-600 flex items-center justify-center text-[10px] font-bold uppercase shrink-0">
                                    {{ mb_substr($review->user->name, 0, 1) }}
                                </div>
                                <div class="min-w-0">
                                    <p class="text-[11px] font-semibold text-gray-800 leading-none truncate">{{ $review->user->name }}</p>
                                    @if($rPt?->name)
                                        <p class="text-[10px] text-gray-400 leading-none mt-0.5 truncate">{{ $rPt->name }}</p>
                                    @endif
                                </div>
                            </div>

                        </div>
                    </div>
                @endforeach
            </div>
        </div>

    </div>
</section>
@endif

@endsection

