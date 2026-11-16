<?php

use App\Models\User;
use App\Modules\Community\Models\ContributionConsent;

// Signing up needs agreement to the contribution terms (PLAN M8). The server checks it; the page
// shows why the account was not created.

test('a visitor signs up only after agreeing to the contribution terms', function () {
    $page = visit('/register');

    $page->assertSee('অবদানের শর্তগুলো পড়ুন')
        ->assertNoJavaScriptErrors()
        ->assertNoAccessibilityIssues()
        ->fill('name', 'Rahima Begum')
        ->fill('email', 'rahima@example.test')
        ->fill('password', 'a-long-test-password')
        ->fill('password_confirmation', 'a-long-test-password')
        ->click('@register-user-button')
        ->assertSee('অ্যাকাউন্ট খুলতে অবদানের শর্তে রাজি হতে হবে।')
        ->assertPathIs('/register');

    expect(User::query()->count())->toBe(0);

    $page->fill('password', 'a-long-test-password')
        ->fill('password_confirmation', 'a-long-test-password')
        ->click('#contribution_terms')
        ->click('@register-user-button')
        ->assertPathIsNot('/register');

    expect(ContributionConsent::query()->where('user_id', User::query()->sole()->id)->exists())->toBeTrue();
});

test('the contribution terms page opens in both languages', function () {
    visit('/contribution-terms')
        ->assertSee('অবদানের শর্ত')
        ->assertNoJavaScriptErrors()
        ->assertNoAccessibilityIssues();

    visit('/en/contribution-terms')
        ->assertSee('Contribution terms')
        ->assertSee('CC BY-SA 4.0');
});
