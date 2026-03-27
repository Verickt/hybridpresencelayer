<?php

use App\Http\Controllers\Auth\MagicLinkController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PresenceFeedController;
use App\Http\Controllers\StatusController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome', [
    'canRegister' => false,
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

Route::post('/magic-link', [MagicLinkController::class, 'send'])
    ->middleware('throttle:5,1')
    ->name('magic-link.send');
Route::get('/magic-link/{token}', [MagicLinkController::class, 'authenticate'])->name('magic-link.authenticate');

Route::middleware(['auth'])->group(function () {
    Route::get('/event/{event:slug}/feed', PresenceFeedController::class)->name('event.feed');
    Route::patch('/event/{event:slug}/status', [StatusController::class, 'update'])->name('event.status.update');
    Route::patch('/event/{event:slug}/invisible', [StatusController::class, 'toggleInvisible'])->name('event.status.invisible');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::get('/notifications/count', [NotificationController::class, 'unreadCount'])->name('notifications.count');
    Route::patch('/event/{event:slug}/notification-preferences', [NotificationController::class, 'updatePreferences'])->name('event.notification-prefs');
});

require __DIR__.'/settings.php';
