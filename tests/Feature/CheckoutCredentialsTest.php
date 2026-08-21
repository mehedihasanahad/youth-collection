<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Controllers\CheckoutController;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutCredentialsTest extends TestCase
{
    use RefreshDatabase;

    private function orderFor(User $user): Order
    {
        return Order::create([
            'order_number' => Order::generateOrderNumber(),
            'user_id' => $user->id,
            'subtotal' => 1000,
            'total_amount' => 1000,
            'status' => Order::STATUS_PENDING,
            'payment_status' => Order::PAYMENT_UNPAID,
            'payment_method' => 'cod',
            'ship_name' => 'Test Customer',
            'ship_phone' => $user->phone,
            'ship_address' => 'House 1, Road 2',
            'ship_district' => 'Dhaka',
        ]);
    }

    public function test_credentials_are_shown_once_then_cleared(): void
    {
        $user = User::factory()->create(['phone' => '01712345678', 'email' => null]);
        $order = $this->orderFor($user);

        $this->actingAs($user)
            ->withSession([CheckoutController::NEW_CREDENTIALS_KEY => [
                'phone' => '01712345678',
                'password' => 'Sup3rSecret1',
            ]])
            ->get(route('checkout.confirmation', $order))
            ->assertOk()
            ->assertSee('Sup3rSecret1')
            ->assertSee('01712345678');

        // The session entry is consumed, so a refresh must not reveal it again.
        $this->actingAs($user)
            ->get(route('checkout.confirmation', $order))
            ->assertOk()
            ->assertDontSee('Sup3rSecret1');
    }

    public function test_no_modal_is_rendered_without_pending_credentials(): void
    {
        $user = User::factory()->create(['phone' => '01712345678']);
        $order = $this->orderFor($user);

        $this->actingAs($user)
            ->get(route('checkout.confirmation', $order))
            ->assertOk()
            ->assertDontSee('credentials-modal');
    }
}
