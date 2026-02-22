<?php

use App\Models\Restaurant;
use App\Models\User;
use App\Models\UserPaymentMethod;
use Illuminate\Support\Str;

function bearerHeadersForUserAdditionalEndpointTest(User $user): array
{
    $token = $user->createToken('user-app-extra-endpoint-test', ['*'])->plainTextToken;

    return [
        'Authorization' => 'Bearer '.$token,
        'Accept' => 'application/json',
    ];
}

it('lets users add list and remove favorite restaurants', function () {
    $user = User::factory()->create();
    $restaurant = Restaurant::factory()->create([
        'is_active' => true,
    ]);
    $headers = bearerHeadersForUserAdditionalEndpointTest($user);

    $this->withHeaders($headers)
        ->postJson('/api/v1/users/me/favorites/'.$restaurant->id)
        ->assertCreated()
        ->assertJsonPath('data.restaurant_id', $restaurant->id);

    $this->withHeaders($headers)
        ->getJson('/api/v1/users/me/favorites')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $restaurant->id);

    $this->withHeaders($headers)
        ->deleteJson('/api/v1/users/me/favorites/'.$restaurant->id)
        ->assertSuccessful();

    $this->withHeaders($headers)
        ->getJson('/api/v1/users/me/favorites')
        ->assertSuccessful()
        ->assertJsonCount(0, 'data');
});

it('lets users add payment methods and keeps default in sync', function () {
    $user = User::factory()->create();
    $headers = bearerHeadersForUserAdditionalEndpointTest($user);

    $firstResponse = $this->withHeaders($headers)
        ->postJson('/api/v1/users/me/payment-methods', [
            'provider' => 'stripe',
            'brand' => 'visa',
            'last_four' => '4242',
            'exp_month' => 12,
            'exp_year' => 2030,
            'token_reference' => 'pm_alias_test_1',
            'is_default' => false,
        ]);

    $firstResponse
        ->assertCreated()
        ->assertJsonPath('data.is_default', true);

    $secondResponse = $this->withHeaders($headers)
        ->postJson('/api/v1/users/me/payment-methods', [
            'provider' => 'stripe',
            'brand' => 'mastercard',
            'last_four' => '5555',
            'exp_month' => 9,
            'exp_year' => 2032,
            'token_reference' => 'pm_alias_test_2',
            'is_default' => true,
        ]);

    $secondResponse
        ->assertCreated()
        ->assertJsonPath('data.is_default', true);

    $firstMethodId = (int) $firstResponse->json('data.id');
    $secondMethodId = (int) $secondResponse->json('data.id');

    expect(UserPaymentMethod::query()->findOrFail($firstMethodId)->is_default)->toBeFalse();
    expect(UserPaymentMethod::query()->findOrFail($secondMethodId)->is_default)->toBeTrue();

    $this->withHeaders($headers)
        ->getJson('/api/v1/users/me/payment-methods')
        ->assertSuccessful()
        ->assertJsonCount(2, 'data');
});

it('lists notifications and marks notification as read', function () {
    $user = User::factory()->create();
    $anotherUser = User::factory()->create();
    $headers = bearerHeadersForUserAdditionalEndpointTest($user);

    $notification = $user->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => 'App\\Notifications\\AppSystemNotification',
        'data' => [
            'title' => 'Order Update',
            'message' => 'Your order is now being prepared.',
        ],
    ]);

    $foreignNotification = $anotherUser->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => 'App\\Notifications\\AppSystemNotification',
        'data' => [
            'title' => 'Order Update',
            'message' => 'This should not be visible to another user.',
        ],
    ]);

    $this->withHeaders($headers)
        ->getJson('/api/v1/notifications')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $notification->id);

    $this->withHeaders($headers)
        ->patchJson('/api/v1/notifications/'.$notification->id.'/read')
        ->assertSuccessful()
        ->assertJsonPath('data.id', $notification->id);

    $this->withHeaders($headers)
        ->patchJson('/api/v1/notifications/'.$foreignNotification->id.'/read')
        ->assertNotFound();
});
