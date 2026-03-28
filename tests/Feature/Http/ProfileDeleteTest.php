<?php

use App\Models\User;

it('allows a passwordless user to delete their account without providing a password', function () {
    $user = User::factory()->create(['password' => null]);

    $this->actingAs($user)
        ->delete(route('profile.destroy'))
        ->assertRedirect('/');

    $this->assertDatabaseMissing('users', ['id' => $user->id]);
});

it('requires password from users who have one', function () {
    $user = User::factory()->create(['password' => bcrypt('secret')]);

    $this->actingAs($user)
        ->delete(route('profile.destroy'), ['password' => ''])
        ->assertSessionHasErrors('password');

    $this->assertDatabaseHas('users', ['id' => $user->id]);
});
