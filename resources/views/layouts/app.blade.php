<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        $siteMetaTitle       = \App\Models\Setting::get('meta_title', config('app.name', 'Youth Collections'));
        $siteMetaDescription = \App\Models\Setting::get('meta_description', 'Modest fashion that reflects your beauty and confidence.');
        $siteMetaKeywords    = \App\Models\Setting::get('meta_keywords', '');
        $siteOgImagePath     = \App\Models\Setting::get('og_image', '');
        $siteOgImage         = $siteOgImagePath ? Storage::url($siteOgImagePath) : asset('images/og-default.png');
        $favicon             = \App\Models\Setting::get('site_favicon', '');
        $siteName            = \App\Models\Setting::get('site_name', config('app.name', 'Youth Collections'));
    @endphp

    <title>@yield('title', $siteMetaTitle)</title>
    <meta name="description" content="@yield('meta_description', $siteMetaDescription)">
    @hasSection('meta_keywords')
        <meta name="keywords" content="@yield('meta_keywords')">
    @elseif($siteMetaKeywords)
        <meta name="keywords" content="{{ $siteMetaKeywords }}">
    @endif
    <meta name="robots" content="@yield('robots', 'index, follow')">

    @if($favicon)
        <link rel="icon" href="{{ Storage::url($favicon) }}" type="image/png">
    @endif

    <meta property="og:type"        content="@yield('og_type', 'website')">
    <meta property="og:site_name"   content="{{ config('app.name') }}">
    <meta property="og:title"       content="@yield('og_title', $siteMetaTitle)">
    <meta property="og:description" content="@yield('og_description', $siteMetaDescription)">
    <meta property="og:url"         content="{{ url()->current() }}">
    <meta property="og:image"       content="@yield('og_image', $siteOgImage)">
    <meta property="og:locale"      content="{{ str_replace('-', '_', app()->getLocale()) }}">
    <meta name="twitter:card"        content="summary_large_image">
    <meta name="twitter:title"       content="@yield('og_title', $siteMetaTitle)">
    <meta name="twitter:description" content="@yield('og_description', $siteMetaDescription)">
    <meta name="twitter:image"       content="@yield('og_image', $siteOgImage)">
    <link rel="canonical" href="{{ url()->current() }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700,800&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@12/swiper-bundle.min.css"/>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
    @include('partials.facebook-pixel')
    @include('partials.google-analytics')
</head>
<body class="bg-white text-gray-900 antialiased">

{{-- ── Top announcement bar ── --}}
@php $trackUrl = auth()->check() ? route('orders.index') : route('login'); @endphp
<div class="bg-gray-950 text-gray-400 text-xs py-2 hidden sm:block">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between">
        <span class="text-gray-300">
            @if($announcementBarText)
                {!! $announcementBarText !!}
            @endif
        </span>
        <div class="flex items-center gap-4 text-xs">
            <a href="{{ route('page.contact') }}" class="hover:text-white transition-colors">Help &amp; Support</a>
            <span class="text-gray-700">|</span>
            <a href="{{ auth()->check() ? route('wishlist.index') : route('login') }}" class="hover:text-white transition-colors">
                Wishlist ({{ $wishlistCount }})
            </a>
            <div class="flex items-center gap-3 pl-3 border-l border-gray-800">
                @if($facebookUrl)
                <a href="{{ $facebookUrl }}" target="_blank" rel="noopener" class="hover:text-white transition-colors" aria-label="Facebook">
                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                    </svg>
                </a>
                @endif
                @if($instagramUrl)
                <a href="{{ $instagramUrl }}" target="_blank" rel="noopener" class="hover:text-white transition-colors" aria-label="Instagram">
                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                    </svg>
                </a>
                @endif
                @if($twitterUrl)
                <a href="{{ $twitterUrl }}" target="_blank" rel="noopener" class="hover:text-white transition-colors" aria-label="Twitter / X">
                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.747l7.73-8.835L1.254 2.25H8.08l4.259 5.629 5.905-5.629zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                    </svg>
                </a>
                @endif
                @if($youtubeUrl)
                <a href="{{ $youtubeUrl }}" target="_blank" rel="noopener" class="hover:text-white transition-colors" aria-label="YouTube">
                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                    </svg>
                </a>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ── Main header ── --}}
