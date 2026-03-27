<?php

use App\Models\User;

it('authenticates a user through the real login form', function () {
    $user = User::factory()->create([
        'email' => 'taylor@example.com',
    ]);

    $page = visit(route('login', absolute: false))->on()->iPhone14Pro();

    $page->assertSee('Log in to your account')
        ->fill('input[name="email"]', $user->email)
        ->fill('input[name="password"]', 'password')
        ->click('@login-button')
        ->assertPathIs('/dashboard')
        ->assertNoSmoke();

    $this->assertAuthenticatedAs($user);
});

it('keeps the user on the login page when credentials are invalid', function () {
    $user = User::factory()->create([
        'email' => 'taylor@example.com',
    ]);

    $page = visit(route('login', absolute: false));

    $page->fill('input[name="email"]', $user->email)
        ->fill('input[name="password"]', 'wrong-password')
        ->click('@login-button')
        ->assertPathIs('/login')
        ->assertSee('These credentials do not match our records.');

    $this->assertGuest();
});
