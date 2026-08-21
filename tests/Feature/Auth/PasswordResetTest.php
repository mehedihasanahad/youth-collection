<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_reset_password_link_can_be_requested_with_a_phone_number(): void
    {
        Notification::fake();

        $user = User::factory()->create(['phone' => '01712345678']);

        $this->post('/forgot-password', ['phone' => '01712345678'])
            ->assertSessionHasNoErrors();

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_reset_link_is_refused_for_an_unknown_phone_number(): void
    {
        Notification::fake();

        $this->post('/forgot-password', ['phone' => '01712345678'])
            ->assertSessionHasErrors('phone');

        Notification::assertNothingSent();
    }

    public function test_reset_link_is_refused_when_the_account_has_no_email(): void
    {
        Notification::fake();

        User::factory()->create(['phone' => '01712345678', 'email' => null]);

        $this->post('/forgot-password', ['phone' => '01712345678'])
            ->assertSessionHasErrors('phone');

        Notification::assertNothingSent();
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        Notification::fake();

        $user = User::factory()->create(['phone' => '01712345678']);

        $this->post('/forgot-password', ['phone' => '01712345678']);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
            $response = $this->get('/reset-password/'.$notification->token);

            $response->assertStatus(200);

            return true;
        });
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create(['phone' => '01712345678']);

        $this->post('/forgot-password', ['phone' => '01712345678']);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

            $response
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('login'));

            return true;
        });
    }
}