<header class="bg-white border-b border-gray-100 sticky top-0 z-50 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="relative flex items-center justify-between h-14">

            {{-- Left: hamburger (mobile) | logo (desktop) --}}
            <div class="flex items-center">
                <button id="mobile-menu-toggle" type="button" aria-label="Menu"
                        class="lg:hidden text-gray-600 hover:text-primary-600 transition-colors p-1.5">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                @php $siteLogo = \App\Models\Setting::get('site_logo'); @endphp
                <a href="{{ route('home') }}" class="hidden lg:flex shrink-0 items-center">
                    @if($siteLogo)
                        <img src="{{ asset('storage/' . $siteLogo) }}"
                             alt="{{ config('app.name', 'Youth Collections') }}"
                             class="h-18 w-auto object-contain">
                    @else
                        <img src="{{ asset('images/youthcollection-logo.png') }}"
                             alt="{{ config('app.name', 'Youth Collections') }}"
                             class="h-18 w-auto object-contain">
                    @endif
                </a>
            </div>

            {{-- Mobile center logo --}}
            <a href="{{ route('home') }}" class="lg:hidden absolute left-1/2 -translate-x-1/2 shrink-0 flex items-center">
                @if($siteLogo)
                    <img src="{{ asset('storage/' . $siteLogo) }}"
                         alt="{{ config('app.name', 'Youth Collections') }}"
                         class="h-14 w-auto object-contain">
                @else
                    <img src="{{ asset('images/youthcollection-logo.png') }}"
                         alt="{{ config('app.name', 'Youth Collections') }}"
                         class="h-14 w-auto object-contain">
                @endif
            </a>

            {{-- Desktop nav --}}
            <nav class="hidden lg:flex items-center gap-5 text-[11px] font-semibold tracking-widest uppercase text-gray-700">
                <a href="{{ route('home') }}"
                   class="{{ request()->routeIs('home') ? 'text-primary-600 border-b border-primary-600 pb-0.5' : 'hover:text-primary-600 transition-colors' }}">
                    Home
                </a>
                <a href="{{ route('shop.category', ['sort' => 'newest']) }}"
                   class="{{ request('sort') === 'newest' ? 'text-primary-600' : 'hover:text-primary-600 transition-colors' }}">
                    New Arrivals
                </a>
                {{-- Admin-managed categories --}}
                @foreach($navCategories as $navCat)
                    @php
                        $navT = $navCat->getTranslation(app()->getLocale()) ?? $navCat->getTranslation('en');
                        $navSlug = $navT?->slug ?? '';
                    @endphp
                    <a href="{{ !empty($navSlug) ? route('shop.category', ['category' => $navSlug]) : '#' }}"
                       class="{{ request('category') === $navSlug && !empty($navSlug) ? 'text-primary-600' : 'hover:text-primary-600 transition-colors' }}">
                        {{ $navT?->name ?? 'Category' }}
                    </a>
                @endforeach
            </nav>

            {{-- Right icons --}}
            <div class="flex items-center gap-1">

                {{-- Search — always visible --}}
                <button id="mobile-search-toggle" type="button" aria-label="Search"
                        class="text-gray-600 hover:text-primary-600 transition-colors p-1.5">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </button>

                {{-- User — desktop only --}}
                @auth
                    <div class="relative group hidden lg:block">
                        <button class="text-gray-600 hover:text-primary-600 transition-colors p-1.5" aria-label="Account">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </button>
                        <div class="absolute right-0 top-full mt-2 w-48 bg-white border border-gray-100 rounded-xl shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-150 z-50">
                            <a href="{{ route('account.index') }}" class="block px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 rounded-t-xl">My Account</a>
                            <a href="{{ $trackUrl }}" class="block px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50">My Orders</a>
                            <a href="{{ route('account.addresses') }}" class="block px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50">Address Book</a>
                            <div class="border-t border-gray-100"></div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="block w-full text-left px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 rounded-b-xl">
                                    Sign Out
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="hidden lg:flex text-gray-600 hover:text-primary-600 transition-colors p-1.5" aria-label="Sign in">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </a>
                @endauth

                {{-- Wishlist — desktop only --}}
                <a href="{{ auth()->check() ? route('wishlist.index') : route('login') }}"
                   class="hidden lg:flex text-gray-600 hover:text-primary-600 transition-colors p-1.5 relative" aria-label="Wishlist">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                    </svg>
                    <span class="wishlist-badge absolute -top-0.5 -right-0.5 bg-primary-600 text-white text-[9px] font-bold w-4 h-4 rounded-full flex items-center justify-center leading-none {{ $wishlistCount > 0 ? '' : 'hidden' }}">
                        {{ $wishlistCount > 9 ? '9+' : $wishlistCount }}
                    </span>
                </a>

                {{-- Cart — desktop only --}}
                <a href="{{ route('cart.index') }}"
                   class="hidden lg:flex text-gray-600 hover:text-primary-600 transition-colors p-1.5 relative" aria-label="Cart">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                    @if($cartCount > 0)
                        <span class="absolute -top-0.5 -right-0.5 bg-primary-600 text-white text-[9px] font-bold w-4 h-4 rounded-full flex items-center justify-center leading-none">
                            {{ $cartCount > 9 ? '9+' : $cartCount }}
                        </span>
                    @endif
                </a>
            </div>
        </div>
    </div>

    {{-- Search dropdown (shared for mobile + desktop) --}}
    <div id="mobile-search-bar" class="hidden border-t border-gray-100 bg-white px-4 py-3">
        <form method="GET" action="{{ route('shop.search') }}" role="search">
            <div class="relative max-w-xl mx-auto">
                <input type="text" name="q" value="{{ request('q') }}"
                       placeholder="Search products..."
                       id="mobile-search-input" autocomplete="off" autofocus
                       class="w-full pl-4 pr-10 py-2.5 border border-gray-200 rounded-full text-sm
                              bg-gray-50 focus:bg-white focus:border-primary-400 focus:ring-2
                              focus:ring-primary-100 focus:outline-none transition-all">
                <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-primary-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </button>
            </div>
        </form>
    </div>

