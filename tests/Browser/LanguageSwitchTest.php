<?php

// The visible text is checked first: those assertions wait for the page, so the language attribute
// is read after the switch has finished.

test('the switcher turns the home page into English and back', function () {
    $page = visit('/');

    $page->assertSee('লগ ইন')
        ->assertScript('document.documentElement.lang', 'bn')
        ->click('English')
        ->assertSee('Log in')
        ->assertDontSee('লগ ইন')
        ->assertPathIs('/en')
        ->assertScript('document.documentElement.lang', 'en')
        ->assertNoJavaScriptErrors()
        ->assertNoAccessibilityIssues();

    $page->click('বাংলা')
        ->assertSee('লগ ইন')
        ->assertPathIs('/')
        ->assertScript('document.documentElement.lang', 'bn');
});

test('the switcher changes the language of pages that have one URL', function () {
    $page = visit('/login');

    $page->assertSee('আপনার অ্যাকাউন্টে লগ ইন করুন')
        ->assertScript('document.documentElement.lang', 'bn')
        ->click('English')
        ->assertSee('Log in to your account')
        ->assertPathIs('/login')
        ->assertScript('document.documentElement.lang', 'en')
        ->assertNoJavaScriptErrors()
        ->assertNoAccessibilityIssues();
});
