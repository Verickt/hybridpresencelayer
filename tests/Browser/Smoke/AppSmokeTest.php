<?php

use App\Models\User;
use Laravel\Fortify\Features;

it('renders public entry pages without browser smoke issues', function () {
    $routes = [
        route('home', absolute: false),
        route('login', absolute: false),
    ];

    if (Features::enabled(Features::registration())) {
        $routes[] = route('register', absolute: false);
    }

    if (Features::enabled(Features::resetPasswords())) {
        $routes[] = route('password.request', absolute: false);
    }

    visit($routes)->assertNoSmoke();
});

it('renders the authenticated dashboard without browser smoke issues', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    visit(route('dashboard', absolute: false))
        ->assertPathIs('/dashboard')
        ->assertNoSmoke();
});
