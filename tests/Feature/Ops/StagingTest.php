<?php

use App\Modules\Taxonomy\Models\Occupation;
use Database\Seeders\StagingSeeder;
use Illuminate\Support\Facades\Config;

// PLAN M22 (A12): staging sits behind HTTP basic auth, is never indexed and holds fake data only.

function onStaging(): void
{
    app()['env'] = 'staging';
    Config::set('atlas.staging.basic_auth_user', 'team');
    Config::set('atlas.staging.basic_auth_password', 'correct horse');
}

it('does nothing outside staging', function () {
    $this->get('/up')->assertOk()->assertHeaderMissing('WWW-Authenticate');
    $this->get('/api/v1/fields')->assertOk();
});

it('asks for basic auth on staging', function (string $url) {
    onStaging();

    $this->get($url)->assertUnauthorized()
        ->assertHeader('WWW-Authenticate', 'Basic realm="Work Atlas staging", charset="UTF-8"')
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
})->with(['/', '/occupations', '/api/v1/fields', '/robots.txt', '/admin']);

it('lets the team in with the right password, still noindex', function () {
    onStaging();

    $this->withBasicAuth('team', 'correct horse')->get('/api/v1/fields')
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    $this->withBasicAuth('team', 'wrong')->get('/api/v1/fields')->assertUnauthorized();
});

it('keeps the health check open for the uptime monitor', function () {
    onStaging();

    $this->get('/up')->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow');
});

it('lets nobody in when no password is configured', function () {
    onStaging();
    Config::set('atlas.staging.basic_auth_password', null);

    $this->withBasicAuth('team', '')->get('/api/v1/fields')->assertUnauthorized();
});

it('seeds clearly fake occupations with values, and can run again', function () {
    $this->seed(StagingSeeder::class);
    $this->seed(StagingSeeder::class);

    $occupations = Occupation::query()->where('slug', 'like', StagingSeeder::SLUG_PREFIX.'%')->get();

    expect($occupations)->toHaveCount(12)
        ->and(Occupation::query()->count())->toBe(12);

    $this->get('/en/occupations/'.StagingSeeder::SLUG_PREFIX.'1')
        ->assertOk()
        ->assertSee('Example occupation 1 (staging)');
    $fields = $this->getJson('/api/v1/occupations/'.StagingSeeder::SLUG_PREFIX.'1')
        ->assertOk()
        ->assertJsonPath('data.names.bn', 'নমুনা পেশা ১ (স্টেজিং)')
        ->json('data.fields');
    $wave1 = array_filter($fields, fn (array $field): bool => $field['group'] !== 'names' && $field['key'] !== 'names');

    expect($wave1)->not->toBeEmpty()
        ->and(array_filter($wave1, fn (array $field): bool => $field['values'] === []))->toBe([]);
});

it('refuses to seed fake data in production', function () {
    app()['env'] = 'production';

    expect(fn () => (new StagingSeeder)->setContainer(app())->__invoke())->toThrow(RuntimeException::class, 'never runs in production');
    expect(Occupation::query()->count())->toBe(0);
});
