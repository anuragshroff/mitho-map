<?php

use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Support\Str;

it('requires bearer authentication for protected user app endpoints', function () {
    $order = Order::factory()->create();
    $restaurant = Restaurant::factory()->create();
    $notificationId = User::factory()->create()
        ->notifications()
        ->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\AppSystemNotification',
            'data' => [
                'title' => 'Auth Check',
                'message' => 'Endpoint should require bearer token.',
            ],
        ])
        ->id;

    $requests = [
        ['GET', '/api/v1/auth/me', []],
        ['PUT', '/api/v1/auth/me', ['name' => 'Updated Name', 'email' => 'updated@example.com']],
        ['GET', '/api/v1/users/me', []],
        ['GET', '/api/v1/users/me/addresses', []],
        ['POST', '/api/v1/users/me/addresses', [
            'label' => 'Home',
            'line_1' => 'Street 1',
            'city' => 'Kathmandu',
            'state' => 'Bagmati',
            'postal_code' => '44600',
            'country' => 'NP',
            'latitude' => 27.7172,
            'longitude' => 85.3240,
        ]],
        ['GET', '/api/v1/users/me/favorites', []],
        ['POST', '/api/v1/users/me/favorites/'.$restaurant->id, []],
        ['DELETE', '/api/v1/users/me/favorites/'.$restaurant->id, []],
        ['GET', '/api/v1/users/me/payment-methods', []],
        ['POST', '/api/v1/users/me/payment-methods', [
            'provider' => 'stripe',
            'brand' => 'visa',
            'last_four' => '4242',
            'exp_month' => 12,
            'exp_year' => 2030,
            'token_reference' => 'pm_auth_guard_test',
            'is_default' => true,
        ]],
        ['GET', '/api/v1/notifications', []],
        ['PATCH', '/api/v1/notifications/'.$notificationId.'/read', []],
        ['GET', '/api/v1/orders', []],
        ['POST', '/api/v1/orders', []],
        ['GET', '/api/v1/orders/'.$order->id, []],
        ['GET', '/api/v1/orders/'.$order->id.'/tracking', []],
        ['GET', '/api/v1/chat/'.$order->id.'/messages', []],
        ['POST', '/api/v1/chat/'.$order->id.'/messages', ['message' => 'Hi']],
        ['DELETE', '/api/v1/auth/token', []],
    ];

    foreach ($requests as [$method, $uri, $payload]) {
        $response = match ($method) {
            'GET' => $this->getJson($uri),
            'POST' => $this->postJson($uri, $payload),
            'PUT' => $this->putJson($uri, $payload),
            'PATCH' => $this->patchJson($uri, $payload),
            'DELETE' => $this->deleteJson($uri),
        };

        $response->assertUnauthorized();
    }
});

it('keeps public auth endpoints accessible without bearer token', function () {
    $this->postJson('/api/v1/auth/sign-in', [])
        ->assertUnprocessable();

    $this->postJson('/api/v1/auth/sign-up', [])
        ->assertUnprocessable();

    $this->postJson('/api/v1/auth/phone/send-otp', [])
        ->assertUnprocessable();

    $this->postJson('/api/v1/auth/phone/verify-otp', [])
        ->assertUnprocessable();
});
