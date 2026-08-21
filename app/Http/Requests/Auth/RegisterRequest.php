<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;

final class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $email = trim((string) $this->input('email', ''));

        $this->merge([
            'phone' => PhoneNumber::normalize($this->input('phone')) ?? trim((string) $this->input('phone', '')),
            'email' => $email === '' ? null : strtolower($email),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:'.PhoneNumber::REGEX, Rule::unique(User::class, 'phone')],
            'email' => ['nullable', 'string', 'email', 'max:255', Rule::unique(User::class, 'email')],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => __('front.phone_invalid'),
            'phone.unique' => __('front.phone_taken'),
        ];
    }

    public function attributes(): array
    {
        return [
            'phone' => __('front.phone_number'),
            'email' => __('front.email'),
        ];
    }
}
