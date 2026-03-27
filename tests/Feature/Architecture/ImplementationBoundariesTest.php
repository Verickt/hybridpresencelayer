<?php

arch('controllers are consistently named and lightweight entry points')
    ->expect('App\Http\Controllers')
    ->toBeClasses()
    ->toHaveSuffix('Controller');

arch('controllers do not send notifications directly')
    ->expect('App\Http\Controllers')
    ->not->toUse('Illuminate\Support\Facades\Notification');

arch('controllers do not broadcast realtime events directly')
    ->expect('App\Http\Controllers')
    ->not->toUse([
        'Illuminate\Support\Facades\Broadcast',
        'Illuminate\Support\Facades\Event',
        'Illuminate\Support\Facades\DB',
    ]);
