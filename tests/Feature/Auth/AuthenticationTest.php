<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_with_their_phone_number(): void
    {
        $user = User::factory()->create(['phone' => '01712345678']);

        $response = $this->post('/login', [
            'phone' => '01712345678',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('home', absolute: false));
    }

    public function test_phone_number_is_normalised_before_authenticating(): void
    {
        $user = User::factory()->create(['phone' => '01712345678']);

        $this->post('/login', [
            'phone' => '+880 1712-345678',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
    }

    public function test_legacy_accounts_can_still_authenticate_with_their_email(): void
    {
        $user = User::factory()->create(['email' => 'legacy@example.com', 'phone' => null]);

        $this->post('/login', [
            'phone' => 'legacy@example.com',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create(['phone' => '01712345678']);

        $this->post('/login', [
            'phone' => $user->phone,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
