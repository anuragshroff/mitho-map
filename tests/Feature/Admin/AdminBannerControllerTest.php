<?php

use App\Models\Banner;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
});

it('can list banners for admin', function () {
    Banner::factory()->count(3)->create();

    $response = $this->actingAs($this->admin)->get(route('admin.banners.index'));

    $response->assertStatus(200);
});

it('can store a new banner', function () {
    $data = [
        'title' => 'New Year Sale',
        'image_url' => 'http://example.com/banner.jpg',
        'target_url' => 'http://example.com/sale',
        'is_active' => true,
        'order' => 5,
    ];

    $response = $this->actingAs($this->admin)->post(route('admin.banners.store'), $data);

    $response->assertRedirect();
    $this->assertDatabaseHas('banners', $data);
});

it('can update a banner', function () {
    $banner = Banner::factory()->create();

    $data = [
        'title' => 'Updated Sale',
        'image_url' => 'http://example.com/updated.jpg',
        'is_active' => false,
    ];

    $response = $this->actingAs($this->admin)->patch(route('admin.banners.update', $banner), $data);

    $response->assertRedirect();
    $this->assertDatabaseHas('banners', array_merge(['id' => $banner->id], $data));
});

it('can update banner status', function () {
    $banner = Banner::factory()->create(['is_active' => true]);

    $response = $this->actingAs($this->admin)
        ->patch(route('admin.banners.update-status', $banner), ['is_active' => false]);

    $response->assertRedirect();
    $this->assertDatabaseHas('banners', [
        'id' => $banner->id,
        'is_active' => false,
    ]);
});

it('can delete a banner', function () {
    $banner = Banner::factory()->create();

    $response = $this->actingAs($this->admin)->delete(route('admin.banners.destroy', $banner));

    $response->assertRedirect();
    $this->assertDatabaseMissing('banners', ['id' => $banner->id]);
});
