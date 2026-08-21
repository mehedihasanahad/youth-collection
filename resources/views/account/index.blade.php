@extends('layouts.app')

@section('title', __('front.account_dashboard') . ' — ' . config('app.name'))

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    {{-- Page heading --}}
    <h1 class="text-2xl font-bold text-gray-900 mb-8">{{ __('front.my_account') }}</h1>

    <div class="flex flex-col lg:flex-row gap-8">

        {{-- Sidebar --}}
        @include('account.partials.sidebar')

        {{-- Main content --}}
        <div class="flex-1 min-w-0 space-y-6">

            {{-- Welcome banner --}}
            <div class="bg-gradient-to-r from-primary-600 to-primary-700 rounded-2xl p-6 text-white">
                <p class="text-sm text-primary-200 mb-1">{{ __('front.welcome_back') }}</p>
                <h2 class="text-xl font-bold">{{ $user->name }}</h2>
                <p class="text-sm text-primary-200 mt-1">{{ $user->displayIdentifier() }}</p>
            </div>

            {{-- Stats row --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                @foreach([
                    ['label' => __('front.total_orders'),   'value' => $orderCount,   'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2', 'color' => 'text-primary-600 bg-primary-50'],
                    ['label' => __('front.wishlist'),        'value' => $wishlistCount,'icon' => 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z', 'color' => 'text-red-500 bg-red-50'],
                    ['label' => __('front.reviews_written'), 'value' => $reviewCount,  'icon' => 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z', 'color' => 'text-amber-500 bg-amber-50'],
                    ['label' => __('front.saved_addresses'), 'value' => $addressCount, 'icon' => 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z', 'color' => 'text-indigo-500 bg-indigo-50'],
                ] as $stat)
                    <div class="bg-white border border-gray-100 rounded-2xl p-4">
                        <div class="w-9 h-9 rounded-xl {{ $stat['color'] }} flex items-center justify-center mb-3">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $stat['icon'] }}"/>
                            </svg>
                        </div>
                        <p class="text-2xl font-bold text-gray-900">{{ $stat['value'] }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $stat['label'] }}</p>
                    </div>
                @endforeach
            </div>

            {{-- Recent orders --}}
            <div class="bg-white border border-gray-100 rounded-2xl overflow-hidden">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <h2 class="text-sm font-bold text-gray-900">{{ __('front.recent_orders') }}</h2>
                    <a href="{{ route('orders.index') }}" class="text-xs text-primary-600 hover:text-primary-700 font-medium">
                        {{ __('front.view_all') }} →
                    </a>
                </div>

                @if($recentOrders->isEmpty())
                    <div class="px-6 py-10 text-center text-sm text-gray-400">{{ __('front.no_orders') }}</div>
                @else
                    <div class="divide-y divide-gray-50">
                        @foreach($recentOrders as $order)
                            @php
                                $statusColors = [
                                    'pending'    => 'bg-amber-100 text-amber-700',
                                    'processing' => 'bg-blue-100 text-blue-700',
                                    'shipped'    => 'bg-indigo-100 text-indigo-700',
                                    'delivered'  => 'bg-emerald-100 text-emerald-700',
                                    'cancelled'  => 'bg-red-100 text-red-700',
                                    'returned'   => 'bg-gray-100 text-gray-600',
                                ];
                            @endphp
                            <div class="flex items-center justify-between px-6 py-4">
                                <div>
                                    <p class="text-sm font-semibold text-gray-800">{{ $order->order_number }}</p>
                                    <p class="text-xs text-gray-400 mt-0.5">{{ $order->created_at->format('d M Y') }} · {{ $order->items->count() }} {{ __('front.items') }}</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-xs px-2 py-0.5 rounded-full font-semibold {{ $statusColors[$order->status] ?? 'bg-gray-100 text-gray-600' }}">
                                        {{ __('front.status_' . $order->status) }}
                                    </span>
                                    <span class="text-sm font-bold text-gray-900">৳{{ number_format($order->total_amount, 0) }}</span>
                                    <a href="{{ route('orders.show', $order) }}" class="text-xs text-primary-600 hover:text-primary-700 font-medium border border-primary-200 hover:border-primary-400 px-3 py-1.5 rounded-lg transition-colors">
                                        {{ __('front.view_order') }}
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

        </div>
    </div>
</div>
@endsection
