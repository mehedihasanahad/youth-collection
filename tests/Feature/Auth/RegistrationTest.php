<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register_with_a_phone_number_only(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'phone' => '01712345678',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('home', absolute: false));

        $this->assertDatabaseHas('users', [
            'phone' => '01712345678',
            'email' => null,
        ]);
    }

    public function test_registration_stores_an_optional_email(): void
    {
        $this->post('/register', [
            'name' => 'Test User',
            'phone' => '+8801712345678',
            'email' => 'Test@Example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        // The phone is normalised and the email lower-cased before storage.
        $this->assertDatabaseHas('users', [
            'phone' => '01712345678',
            'email' => 'test@example.com',
        ]);
    }

    public function test_a_phone_number_can_only_be_registered_once(): void
    {
        User::factory()->create(['phone' => '01712345678']);

        $response = $this->post('/register', [
            'name' => 'Test User',
            'phone' => '01712345678',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('phone');
        $this->assertGuest();
    }

    public function test_an_invalid_phone_number_is_rejected(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'phone' => '12345',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('phone');
        $this->assertGuest();
    }
}
