<?php

use App\Models\User;

test('home page renders for guests', function () {
    $response = $this->get(route('home'));
    $response->assertOk();
});

test('home page renders for authenticated users', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('home'));
    $response->assertOk();
});
