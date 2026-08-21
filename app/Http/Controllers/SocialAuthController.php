<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    /**
     * Redirect to Google OAuth — only if admin has enabled it.
     */
    public function redirectToGoogle()
    {
        if (! $this->googleEnabled()) {
            abort(404);
        }

        $this->configureGoogle();

        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle the callback from Google.
     */
    public function handleGoogleCallback()
    {
        if (! $this->googleEnabled()) {
            abort(404);
        }

        $this->configureGoogle();

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            return redirect()->route('login')->withErrors(['phone' => 'Google authentication failed. Please try again.']);
        }

        // Find existing user by email
        $user = User::where('email', $googleUser->getEmail())->first();

        if ($user) {
            // Link Google provider info if not already set
            if (! $user->provider_id) {
                $user->update([
                    'provider'    => 'google',
                    'provider_id' => $googleUser->getId(),
                ]);
            }
        } else {
            // Create a new customer account
            $user = User::create([
                'name'              => $googleUser->getName(),
                'email'             => $googleUser->getEmail(),
                'provider'          => 'google',
                'provider_id'       => $googleUser->getId(),
                'password'          => bcrypt(\Illuminate\Support\Str::random(32)),
                'email_verified_at' => now(),
                'is_active'         => true,
            ]);
        }

        if (! $user->is_active) {
            return redirect()->route('login')->withErrors(['phone' => 'Your account is inactive. Please contact support.']);
        }

        Auth::login($user, remember: true);

        return redirect()->intended(route('home'));
    }

    private function googleEnabled(): bool
    {
        return (bool) Setting::get('google_login_enabled', '0')
            && Setting::get('google_client_id', '') !== ''
            && Setting::get('google_client_secret', '') !== '';
    }

    /**
     * Override the Socialite/Google config with credentials stored in admin Settings.
     * This allows changing OAuth credentials from the admin panel without redeploying.
     */
    private function configureGoogle(): void
    {
        config([
            'services.google.client_id'     => Setting::get('google_client_id', ''),
            'services.google.client_secret'  => Setting::get('google_client_secret', ''),
            'services.google.redirect'       => route('auth.google.callback'),
        ]);
    }
}
