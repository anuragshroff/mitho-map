<?php

use App\Models\SpecialOffer;

it('returns active and valid special offers', function () {
    $inactiveOffer = SpecialOffer::factory()->create(['is_active' => false]);
    $expiredOffer = SpecialOffer::factory()->create([
        'is_active' => true,
        'valid_until' => now()->subDay(),
    ]);
    $futureOffer = SpecialOffer::factory()->create([
        'is_active' => true,
        'valid_from' => now()->addDay(),
    ]);

    $validOffer1 = SpecialOffer::factory()->create([
        'is_active' => true,
        'valid_from' => now()->subDay(),
        'valid_until' => now()->addDay(),
        'created_at' => now()->subMinute(),
    ]);
    $validOffer2 = SpecialOffer::factory()->create([
        'is_active' => true,
        'valid_from' => null,
        'valid_until' => null,
        'created_at' => now(),
    ]);

    $response = $this->getJson(route('api.v1.special-offers.index'));

    $response->assertStatus(200)
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.id', $validOffer2->id) // latest comes first
        ->assertJsonPath('data.1.id', $validOffer1->id);
});
