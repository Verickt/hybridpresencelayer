<?php

use App\Http\Controllers\Auth\MagicLinkController;
use App\Http\Controllers\BlockController;
use App\Http\Controllers\BoothController;
use App\Http\Controllers\BoothDemoController;
use App\Http\Controllers\BoothModerationController;
use App\Http\Controllers\BoothStaffController;
use App\Http\Controllers\BoothTabletController;
use App\Http\Controllers\BoothThreadController;
use App\Http\Controllers\BoothVisitController;
use App\Http\Controllers\CallController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ConnectionListController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventLandingController;
use App\Http\Controllers\EventProfileController;
use App\Http\Controllers\EventSetupController;
use App\Http\Controllers\EventSetupPageController;
use App\Http\Controllers\ManifestController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\OrganizerActionController;
use App\Http\Controllers\ParticipantController;
use App\Http\Controllers\PingController;
use App\Http\Controllers\PresenceFeedController;
use App\Http\Controllers\QrResolveController;
use App\Http\Controllers\QuickJoinController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SessionCheckInController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\SessionModerateController;
use App\Http\Controllers\SessionQrDisplayController;
use App\Http\Controllers\SessionQuestionController;
use App\Http\Controllers\SessionQuestionReplyController;
use App\Http\Controllers\SessionReactionController;
use App\Http\Controllers\SharedInterestController;
use App\Http\Controllers\StatusController;
use App\Http\Controllers\SuggestionController;
use App\Http\Controllers\VideoCallController;
use Illuminate\Support\Facades\Route;

Route::get('/event/{event:slug}/manifest.json', ManifestController::class)->name('event.manifest');

Route::get('/', EventLandingController::class)->name('home');

Route::post('/magic-link', [MagicLinkController::class, 'send'])
    ->middleware('throttle:5,1')
    ->name('magic-link.send');
Route::get('/magic-link/{token}', [MagicLinkController::class, 'authenticate'])->name('magic-link.authenticate');

Route::get('/event/{event:slug}/join', [QuickJoinController::class, 'show'])->name('event.join');
Route::post('/event/{event:slug}/join', [QuickJoinController::class, 'store'])->name('event.join.store');

