<?php

use App\Models\Connection;
use App\Models\Message;
use App\Models\User;

it('belongs to a connection and sender', function () {
    $sender = User::factory()->create();
    $connection = Connection::factory()->create(['user_a_id' => $sender->id]);

    $message = Message::factory()->create([
        'connection_id' => $connection->id,
        'sender_id' => $sender->id,
    ]);

    expect($message->connection->id)->toBe($connection->id)
        ->and($message->sender->id)->toBe($sender->id);
});

it('has a max length of 500 characters', function () {
    $message = Message::factory()->create(['body' => str_repeat('a', 500)]);

    expect(strlen($message->body))->toBe(500);
});

it('preserves message bodies exactly as sent', function () {
    $body = "First line\nSecond line";
    $message = Message::factory()->create(['body' => $body]);

    expect($message->body)->toBe($body);
});