</header>

{{-- Mobile drawer overlay --}}
<div id="drawer-overlay" class="lg:hidden fixed inset-0 bg-black/50 z-40 hidden"></div>

{{-- Mobile left drawer --}}
<div id="mobile-nav"
     class="lg:hidden fixed top-0 left-0 h-full w-72 max-w-[85vw] bg-white z-50 shadow-2xl -translate-x-full transition-transform duration-300 ease-in-out flex flex-col">

    {{-- Drawer header --}}
    <div class="flex items-center justify-between px-5 h-14 border-b border-gray-100 shrink-0">
        <a href="{{ route('home') }}">
            @if($siteLogo)
                <img src="{{ asset('storage/' . $siteLogo) }}" alt="{{ config('app.name') }}" class="h-8 w-auto object-contain">
            @else
                <img src="{{ asset('images/youthcollection-logo.png') }}" alt="{{ config('app.name') }}" class="h-8 w-auto object-contain">
            @endif
        </a>
        <button id="drawer-close" type="button" aria-label="Close menu"
                class="text-gray-400 hover:text-gray-700 transition-colors p-1.5">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    {{-- Nav links --}}
    <nav class="flex-1 overflow-y-auto px-5 py-2">
        <a href="{{ route('home') }}"
           class="flex items-center py-3.5 border-b border-gray-50 text-[11px] font-bold uppercase tracking-widest transition-colors {{ request()->routeIs('home') ? 'text-primary-600' : 'text-gray-700 hover:text-primary-600' }}">
            Home
        </a>
        <a href="{{ route('shop.category', ['sort' => 'newest']) }}"
           class="flex items-center py-3.5 border-b border-gray-50 text-[11px] font-bold uppercase tracking-widest text-gray-700 hover:text-primary-600 transition-colors">
            New Arrivals
        </a>
        @foreach($navCategories as $navCat)
            @php
                $mNavT    = $navCat->getTranslation(app()->getLocale()) ?? $navCat->getTranslation('en');
                $mNavSlug = $mNavT?->slug ?? '';
            @endphp
            <a href="{{ !empty($mNavSlug) ? route('shop.category', ['category' => $mNavSlug]) : '#' }}"
               class="flex items-center py-3.5 border-b border-gray-50 text-[11px] font-bold uppercase tracking-widest transition-colors {{ request('category') === $mNavSlug && !empty($mNavSlug) ? 'text-primary-600' : 'text-gray-700 hover:text-primary-600' }}">
                {{ $mNavT?->name ?? 'Category' }}
            </a>
        @endforeach
    </nav>

    {{-- Auth footer --}}
    <div class="px-5 py-4 border-t border-gray-100 shrink-0" style="padding-bottom: calc(1rem + env(safe-area-inset-bottom, 0px) + 3.5rem)">
        @auth
            <a href="{{ route('account.index') }}"
               class="flex items-center gap-2.5 text-sm font-semibold text-gray-700 hover:text-primary-600 transition-colors mb-3">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                My Account
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-xs text-red-500 hover:text-red-700 transition-colors uppercase tracking-widest font-semibold">
                    Sign Out
                </button>
            </form>
        @else
            <div class="flex gap-2">
                <a href="{{ route('login') }}"
                   class="flex-1 text-center text-[11px] font-bold uppercase tracking-widest py-2.5 border border-gray-300 text-gray-700 hover:border-primary-600 hover:text-primary-600 transition-colors rounded">
                    Sign In
                </a>
                <a href="{{ route('register') }}"
                   class="flex-1 text-center text-[11px] font-bold uppercase tracking-widest py-2.5 bg-primary-600 text-white hover:bg-primary-700 transition-colors rounded">
                    Register
                </a>
            </div>
        @endauth
    </div>
</div>

{{-- Flash messages --}}
@if(session('cart_success'))
    <div class="bg-green-50 border-b border-green-200 text-green-800 text-sm px-4 py-3 text-center">
        {{ session('cart_success') }}
    </div>
@endif
@if(session('cart_error'))
    <div class="bg-red-50 border-b border-red-200 text-red-800 text-sm px-4 py-3 text-center">
        {{ session('cart_error') }}
    </div>
