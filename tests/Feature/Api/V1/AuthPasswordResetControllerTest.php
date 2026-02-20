<?php

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

it('sends a password reset link', function () {
    $user = User::factory()->create();

    $response = $this->postJson(route('api.v1.auth.password.email'), [
        'email' => $user->email,
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure(['message']);
});

it('fails to send link if email does not exist', function () {
    $response = $this->postJson(route('api.v1.auth.password.email'), [
        'email' => 'nonexistent@example.com',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

it('resets the password successfully', function () {
    Event::fake();
    $user = User::factory()->create(['password' => Hash::make('oldpassword')]);
    $token = Password::broker()->createToken($user);

    $response = $this->postJson(route('api.v1.auth.password.reset'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'newpassword1',
        'password_confirmation' => 'newpassword1',
    ]);

    $response->assertStatus(200);
    $this->assertTrue(Hash::check('newpassword1', $user->fresh()->password));
    Event::assertDispatched(PasswordReset::class);
});

it('fails to reset password with invalid token', function () {
    $user = User::factory()->create();

    $response = $this->postJson(route('api.v1.auth.password.reset'), [
        'token' => 'invalid-token',
        'email' => $user->email,
        'password' => 'newpassword1',
        'password_confirmation' => 'newpassword1',
    ]);

    $response->assertStatus(400);
});
