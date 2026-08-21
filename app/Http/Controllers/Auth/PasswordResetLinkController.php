<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * The customer identifies themselves with their phone number; the reset link
     * is delivered to the email address stored on that account. Accounts without
     * an email have no self-service recovery channel and are sent to support.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'phone' => PhoneNumber::normalize($request->input('phone')) ?? trim((string) $request->input('phone', '')),
        ]);

        $request->validate(
            ['phone' => ['required', 'string', 'regex:'.PhoneNumber::REGEX]],
            ['phone.regex' => __('front.phone_invalid')]
        );

        $user = User::where('phone', $request->input('phone'))->first();

        if (! $user) {
            return back()->withInput($request->only('phone'))
                ->withErrors(['phone' => __('front.password_reset_no_account')]);
        }

        if (! $user->hasEmail()) {
            return back()->withInput($request->only('phone'))
                ->withErrors(['phone' => __('front.password_reset_no_email')]);
        }

        $status = Password::sendResetLink(['email' => $user->email]);

        return $status == Password::RESET_LINK_SENT
                    ? back()->with('status', __('front.password_reset_sent', ['email' => $this->maskEmail($user->email)]))
                    : back()->withInput($request->only('phone'))
                        ->withErrors(['phone' => __($status)]);
    }

    /** j***@example.com — enough for the customer to recognise the inbox, not enough to harvest it. */
    private function maskEmail(string $email): string
    {
        [$local, $domain] = explode('@', $email, 2);

        return mb_substr($local, 0, 1).str_repeat('*', max(mb_strlen($local) - 1, 1)).'@'.$domain;
    }
}