@endif

{{-- Main content --}}
<main class="pb-16 lg:pb-0">
    @yield('content')
</main>

{{-- ── Footer ── --}}
<footer class="bg-gray-950 text-gray-400 pb-16 lg:pb-0">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-10">

            {{-- Col 1: Brand --}}
            <div class="sm:col-span-2 lg:col-span-1">
                <a href="{{ route('home') }}" class="inline-block mb-3">
                    <img src="{{ asset('images/youthcollection-logo.png') }}"
                         alt="{{ config('app.name') }}"
                         class="h-20 w-auto">
                </a>
                <p class="text-white text-xs font-bold uppercase tracking-widest mb-2">{{ $siteName }}</p>
                <p class="text-xs leading-relaxed mb-5">Choose well, be well. We bring you modest fashion that makes you feel confident and elegant every day.</p>
                <div class="flex items-center gap-3">
                    @if($facebookUrl)
                    <a href="{{ $facebookUrl }}" target="_blank" rel="noopener" class="text-gray-500 hover:text-white transition-colors" aria-label="Facebook">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                        </svg>
                    </a>
                    @endif
                    @if($instagramUrl)
                    <a href="{{ $instagramUrl }}" target="_blank" rel="noopener" class="text-gray-500 hover:text-white transition-colors" aria-label="Instagram">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                        </svg>
                    </a>
                    @endif
                    @if($twitterUrl)
                    <a href="{{ $twitterUrl }}" target="_blank" rel="noopener" class="text-gray-500 hover:text-white transition-colors" aria-label="Twitter / X">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.747l7.73-8.835L1.254 2.25H8.08l4.259 5.629 5.905-5.629zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                        </svg>
                    </a>
                    @endif
                    @if($youtubeUrl)
                    <a href="{{ $youtubeUrl }}" target="_blank" rel="noopener" class="text-gray-500 hover:text-white transition-colors" aria-label="YouTube">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                        </svg>
                    </a>
                    @endif
                </div>
            </div>

            {{-- Col 2: Newsletter --}}
            <div>
                <h4 class="text-white text-xs font-bold uppercase tracking-widest mb-4">Newsletter</h4>
                <p class="text-xs leading-relaxed mb-4">Subscribe to get special offers, free giveaways, and once-in-a-lifetime deals.</p>
                <form class="subscribe-form" data-url="{{ route('subscribe.store') }}">
                    @csrf
                    <div class="flex">
                        <input type="email" name="email" required
                               placeholder="Enter your email"
                               class="subscribe-input flex-1 min-w-0 text-xs px-3 py-2.5 rounded-l bg-gray-900 border border-primary-600 border-r-0 text-white placeholder-gray-600 focus:outline-none transition-colors">
                        <button type="submit"
                                class="subscribe-btn shrink-0 bg-primary-600 hover:bg-primary-700 text-white text-xs px-3 py-2.5 rounded-r font-bold uppercase tracking-wider flex items-center justify-center transition-colors">
                            <span class="subscribe-btn-label">Subscribe</span>
                            <svg class="subscribe-spinner" style="display:none;width:14px;height:14px;animation:subscribe-spin .8s linear infinite" fill="none" viewBox="0 0 24 24">
                                <circle style="opacity:.3" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
                                <path style="opacity:.9" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="subscribe-feedback mt-2 items-center gap-2 text-xs rounded px-3 py-2" style="display:none">
                        <svg class="subscribe-feedback-icon w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"></svg>
                        <span class="subscribe-feedback-text"></span>
                    </div>
                </form>
            </div>

            {{-- Col 3: Quick Links (merged essential links) --}}
            <div>
                <h4 class="text-white text-xs font-bold uppercase tracking-widest mb-4">Quick Links</h4>
                <ul class="space-y-2.5 text-xs">
                    <li><a href="{{ route('page.about') }}" class="hover:text-white transition-colors">About Us</a></li>
                    <li><a href="{{ route('shop.category', ['sort' => 'newest']) }}" class="hover:text-white transition-colors">New Arrivals</a></li>
                    <li><a href="{{ route('page.terms') }}" class="hover:text-white transition-colors">Terms &amp; Conditions</a></li>
                    {{-- <li><a href="{{ $trackUrl }}" class="hover:text-white transition-colors">Track Your Order</a></li> --}}
                    <li><a href="{{ route('page.faq') }}" class="hover:text-white transition-colors">Help Center</a></li>
                    <li><a href="{{ route('page.privacy') }}" class="hover:text-white transition-colors">Privacy Policy</a></li>
                </ul>
            </div>

            {{-- Col 4: Contact --}}
            <div>
                <h4 class="text-white text-xs font-bold uppercase tracking-widest mb-4">Contact Us</h4>
                <ul class="space-y-3.5 text-xs">
                    <li class="flex items-start gap-2">
                        <svg class="w-3.5 h-3.5 mt-0.5 shrink-0 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        @if($sitePhone)<span>{{ $sitePhone }}</span>@endif
                    </li>
                    <li class="flex items-start gap-2">
                        <svg class="w-3.5 h-3.5 mt-0.5 shrink-0 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        @if($siteEmail)<span>{{ $siteEmail }}</span>@endif
                    </li>
                    <li class="flex items-start gap-2">
                        <svg class="w-3.5 h-3.5 mt-0.5 shrink-0 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        @if($siteAddress)<span>{{ $siteAddress }}</span>@endif
                    </li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Bottom bar --}}
    <div class="border-t border-gray-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 text-center">
            <p class="text-xs text-gray-600">&copy; {{ date('Y') }} {{ config('app.name', 'Youth Collections') }}. All Rights Reserved.</p>
        </div>
    </div>
