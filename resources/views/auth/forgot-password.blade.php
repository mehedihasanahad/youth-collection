@extends('layouts.app')

@section('title', __('front.forgot_password') . ' — ' . config('app.name', 'ShopZone'))

@section('content')
<div class="min-h-[70vh] flex items-center justify-center py-14 px-4">
    <div class="w-full max-w-md">

        {{-- Card --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 px-8 py-10">

            {{-- Icon / heading --}}
            <div class="text-center mb-8">
                <div class="w-14 h-14 bg-primary-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-7 h-7 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-900">{{ __('front.forgot_password') }}</h1>
                <p class="mt-2 text-sm text-gray-500">{{ __('front.forgot_password_hint') }}</p>
            </div>

            {{-- Session Status --}}
            @if(session('status'))
                <div class="mb-6 text-sm text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg px-4 py-3">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
                @csrf

                {{-- Phone number --}}
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ __('front.phone_number') }}
                    </label>
                    <input type="tel" id="phone" name="phone"
                           value="{{ old('phone') }}"
                           placeholder="01XXXXXXXXX"
                           inputmode="tel"
                           required autofocus autocomplete="tel"
                           class="w-full px-4 py-2.5 border rounded-xl text-sm bg-gray-50 focus:bg-white focus:border-primary-400 focus:ring-2 focus:ring-primary-100 focus:outline-none transition-all
                                  {{ $errors->has('phone') ? 'border-red-400' : 'border-gray-200' }}">
                    @error('phone')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Submit --}}
                <button type="submit"
                        class="w-full bg-primary-600 hover:bg-primary-700 active:bg-primary-800 text-white font-semibold py-3 rounded-xl transition-colors text-sm">
                    {{ __('front.send_reset_link') }}
                </button>
            </form>

            {{-- Back to login --}}
            <p class="mt-6 text-center text-sm text-gray-500">
                <a href="{{ route('login') }}" class="text-primary-600 hover:text-primary-700 font-semibold transition-colors inline-flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    {{ __('front.back_to_login') }}
                </a>
            </p>
        </div>
    </div>
</div>
@endsection
