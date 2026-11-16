<?php

use App\Modules\Knowledge\Models\FieldDefinition;

/*
 * PLAN M23: the main public pages, on a phone and in both languages, load without JavaScript errors
 * and without accessibility issues (axe). Pages behind a sign-in have their own browser tests.
 */

beforeEach(function () {
    wave1Setup();
    $this->occupation = listedOccupation();
    recordValue(FieldDefinition::query()->where('key', 'physical_workload')->firstOrFail(), 3, occupationId: $this->occupation->id);
});

dataset('public pages', [
    'home' => fn (): string => '/',
    'occupations' => fn (): string => '/occupations',
    'browse a group' => fn (): string => '/occupations/groups/7126',
    'occupation' => fn (): string => "/occupations/{$this->occupation->slug}",
    'report an error' => fn (): string => "/occupations/{$this->occupation->slug}/report",
    'talk page' => fn (): string => "/occupations/{$this->occupation->slug}/talk",
    'search' => fn (): string => '/search?q=plumber',
    'guide' => fn (): string => '/guide',
    'compare' => fn (): string => "/compare?o[]={$this->occupation->slug}",
    'developers' => fn (): string => '/developers',
    'policies' => fn (): string => '/policies',
    'privacy policy' => fn (): string => '/policies/privacy',
    'contribution terms' => fn (): string => '/contribution-terms',
    'log in' => fn (): string => '/login',
    'register' => fn (): string => '/register',
]);

test('a public page has no accessibility issues on a phone', function (string $path) {
    foreach (['', '/en'] as $prefix) {
        $url = in_array($path, ['/login', '/register'], true) ? $path : ($prefix === '' ? $path : $prefix.rtrim($path, '/'));

        visit($url)->on()->mobile()
            ->assertDontSee('Not Found')
            ->assertDontSee('Server Error')
            ->assertNoJavaScriptErrors()
            ->assertNoAccessibilityIssues();
    }
})->with('public pages');
