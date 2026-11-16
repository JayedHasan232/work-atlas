<?php

use App\Modules\Publishing\Enums\Policy;
use Illuminate\Support\Facades\Lang;
use Inertia\Testing\AssertableInertia as Assert;

/*
 * PLAN M23: the policy pages (code of conduct, editorial, privacy, not advice, neutrality and funding)
 * are public in both languages, say they are drafts, and are noindex while the index gate is off.
 */

it('lists every policy and the contribution terms', function () {
    $this->get('/en/policies')
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex')
        ->assertInertia(fn (Assert $page) => $page
            ->component('publishing/policies')
            ->has('policies', count(Policy::cases()))
            ->where('policies.0', ['slug' => 'code-of-conduct', 'key' => 'code_of_conduct'])
            ->where('translations.policies.title', 'Policies')
            ->where('translations.terms.title', 'Contribution terms'));
});

it('shows each policy in Bangla and English with the draft notice', function (Policy $policy) {
    foreach (['bn' => '', 'en' => '/en'] as $locale => $prefix) {
        $this->get("{$prefix}/policies/{$policy->value}")
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex')
            ->assertInertia(fn (Assert $page) => $page
                ->component('publishing/policy')
                ->where('policy', ['slug' => $policy->value, 'key' => $policy->key()])
                ->where('localization.locale', $locale)
                ->where('translations.policies.draft_notice', Lang::get('policies.draft_notice', [], $locale))
                ->where(
                    "translations.policies.{$policy->key()}.title",
                    Lang::get("policies.{$policy->key()}.title", [], $locale),
                ));
    }
})->with(Policy::cases());

it('answers 404 for a policy that does not exist', function () {
    $this->get('/en/policies/terms-of-sale')->assertNotFound();
});

it('has a title, a summary and sections in both languages for every policy', function (Policy $policy) {
    foreach (['bn', 'en'] as $locale) {
        $text = Lang::get("policies.{$policy->key()}", [], $locale);

        expect($text)->toBeArray()
            ->and($text['title'])->toBeString()->not->toBeEmpty()
            ->and($text['summary'])->toBeString()->not->toBeEmpty()
            ->and($text['sections'])->toBeArray()->not->toBeEmpty();
    }
})->with(Policy::cases());

it('renders exactly the sections the translations define', function (Policy $policy) {
    // The page lists its sections in resources/js/lib/policies.ts; a section missing there would never show.
    $source = file_get_contents(resource_path('js/lib/policies.ts')) ?: '';
    preg_match_all("/'policies\\.{$policy->key()}\\.sections\\.([a-z_]+)'/", $source, $matches);

    expect($matches[1])->toBe(array_keys(Lang::get("policies.{$policy->key()}.sections", [], 'en')));
})->with(Policy::cases());