</footer>

{{-- Wishlist AJAX toggle --}}
<script>
function toggleWishlist(btn, productId) {
    const wishlisted = btn.dataset.wishlisted === 'true';
    const url = wishlisted ? btn.dataset.destroyUrl : btn.dataset.storeUrl;
    const method = wishlisted ? 'DELETE' : 'POST';
    const body = wishlisted ? null : JSON.stringify({ product_id: productId });

    fetch(url, {
        method,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        },
        body,
    })
    .then(res => res.json())
    .then(data => {
        const nowWishlisted = data.in_wishlist;
        btn.dataset.wishlisted = nowWishlisted ? 'true' : 'false';
        const svg = btn.querySelector('svg');
        if (svg) svg.setAttribute('fill', nowWishlisted ? 'currentColor' : 'none');
        if (nowWishlisted) { btn.classList.add('text-red-500'); btn.classList.remove('text-gray-400'); }
        else               { btn.classList.remove('text-red-500'); btn.classList.add('text-gray-400'); }
        const badge = document.querySelector('.wishlist-badge');
        if (badge) {
            if (data.wishlist_count > 0) {
                badge.textContent = data.wishlist_count > 9 ? '9+' : data.wishlist_count;
                badge.classList.remove('hidden');
            } else {
                badge.classList.add('hidden');
            }
        }
    });
}
</script>

{{-- Search + drawer toggles --}}
<script>
(function () {
    var searchToggle = document.getElementById('mobile-search-toggle');
    var searchBar    = document.getElementById('mobile-search-bar');
    var searchInput  = document.getElementById('mobile-search-input');
    var menuToggle   = document.getElementById('mobile-menu-toggle');
    var drawer       = document.getElementById('mobile-nav');
    var overlay      = document.getElementById('drawer-overlay');
    var drawerClose  = document.getElementById('drawer-close');

    function openDrawer() {
        drawer.classList.remove('-translate-x-full');
        overlay.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        if (searchBar) searchBar.classList.add('hidden');
    }

    function closeDrawer() {
        drawer.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
        document.body.style.overflow = '';
    }

    if (menuToggle)   menuToggle.addEventListener('click', openDrawer);
    if (drawerClose)  drawerClose.addEventListener('click', closeDrawer);
    if (overlay)      overlay.addEventListener('click', closeDrawer);

    if (searchToggle && searchBar) {
        searchToggle.addEventListener('click', function () {
            var hidden = searchBar.classList.contains('hidden');
            searchBar.classList.toggle('hidden', !hidden);
            closeDrawer();
            if (hidden && searchInput) searchInput.focus();
        });
    }

    document.addEventListener('click', function (e) {
        if (searchBar && !searchBar.classList.contains('hidden') &&
            searchToggle && !searchBar.contains(e.target) && !searchToggle.contains(e.target)) {
            searchBar.classList.add('hidden');
        }
    });
})();
</script>

{{-- Subscribe spinner keyframe --}}
<style>@keyframes subscribe-spin{to{transform:rotate(360deg)}}</style>

