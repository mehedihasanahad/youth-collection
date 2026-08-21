@extends('layouts.app')

@section('title', __('front.login_title') . ' — ' . config('app.name', 'ShopZone'))

@section('content')
<div class="min-h-[70vh] flex items-center justify-center py-14 px-4">
    <div class="w-full max-w-md">

        {{-- Card --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 px-8 py-10">

            {{-- Logo / heading --}}
            <div class="text-center mb-8">
                <div class="w-14 h-14 bg-primary-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-7 h-7 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-900">{{ __('front.login_title') }}</h1>
            </div>

            {{-- Session Status --}}
            @if(session('status'))
                <div class="mb-4 text-sm text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg px-4 py-3">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-5">
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

                {{-- Password --}}
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label for="password" class="block text-sm font-medium text-gray-700">
                            {{ __('front.password') }}
                        </label>
                        @if(Route::has('password.request'))
                            <a href="{{ route('password.request') }}"
                               class="text-xs text-primary-600 hover:text-primary-700 transition-colors">
                                {{ __('front.forgot_password') }}
                            </a>
                        @endif
                    </div>
                    <input type="password" id="password" name="password"
                           required autocomplete="current-password"
                           class="w-full px-4 py-2.5 border rounded-xl text-sm bg-gray-50 focus:bg-white focus:border-primary-400 focus:ring-2 focus:ring-primary-100 focus:outline-none transition-all
                                  {{ $errors->has('password') ? 'border-red-400' : 'border-gray-200' }}">
                    @error('password')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Remember me --}}
                <div class="flex items-center gap-2">
                    <input type="checkbox" id="remember_me" name="remember"
                           class="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                    <label for="remember_me" class="text-sm text-gray-600">
                        {{ __('front.remember_me') }}
                    </label>
                </div>

                {{-- Submit --}}
                <button type="submit"
                        class="w-full bg-primary-600 hover:bg-primary-700 active:bg-primary-800 text-white font-semibold py-3 rounded-xl transition-colors text-sm">
                    {{ __('front.sign_in') }}
                </button>
            </form>

            {{-- Google Login --}}
            @if($googleLoginEnabled)
                <div class="mt-5">
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-200"></div>
                        </div>
                        <div class="relative flex justify-center text-xs uppercase">
                            <span class="bg-white px-3 text-gray-400 font-medium tracking-wide">or</span>
                        </div>
                    </div>
                    <a href="{{ route('auth.google.redirect') }}"
                       class="mt-4 flex w-full items-center justify-center gap-3 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 active:bg-gray-100 transition-colors">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/>
                            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                        </svg>
                        Continue with Google
                    </a>
                </div>
            @endif

            {{-- Register link --}}
            <p class="mt-6 text-center text-sm text-gray-500">
                {{ __('front.no_account') }}
                <a href="{{ route('register') }}" class="text-primary-600 hover:text-primary-700 font-semibold transition-colors">
                    {{ __('front.register') }}
                </a>
            </p>
        </div>
    </div>
</div>
@endsection
