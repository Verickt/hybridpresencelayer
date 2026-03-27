<?php

use App\Models\InterestTag;

it('renders the onboarding flow with magic link and participant setup', function () {
    InterestTag::factory()->count(3)->create();

    visit(route('login', absolute: false))
        ->on()->iPhone14Pro()
        ->assertPresent('@magic-link-name')
        ->assertPresent('@magic-link-email')
        ->assertPresent('@participant-type-physical')
        ->assertPresent('@participant-type-remote')
        ->assertPresent('@interest-tag-picker')
        ->assertPresent('@icebreaker-question')
        ->assertPresent('@magic-link-submit')
        ->assertNoSmoke()
        ->assertNoAccessibilityIssues();
});

it('submits the onboarding form with prefilled invitation data', function () {
    $tags = InterestTag::factory()->count(3)->create();
    $firstTagId = $tags[0]->id;
    $secondTagId = $tags[1]->id;
    $thirdTagId = $tags[2]->id;

    visit(route('login', absolute: false))
        ->fill('@magic-link-name', 'Taylor Otwell')
        ->fill('@magic-link-email', 'taylor@example.com')
        ->click('@participant-type-physical')
        ->click('@interest-tag-'.$firstTagId)
        ->click('@interest-tag-'.$secondTagId)
        ->click('@interest-tag-'.$thirdTagId)
        ->click('@magic-link-submit')
        ->assertPresent('@magic-link-success')
        ->assertSee('Check your inbox')
        ->assertNoSmoke()
        ->assertNoAccessibilityIssues();
});

it('shows validation errors when onboarding fields are missing', function () {
    visit(route('login', absolute: false))
        ->click('@magic-link-submit')
        ->assertPresent('@magic-link-errors')
        ->assertSee('The email field is required')
        ->assertSee('The participant type field is required')
        ->assertNoAccessibilityIssues();
});

it('requires three interest tags before continuing', function () {
    $tags = InterestTag::factory()->count(3)->create();
    $firstTagId = $tags[0]->id;

    visit(route('login', absolute: false))
        ->fill('@magic-link-name', 'Taylor Otwell')
        ->fill('@magic-link-email', 'taylor@example.com')
        ->click('@participant-type-remote')
        ->click('@interest-tag-'.$firstTagId)
        ->click('@magic-link-submit')
        ->assertPresent('@interest-tag-error')
        ->assertSee('Select exactly 3 interest tags')
        ->assertNoAccessibilityIssues();
});
