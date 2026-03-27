<?php

use App\Models\Connection;
use App\Models\ContactCard;
use App\Models\User;

it('stores contact card data for a connection', function () {
    $user = User::factory()->create();
    $connection = Connection::factory()->create(['user_a_id' => $user->id]);

    $card = ContactCard::factory()->create([
        'user_id' => $user->id,
        'connection_id' => $connection->id,
    ]);

    expect($card->user->id)->toBe($user->id)
        ->and($card->connection->id)->toBe($connection->id);
});

it('persists the exported contact snapshot even if the user profile changes', function () {
    $user = User::factory()->create([
        'name' => 'Taylor',
        'email' => 'taylor@example.com',
        'company' => 'Acme Corp',
    ]);
    $connection = Connection::factory()->create(['user_a_id' => $user->id]);
    $card = ContactCard::factory()->create([
        'user_id' => $user->id,
        'connection_id' => $connection->id,
        'name' => 'Taylor',
        'email' => 'taylor@example.com',
        'company' => 'Acme Corp',
    ]);

    $user->update(['name' => 'Taylor Updated', 'company' => 'New Corp']);

    expect($card->fresh()->name)->toBe('Taylor')
        ->and($card->fresh()->company)->toBe('Acme Corp');
});