Route::middleware(['auth'])->group(function () {
    // Onboarding wizard
    Route::get('/event/{event:slug}/onboarding/type', [OnboardingController::class, 'typeSelection'])->name('event.onboarding.type');
    Route::post('/event/{event:slug}/onboarding/type', [OnboardingController::class, 'saveType']);
    Route::get('/event/{event:slug}/onboarding/tags', [OnboardingController::class, 'interestTags'])->name('event.onboarding.tags');
    Route::post('/event/{event:slug}/onboarding/tags', [OnboardingController::class, 'saveTags']);
    Route::get('/event/{event:slug}/onboarding/icebreaker', [OnboardingController::class, 'icebreaker'])->name('event.onboarding.icebreaker');
    Route::post('/event/{event:slug}/onboarding/icebreaker', [OnboardingController::class, 'saveIcebreaker']);
    Route::get('/event/{event:slug}/onboarding/email', [OnboardingController::class, 'email'])->name('event.onboarding.email');
    Route::post('/event/{event:slug}/onboarding/email', [OnboardingController::class, 'saveEmail'])->name('event.onboarding.email.save');
    Route::get('/event/{event:slug}/onboarding/ready', [OnboardingController::class, 'ready'])->name('event.onboarding.ready');

    Route::get('/event/{event:slug}/feed', PresenceFeedController::class)->name('event.feed');
    Route::patch('/event/{event:slug}/status', [StatusController::class, 'update'])->name('event.status.update');
    Route::patch('/event/{event:slug}/invisible', [StatusController::class, 'toggleInvisible'])->name('event.status.invisible');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::get('/notifications/count', [NotificationController::class, 'unreadCount'])->name('notifications.count');
    Route::patch('/event/{event:slug}/notification-preferences', [NotificationController::class, 'updatePreferences'])->name('event.notification-prefs');

    Route::get('/event/{event:slug}/booths', [BoothController::class, 'index'])->name('event.booths');
    Route::get('/event/{event:slug}/booths/{booth}', [BoothController::class, 'show'])->name('event.booths.show')->scopeBindings();
    Route::get('/event/{event:slug}/booths/{booth}/tablet', BoothTabletController::class)->name('event.booths.tablet')->scopeBindings();

    Route::post('/event/{event:slug}/booths/{booth}/checkin', [BoothVisitController::class, 'store'])->name('event.booths.checkin')->scopeBindings();
    Route::delete('/event/{event:slug}/booths/{booth}/checkout', [BoothVisitController::class, 'destroy'])->name('event.booths.checkout')->scopeBindings();
    Route::post('/event/{event:slug}/booths/{booth}/threads', [BoothThreadController::class, 'store'])->name('event.booths.threads.store')->scopeBindings();
    Route::post('/event/{event:slug}/booths/{booth}/threads/{thread}/replies', [BoothThreadController::class, 'reply'])->name('event.booths.threads.replies.store')->scopeBindings();
    Route::post('/event/{event:slug}/booths/{booth}/threads/{thread}/vote', [BoothThreadController::class, 'vote'])->name('event.booths.threads.vote')->scopeBindings();
    Route::patch('/event/{event:slug}/booths/{booth}/threads/{thread}/follow-up', [BoothThreadController::class, 'followUp'])->name('event.booths.threads.follow-up')->scopeBindings();
    Route::patch('/event/{event:slug}/booths/{booth}/threads/{thread}/answer', [BoothModerationController::class, 'answer'])->name('event.booths.threads.answer')->scopeBindings();
    Route::patch('/event/{event:slug}/booths/{booth}/threads/{thread}/pin', [BoothModerationController::class, 'pin'])->name('event.booths.threads.pin')->scopeBindings();
    Route::post('/event/{event:slug}/booths/{booth}/demos', [BoothDemoController::class, 'store'])->name('event.booths.demos.start')->scopeBindings();
    Route::patch('/event/{event:slug}/booths/{booth}/demos/{demo}/end', [BoothDemoController::class, 'end'])->name('event.booths.demos.end')->scopeBindings();

    Route::get('/event/{event:slug}/booths/{booth}/leads', [BoothStaffController::class, 'leads'])->name('event.booths.leads')->scopeBindings();
    Route::post('/event/{event:slug}/booths/{booth}/announce', [BoothStaffController::class, 'announce'])->name('event.booths.announce')->scopeBindings();
    Route::get('/event/{event:slug}/booths/{booth}/leads/export', [BoothStaffController::class, 'exportLeads'])->name('event.booths.leads.export')->scopeBindings();

    Route::get('/event/{event:slug}/sessions', [SessionController::class, 'index'])->name('event.sessions');
    Route::get('/event/{event:slug}/sessions/{session}', [SessionController::class, 'show'])->name('event.sessions.show')->scopeBindings();
    Route::get('/event/{event:slug}/sessions/{session}/moderate', [SessionController::class, 'moderate'])->name('event.sessions.moderate')->scopeBindings();
    Route::get('/event/{event:slug}/sessions/{session}/connections', [SessionController::class, 'postSession'])
        ->name('event.sessions.post-session')->scopeBindings();
    Route::get('/event/{event:slug}/sessions/{session}/qr-display', SessionQrDisplayController::class)->name('event.sessions.qr-display')->scopeBindings();
    Route::post('/event/{event:slug}/sessions', [SessionController::class, 'store'])->name('event.sessions.store');
    Route::delete('/event/{event:slug}/sessions/{session}', [SessionController::class, 'destroy'])->name('event.sessions.destroy')->scopeBindings();
    Route::post('/event/{event:slug}/booths', [BoothController::class, 'store'])->name('event.booths.store');
    Route::delete('/event/{event:slug}/booths/{booth}', [BoothController::class, 'destroy'])->name('event.booths.destroy')->scopeBindings();

    Route::post('/event/{event:slug}/sessions/{session}/checkin', [SessionCheckInController::class, 'store'])->name('event.sessions.checkin')->scopeBindings();
    Route::delete('/event/{event:slug}/sessions/{session}/checkout', [SessionCheckInController::class, 'destroy'])->name('event.sessions.checkout')->scopeBindings();

    Route::post('/event/{event:slug}/sessions/{session}/reactions', [SessionReactionController::class, 'store'])->name('event.sessions.reactions.store')->scopeBindings();

    Route::post('/event/{event:slug}/sessions/{session}/questions', [SessionQuestionController::class, 'store'])->name('event.sessions.questions.store')->scopeBindings();
    Route::post('/event/{event:slug}/sessions/{session}/questions/{question}/vote', [SessionQuestionController::class, 'vote'])->name('event.sessions.questions.vote')->scopeBindings();
    Route::post('/event/{event:slug}/sessions/{session}/questions/{question}/replies', [SessionQuestionReplyController::class, 'store'])->name('event.sessions.questions.replies.store')->scopeBindings();
    Route::post('/event/{event:slug}/sessions/{session}/questions/{question}/replies/{reply}/vote', [SessionQuestionReplyController::class, 'vote'])->name('event.sessions.questions.replies.vote')->scopeBindings();

    Route::post('/event/{event:slug}/sessions/{session}/questions/{question}/pin', [SessionModerateController::class, 'pin'])->name('event.sessions.questions.pin')->scopeBindings();
    Route::post('/event/{event:slug}/sessions/{session}/questions/{question}/hide', [SessionModerateController::class, 'hide'])->name('event.sessions.questions.hide')->scopeBindings();
    Route::post('/event/{event:slug}/sessions/{session}/questions/{question}/answer', [SessionModerateController::class, 'answer'])->name('event.sessions.questions.answer')->scopeBindings();

    Route::get('/event/{event:slug}/suggestions', [SuggestionController::class, 'index'])->name('event.suggestions');
    Route::patch('/event/{event:slug}/suggestions/{suggestion}/decline', [SuggestionController::class, 'decline'])->name('event.suggestions.decline');
    Route::patch('/event/{event:slug}/suggestions/{suggestion}/accept', [SuggestionController::class, 'accept'])->name('event.suggestions.accept');
    Route::get('/event/{event:slug}/search', SearchController::class)->name('event.search');

    Route::get('/event/{event:slug}/dashboard', DashboardController::class)->name('event.dashboard');

    Route::get('/events/create', EventSetupPageController::class)->name('events.create');
    Route::post('/events', [EventSetupController::class, 'store'])->name('events.store');
    Route::patch('/events/{event:slug}', [EventSetupController::class, 'update'])->name('events.update');
    Route::post('/events/{event:slug}/import-attendees', [EventSetupController::class, 'importAttendees'])->name('events.import-attendees');

    Route::post('/event/{event:slug}/qr/resolve', QrResolveController::class)->name('event.qr.resolve');

    Route::get('/event/{event:slug}/connections', ConnectionListController::class)->name('event.connections');
    Route::get('/event/{event:slug}/participants', [ParticipantController::class, 'index'])->name('event.participants');
    Route::delete('/event/{event:slug}/participants/{user}', [ParticipantController::class, 'destroy'])->name('event.participants.destroy');
    Route::get('/event/{event:slug}/profile', EventProfileController::class)->name('event.profile');

    Route::post('/event/{event:slug}/ping/{user}', [PingController::class, 'store'])->name('event.ping');
    Route::patch('/event/{event:slug}/ping/{ping}/ignore', [PingController::class, 'ignore'])->name('event.ping.ignore');

    Route::post('/event/{event:slug}/block/{user}', [BlockController::class, 'store'])->name('event.block');
    Route::delete('/event/{event:slug}/block/{user}', [BlockController::class, 'destroy'])->name('event.unblock');
    Route::post('/event/{event:slug}/report/{user}', [ReportController::class, 'store'])->name('event.report');

    Route::post('/event/{event:slug}/share-interest', [SharedInterestController::class, 'store'])->name('event.share-interest');
    Route::get('/event/{event:slug}/shared-interests', [SharedInterestController::class, 'index'])->name('event.shared-interests');

    Route::get('/event/{event:slug}/connections/{connection}/chat', ChatController::class)->name('event.connection.chat');
    Route::get('/event/{event:slug}/connections/{connection}/call/{call}', VideoCallController::class)->name('event.connection.call');

    Route::get('/connections/{connection}/messages', [MessageController::class, 'index'])->name('connection.messages.index');
    Route::post('/connections/{connection}/messages', [MessageController::class, 'store'])->name('connection.messages.store');

    Route::post('/connections/{connection}/call', [CallController::class, 'start'])->name('connection.call.start');
    Route::patch('/connections/{connection}/call/{call}/extend', [CallController::class, 'extend'])->name('connection.call.extend');
    Route::patch('/connections/{connection}/call/{call}/end', [CallController::class, 'end'])->name('connection.call.end');

    Route::post('/event/{event:slug}/actions/announce', [OrganizerActionController::class, 'announce'])->name('event.actions.announce');
    Route::post('/event/{event:slug}/actions/serendipity-wave', [OrganizerActionController::class, 'serendipityWave'])->name('event.actions.serendipity-wave');
});

Route::scopeBindings()->group(function () {
    Route::get('/event/{event:slug}/sessions/{session}/qr-checkin', fn () => abort(204))
        ->middleware('signed:relative')
        ->name('event.sessions.qr-checkin');

    Route::get('/event/{event:slug}/booths/{booth}/qr-checkin', fn () => abort(204))
        ->middleware('signed:relative')
        ->name('event.booths.qr-checkin');
});

require __DIR__.'/settings.php';
