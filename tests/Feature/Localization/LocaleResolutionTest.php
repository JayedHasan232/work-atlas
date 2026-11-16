<?php

use App\Http\Middleware\SetLocale;
use Illuminate\Support\Facades\Lang;
use Inertia\Testing\AssertableInertia as Assert;

test('the home page is Bangla at the root', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('<html lang="bn"', false)
        ->assertInertia(fn (Assert $page) => $page
            ->component('welcome')
            ->where('localization.locale', 'bn')
            ->where('localization.prefix', '')
            ->where('translations.common.nav.log_in', 'লগ ইন')
            ->where('translations.home.title', 'কাজ'));
});

test('the English home page lives under /en', function () {
    $this->get('/en')
        ->assertOk()
        ->assertSee('<html lang="en"', false)
        ->assertInertia(fn (Assert $page) => $page
            ->component('welcome')
            ->where('localization.locale', 'en')
            ->where('localization.prefix', '/en')
            ->where('translations.common.nav.log_in', 'Log in'));
});

test('public pages point to themselves and to the other language', function () {
    $expected = [
        ['code' => 'bn', 'name' => 'বাংলা', 'url' => route('home')],
        ['code' => 'en', 'name' => 'English', 'url' => route('en.home')],
    ];

    $this->get('/')
        ->assertInertia(fn (Assert $page) => $page
            ->where('localization.canonical', route('home'))
            ->where('localization.locales', $expected));

    $this->get('/en')
        ->assertInertia(fn (Assert $page) => $page
            ->where('localization.canonical', route('en.home'))
            ->where('localization.locales', $expected));
});

test('pages without a language in the URL default to Bangla', function () {
    $this->get('/login')
        ->assertOk()
        ->assertSee('<html lang="bn"', false)
        ->assertInertia(fn (Assert $page) => $page
            ->component('auth/login')
            ->where('localization.locale', 'bn')
            ->where('localization.canonical', null)
            ->where('localization.locales.0.url', null)
            ->where('localization.locales.1.url', null)
            ->where('translations.auth.login.title', 'আপনার অ্যাকাউন্টে লগ ইন করুন'));
});

test('the language of the last public page carries over to other pages', function () {
    $this->get('/en')->assertSessionHas(SetLocale::SESSION_KEY, 'en');

    $this->get('/login')
        ->assertSee('<html lang="en"', false)
        ->assertInertia(fn (Assert $page) => $page
            ->where('localization.locale', 'en')
            ->where('localization.prefix', '/en')
            ->where('translations.auth.login.title', 'Log in to your account'));

    // The URL decides on public pages: the Bangla home page switches back.
    $this->get('/')->assertSessionHas(SetLocale::SESSION_KEY, 'bn');
    $this->get('/login')->assertInertia(fn (Assert $page) => $page->where('localization.locale', 'bn'));
});

test('the switcher saves the language for pages without one in the URL', function () {
    $this->from('/login')
        ->post(route('locale.update'), ['locale' => 'en'])
        ->assertRedirect('/login')
        ->assertSessionHas(SetLocale::SESSION_KEY, 'en');

    $this->get('/login')->assertInertia(fn (Assert $page) => $page->where('localization.locale', 'en'));
});

test('the switcher rejects a language the site does not have', function () {
    $this->from('/login')
        ->post(route('locale.update'), ['locale' => 'fr'])
        ->assertRedirect('/login')
        ->assertSessionHasErrors('locale');

    $this->get('/login')->assertInertia(fn (Assert $page) => $page->where('localization.locale', 'bn'));
});

test('each page gets only the translation groups it needs', function () {
    $this->get('/')
        ->assertInertia(fn (Assert $page) => $page
            ->has('translations', 2)
            ->has('translations.common')
            ->has('translations.home'));

    $this->get('/login')
        ->assertInertia(fn (Assert $page) => $page
            ->has('translations', 2)
            ->has('translations.common')
            ->has('translations.auth'));
});

test('a line missing in Bangla falls back to English', function () {
    Lang::get('common', [], 'en');
    Lang::addLines(['common.only_in_english' => 'Only in English'], 'en');

    $this->get('/')
        ->assertInertia(fn (Assert $page) => $page
            ->where('translations.common.only_in_english', 'Only in English')
            ->where('translations.common.nav.log_in', 'লগ ইন'));
});

test('pages without a language in the URL have no /en copy', function () {
    $this->get('/en/login')->assertNotFound();
});