{{-- Newsletter AJAX --}}
<script>
(function () {
    var MSGS = {
        check_email:        '{{ __('front.subscribe_check_email') }}',
        already_subscribed: '{{ __('front.subscribe_already') }}',
        error:              'Something went wrong. Please try again.',
    };
    var ICON_OK   = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>';
    var ICON_WARN = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20A10 10 0 0012 2z"/>';
    var ICON_ERR  = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>';

    function showFeedback(form, type, msg) {
        var fb = form.querySelector('.subscribe-feedback');
        var icon = form.querySelector('.subscribe-feedback-icon');
        var text = form.querySelector('.subscribe-feedback-text');
        if (!fb) return;
        fb.style.display = 'flex';
        fb.className = 'subscribe-feedback mt-2 flex items-center gap-2 text-xs rounded px-3 py-2 '
            + (type === 'success' ? 'bg-green-900/60 text-green-300'
             : type === 'warn'    ? 'bg-amber-900/60 text-amber-300'
             :                      'bg-red-900/60 text-red-300');
        if (icon) icon.innerHTML = type === 'success' ? ICON_OK : type === 'warn' ? ICON_WARN : ICON_ERR;
        if (text) text.textContent = msg;
    }

    function setLoading(form, loading) {
        var btn     = form.querySelector('.subscribe-btn');
        var label   = form.querySelector('.subscribe-btn-label');
        var spinner = form.querySelector('.subscribe-spinner');
        var input   = form.querySelector('.subscribe-input');
        if (btn)     btn.disabled = loading;
        if (input)   input.disabled = loading;
        if (label)   label.style.display  = loading ? 'none' : '';
        if (spinner) spinner.style.display = loading ? 'block' : 'none';
    }

    document.querySelectorAll('.subscribe-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var emailEl = form.querySelector('[name="email"]');
            var token   = (form.querySelector('[name="_token"]') || {}).value || '';
            var email   = emailEl ? emailEl.value.trim() : '';
            if (!email) return;
            setLoading(form, true);
            fetch(form.dataset.url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                body: JSON.stringify({ email: email }),
            })
            .then(function (r) { if (!r.ok) throw new Error('server'); return r.json(); })
            .then(function (data) {
                if (data.status === 'already_subscribed') showFeedback(form, 'warn', MSGS.already_subscribed);
                else { showFeedback(form, 'success', MSGS.check_email); if (emailEl) emailEl.value = ''; }
            })
            .catch(function () { showFeedback(form, 'error', MSGS.error); })
            .finally(function () { setLoading(form, false); });
        });
    });
})();
</script>

{{-- Mobile fixed bottom navigation --}}
<nav class="lg:hidden fixed bottom-0 inset-x-0 z-50 bg-white border-t border-gray-100 shadow-[0_-2px_16px_rgba(0,0,0,0.07)] overflow-visible"
     style="padding-bottom: env(safe-area-inset-bottom, 0px);">
    <div class="flex items-center justify-around h-14 px-1">

        {{-- Shop --}}
        <a href="{{ route('shop.category') }}"
           class="flex flex-col items-center justify-center gap-0.5 flex-1 py-2 transition-colors {{ request()->routeIs('shop.*') ? 'text-primary-600' : 'text-gray-400' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                      d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
            </svg>
            <span class="text-[9px] font-semibold uppercase tracking-wide">Shop</span>
        </a>

        {{-- Wishlist --}}
        <a href="{{ auth()->check() ? route('wishlist.index') : route('login') }}"
           class="flex flex-col items-center justify-center gap-0.5 flex-1 py-2 transition-colors {{ request()->routeIs('wishlist.*') ? 'text-primary-600' : 'text-gray-400' }}">
            <div class="relative">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                          d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                </svg>
                <span class="wishlist-badge absolute -top-1.5 -right-1.5 bg-primary-600 text-white text-[8px] font-bold w-3.5 h-3.5 rounded-full flex items-center justify-center leading-none {{ $wishlistCount > 0 ? '' : 'hidden' }}">
                    {{ $wishlistCount > 9 ? '9+' : $wishlistCount }}
                </span>
            </div>
            <span class="text-[9px] font-semibold uppercase tracking-wide">Wishlist</span>
        </a>

        {{-- Home — elevated center button --}}
        <a href="{{ route('home') }}"
           class="flex flex-col items-center justify-center flex-1 -mt-6">
            <span class="w-13 h-13 rounded-full flex items-center justify-center shadow-lg transition-transform active:scale-95
                         {{ request()->routeIs('home') ? 'bg-primary-700' : 'bg-primary-600' }}"
                  style="width:52px;height:52px;box-shadow:0 4px 18px rgba(203,120,136,0.55);">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
            </span>
            <span class="text-[9px] font-semibold uppercase tracking-wide mt-0.5 {{ request()->routeIs('home') ? 'text-primary-600' : 'text-gray-400' }}">Home</span>
        </a>

        {{-- Cart --}}
        <a href="{{ route('cart.index') }}"
           class="flex flex-col items-center justify-center gap-0.5 flex-1 py-2 transition-colors {{ request()->routeIs('cart.*') ? 'text-primary-600' : 'text-gray-400' }}">
            <div class="relative">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                          d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                </svg>
                @if($cartCount > 0)
                    <span class="absolute -top-1.5 -right-1.5 bg-primary-600 text-white text-[8px] font-bold w-3.5 h-3.5 rounded-full flex items-center justify-center leading-none">
                        {{ $cartCount > 9 ? '9+' : $cartCount }}
                    </span>
                @endif
            </div>
            <span class="text-[9px] font-semibold uppercase tracking-wide">Cart</span>
        </a>

        {{-- Account --}}
        @auth
            <a href="{{ route('account.index') }}"
               class="flex flex-col items-center justify-center gap-0.5 flex-1 py-2 transition-colors {{ request()->routeIs('account.*') ? 'text-primary-600' : 'text-gray-400' }}">
        @else
            <a href="{{ route('login') }}"
               class="flex flex-col items-center justify-center gap-0.5 flex-1 py-2 text-gray-400 transition-colors">
        @endauth
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                          d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                <span class="text-[9px] font-semibold uppercase tracking-wide">{{ auth()->check() ? 'Account' : 'Sign In' }}</span>
            </a>

    </div>
