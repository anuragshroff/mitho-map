<?php

use App\Models\Banner;

it('returns active banners ordered correctly', function () {
    $inactiveBanner = Banner::factory()->create([
        'is_active' => false,
        'order' => 1,
    ]);

    $secondBanner = Banner::factory()->create([
        'is_active' => true,
        'order' => 2,
    ]);

    $firstBanner = Banner::factory()->create([
        'is_active' => true,
        'order' => 1,
    ]);

    $response = $this->getJson(route('api.v1.banners.index'));

    $response->assertStatus(200)
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.id', $firstBanner->id)
        ->assertJsonPath('data.1.id', $secondBanner->id);
});
