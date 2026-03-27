<?php

use App\Http\Controllers\Auth\MagicLinkController;
use App\Http\Controllers\BoothController;
use App\Http\Controllers\BoothStaffController;
use App\Http\Controllers\BoothVisitController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PresenceFeedController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SessionCheckInController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\SessionQuestionController;
use App\Http\Controllers\SessionReactionController;
use App\Http\Controllers\StatusController;
use App\Http\Controllers\SuggestionController;
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

    Route::get('/event/{event:slug}/booths', [BoothController::class, 'index'])->name('event.booths');
    Route::get('/event/{event:slug}/booths/{booth}', [BoothController::class, 'show'])->name('event.booths.show')->scopeBindings();

    Route::post('/event/{event:slug}/booths/{booth}/checkin', [BoothVisitController::class, 'store'])->name('event.booths.checkin')->scopeBindings();
    Route::delete('/event/{event:slug}/booths/{booth}/checkout', [BoothVisitController::class, 'destroy'])->name('event.booths.checkout')->scopeBindings();

    Route::get('/event/{event:slug}/booths/{booth}/leads', [BoothStaffController::class, 'leads'])->name('event.booths.leads')->scopeBindings();
    Route::post('/event/{event:slug}/booths/{booth}/announce', [BoothStaffController::class, 'announce'])->name('event.booths.announce')->scopeBindings();
    Route::get('/event/{event:slug}/booths/{booth}/leads/export', [BoothStaffController::class, 'exportLeads'])->name('event.booths.leads.export')->scopeBindings();

    Route::get('/event/{event:slug}/sessions', [SessionController::class, 'index'])->name('event.sessions');
    Route::get('/event/{event:slug}/sessions/{session}', [SessionController::class, 'show'])->name('event.sessions.show')->scopeBindings();
    Route::post('/event/{event:slug}/sessions', [SessionController::class, 'store'])->name('event.sessions.store');

    Route::post('/event/{event:slug}/sessions/{session}/checkin', [SessionCheckInController::class, 'store'])->name('event.sessions.checkin')->scopeBindings();
    Route::delete('/event/{event:slug}/sessions/{session}/checkout', [SessionCheckInController::class, 'destroy'])->name('event.sessions.checkout')->scopeBindings();

    Route::post('/event/{event:slug}/sessions/{session}/reactions', [SessionReactionController::class, 'store'])->name('event.sessions.reactions.store')->scopeBindings();

    Route::post('/event/{event:slug}/sessions/{session}/questions', [SessionQuestionController::class, 'store'])->name('event.sessions.questions.store')->scopeBindings();
    Route::post('/event/{event:slug}/sessions/{session}/questions/{question}/vote', [SessionQuestionController::class, 'vote'])->name('event.sessions.questions.vote')->scopeBindings();

    Route::get('/event/{event:slug}/suggestions', [SuggestionController::class, 'index'])->name('event.suggestions');
    Route::patch('/event/{event:slug}/suggestions/{suggestion}/decline', [SuggestionController::class, 'decline'])->name('event.suggestions.decline');
    Route::patch('/event/{event:slug}/suggestions/{suggestion}/accept', [SuggestionController::class, 'accept'])->name('event.suggestions.accept');
    Route::get('/event/{event:slug}/search', SearchController::class)->name('event.search');
});

require __DIR__.'/settings.php';