</nav>

@stack('scripts')

@if($whatsappNumber)
{{-- WhatsApp Chat Widget --}}
<div id="wa-widget" style="position:fixed;bottom:24px;right:24px;z-index:9999;display:flex;flex-direction:column;align-items:flex-end;gap:12px;">

    {{-- Chat Box --}}
    <div id="wa-box" style="display:none;width:320px;border-radius:16px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);border:1px solid #e5e7eb;">

        {{-- Header --}}
        <div style="background:#075e54;display:flex;align-items:center;gap:12px;padding:12px 16px;">
            <div style="width:40px;height:40px;border-radius:50%;background:#25d366;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" style="width:20px;height:20px;fill:white;">
                    <path d="M16 .5C7.44.5.5 7.44.5 16c0 2.83.74 5.49 2.03 7.8L.5 31.5l7.93-2.08A15.45 15.45 0 0 0 16 31.5C24.56 31.5 31.5 24.56 31.5 16S24.56.5 16 .5zm0 28.18a13.1 13.1 0 0 1-6.68-1.83l-.48-.29-4.71 1.24 1.26-4.6-.31-.5A13.07 13.07 0 0 1 2.88 16C2.88 9.07 8.07 3.38 16 3.38 23.46 3.38 28.62 9.07 28.62 16c0 6.93-5.16 12.68-12.62 12.68zm7.13-9.47c-.39-.2-2.3-1.14-2.66-1.27-.36-.13-.62-.2-.88.2-.26.38-1.01 1.27-1.24 1.53-.23.26-.46.29-.85.1-.39-.2-1.65-.61-3.14-1.94-1.16-1.04-1.94-2.32-2.17-2.71-.23-.39-.02-.6.17-.79.18-.18.39-.46.58-.69.2-.23.26-.39.39-.65.13-.26.07-.49-.03-.69-.1-.2-.88-2.12-1.2-2.9-.32-.77-.64-.66-.88-.67h-.75c-.26 0-.68.1-1.04.49-.36.39-1.37 1.34-1.37 3.26s1.4 3.78 1.6 4.04c.2.26 2.76 4.21 6.68 5.91.93.4 1.66.64 2.23.82.94.3 1.79.26 2.46.16.75-.11 2.3-.94 2.63-1.85.32-.91.32-1.69.23-1.85-.1-.16-.36-.26-.75-.46z"/>
                </svg>
            </div>
            <div style="flex:1;min-width:0;">
                <p style="color:white;font-weight:700;font-size:14px;line-height:1.2;margin:0;">{{ config('app.name') }}</p>
                <p style="color:#a7f3d0;font-size:12px;margin:2px 0 0;">Typically replies instantly</p>
            </div>
            <button onclick="waClose()" aria-label="Close" style="background:none;border:none;cursor:pointer;color:rgba(255,255,255,0.7);padding:4px;display:flex;align-items:center;justify-content:center;border-radius:50%;">
                <svg style="width:18px;height:18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Chat Body --}}
        <div style="background:#dfe5e1;padding:16px;">
            <div style="display:flex;justify-content:flex-start;">
                <div style="background:white;border-radius:0 8px 8px 8px;padding:12px 14px;max-width:90%;box-shadow:0 1px 2px rgba(0,0,0,0.1);">
                    <p style="color:#1f2937;font-size:13px;line-height:1.6;margin:0;">👋 Hello! How can we help you today? Send us a message and we'll get back to you on WhatsApp.</p>
                    <p style="color:#9ca3af;font-size:11px;margin:6px 0 0;text-align:right;">{{ config('app.name') }}</p>
                </div>
            </div>
        </div>

        {{-- Input Area --}}
        <div style="background:white;padding:10px 12px;display:flex;align-items:flex-end;gap:8px;border-top:1px solid #f3f4f6;">
            <textarea
                id="wa-message"
                rows="2"
                placeholder="Type a message..."
                onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();waSend();}"
                style="flex:1;resize:none;font-size:13px;color:#1f2937;border:1px solid #e5e7eb;border-radius:12px;padding:8px 12px;outline:none;font-family:inherit;line-height:1.5;"
            >Hello! I have a question about your products.</textarea>
            <button
                onclick="waSend()"
                aria-label="Send"
                style="width:40px;height:40px;border-radius:50%;background:#25d366;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:transform .15s;"
                onmouseenter="this.style.transform='scale(1.1)'"
                onmouseleave="this.style.transform='scale(1)'"
            >
                <svg style="width:18px;height:18px;fill:white;" viewBox="0 0 24 24">
                    <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                </svg>
            </button>
        </div>
    </div>

    {{-- Toggle Button + Dismiss --}}
    <div style="position:relative;display:inline-flex;">
        <button
            id="wa-toggle"
            onclick="waToggle()"
            aria-label="Chat on WhatsApp"
            style="width:56px;height:56px;border-radius:50%;background:#25d366;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 16px rgba(37,211,102,0.45);transition:transform .2s;"
            onmouseenter="this.style.transform='scale(1.1)'"
            onmouseleave="this.style.transform='scale(1)'"
        >
            <svg id="wa-icon-open" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" style="width:28px;height:28px;fill:white;">
                <path d="M16 .5C7.44.5.5 7.44.5 16c0 2.83.74 5.49 2.03 7.8L.5 31.5l7.93-2.08A15.45 15.45 0 0 0 16 31.5C24.56 31.5 31.5 24.56 31.5 16S24.56.5 16 .5zm0 28.18a13.1 13.1 0 0 1-6.68-1.83l-.48-.29-4.71 1.24 1.26-4.6-.31-.5A13.07 13.07 0 0 1 2.88 16C2.88 9.07 8.07 3.38 16 3.38 23.46 3.38 28.62 9.07 28.62 16c0 6.93-5.16 12.68-12.62 12.68zm7.13-9.47c-.39-.2-2.3-1.14-2.66-1.27-.36-.13-.62-.2-.88.2-.26.38-1.01 1.27-1.24 1.53-.23.26-.46.29-.85.1-.39-.2-1.65-.61-3.14-1.94-1.16-1.04-1.94-2.32-2.17-2.71-.23-.39-.02-.6.17-.79.18-.18.39-.46.58-.69.2-.23.26-.39.39-.65.13-.26.07-.49-.03-.69-.1-.2-.88-2.12-1.2-2.9-.32-.77-.64-.66-.88-.67h-.75c-.26 0-.68.1-1.04.49-.36.39-1.37 1.34-1.37 3.26s1.4 3.78 1.6 4.04c.2.26 2.76 4.21 6.68 5.91.93.4 1.66.64 2.23.82.94.3 1.79.26 2.46.16.75-.11 2.3-.94 2.63-1.85.32-.91.32-1.69.23-1.85-.1-.16-.36-.26-.75-.46z"/>
            </svg>
            <svg id="wa-icon-close" style="display:none;width:24px;height:24px;" fill="none" stroke="white" viewBox="0 0 24 24" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>

        {{-- Dismiss button --}}
        <button
            onclick="waDismiss()"
            aria-label="Hide WhatsApp button"
            style="position:absolute;top:-4px;right:-4px;width:18px;height:18px;border-radius:50%;background:#4b5563;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;box-shadow:0 1px 4px rgba(0,0,0,0.3);transition:background .15s;"
            onmouseenter="this.style.background='#1f2937'"
            onmouseleave="this.style.background='#4b5563'"
        >
            <svg style="width:9px;height:9px;" fill="none" stroke="white" viewBox="0 0 24 24" stroke-width="3">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>
</div>

<script>
(function () {
    var waNumber = '{{ $whatsappNumber }}';

    window.waToggle = function () {
        var box  = document.getElementById('wa-box');
        var open = document.getElementById('wa-icon-open');
        var cls  = document.getElementById('wa-icon-close');
        var visible = box.style.display !== 'none';
        box.style.display  = visible ? 'none' : 'block';
        open.style.display = visible ? 'block' : 'none';
        cls.style.display  = visible ? 'none'  : 'block';
    };

    window.waClose = function () {
        document.getElementById('wa-box').style.display = 'none';
        document.getElementById('wa-icon-open').style.display  = 'block';
        document.getElementById('wa-icon-close').style.display = 'none';
    };

    window.waDismiss = function () {
        document.getElementById('wa-widget').style.display = 'none';
    };

    window.waSend = function () {
        var msg = document.getElementById('wa-message').value.trim();
        if (!msg) return;
        var url = 'https://wa.me/' + waNumber + '?text=' + encodeURIComponent(msg);
        window.open(url, '_blank', 'noopener,noreferrer');
    };
}());
</script>
@endif

<script>
window.downloadInvoice = async function (url, filename, btn) {
    const original = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<svg class="animate-spin w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> Preparing...';
    try {
        const res  = await fetch(url, {credentials: 'same-origin'});
        const blob = await res.blob();
        const link = document.createElement('a');
        link.href     = URL.createObjectURL(blob);
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(link.href);
    } catch (e) {
        console.error('Invoice download failed', e);
    } finally {
        btn.disabled  = false;
        btn.innerHTML = original;
    }
};
</script>
</body>
</html>
