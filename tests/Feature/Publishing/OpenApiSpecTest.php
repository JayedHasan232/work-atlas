<?php

use App\Modules\Community\Enums\Role;
use App\Modules\Knowledge\Actions\ChangeStatus;
use App\Modules\Knowledge\Enums\FieldStatus;
use App\Modules\Knowledge\Models\FieldDefinition;
use App\Modules\Knowledge\Models\Source;
use App\Modules\Publishing\OpenApiSpec;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Validator;

/*
 * PLAN M21, D76: the OpenAPI description covers every API route, and real responses match its
 * schemas. The schemas allow no extra properties, so a new field in a response fails here until it is
 * described — and checked for personal data.
 */

beforeEach(function () {
    wave1Setup();
    $this->occupation = listedOccupation();
    $source = Source::factory()->create(['url' => 'https://source.example.test/wages']);
    $income = FieldDefinition::factory()->sensitive()->create(['key' => 'income_note', 'wave' => 1, 'label' => ['bn' => 'আয়', 'en' => 'Income']]);
    $revision = recordValue($income, 4, sourceIds: [$source->id], occupationId: $this->occupation->id);
    app(ChangeStatus::class)->handle($revision->field_value_id, FieldStatus::Verified, personActor(Role::Maintainer));
    recordValue(FieldDefinition::query()->where('key', 'work_time')->firstOrFail(), ['day'], occupationId: $this->occupation->id);
});

/**
 * Fails with the validation errors when the JSON does not match the named schema of the spec.
 */
function assertMatchesSpec(string $json, string $schema): void
{
    $spec = app(OpenApiSpec::class)->document();
    $wrapper = json_decode((string) json_encode([
        '$schema' => 'https://json-schema.org/draft/2020-12/schema',
        '$id' => 'https://work-atlas.test/openapi.json',
        '$ref' => "#/components/schemas/{$schema}",
        'components' => $spec['components'],
    ]));
    $validator = new Validator;
    $validator->parser()->setOption('allowUnevaluated', true);
    $result = $validator->validate(json_decode($json), $wrapper);

    expect($result->isValid())->toBeTrue(
        $result->isValid() ? '' : json_encode((new ErrorFormatter)->format($result->error()), JSON_PRETTY_PRINT).' in '.$json,
    );
}

it('is OpenAPI 3.1 with a server address and the license', function () {
    $this->get('/api/v1/openapi.json')->assertOk()
        ->assertJsonPath('openapi', '3.1.0')
        ->assertJsonPath('servers.0.url', url('/api/v1'))
        ->assertJsonPath('info.license.url', 'https://creativecommons.org/licenses/by-sa/4.0/');
});

it('describes every API route, and nothing more', function () {
    $routes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn (RoutingRoute $route): bool => str_starts_with($route->uri(), 'api/v1/'))
        ->map(fn (RoutingRoute $route): string => 'GET /'.substr($route->uri(), strlen('api/v1/')))
        ->sort()->values()->all();
    $described = collect(app(OpenApiSpec::class)->operations())
        ->map(fn (array $operation): string => "{$operation['method']} {$operation['path']}")
        ->sort()->values()->all();

    expect($described)->toBe($routes)
        ->and(collect(Route::getRoutes()->getRoutes())->filter(fn (RoutingRoute $route): bool => str_starts_with($route->uri(), 'api/v1/'))
            ->every(fn (RoutingRoute $route): bool => $route->methods() === ['GET', 'HEAD']))->toBeTrue();
});

it('matches real responses', function (string $url, string $schema) {
    $url = str_replace('{slug}', $this->occupation->slug, $url);
    $response = $this->get($url)->assertOk();

    assertMatchesSpec((string) $response->getContent(), $schema);
})->with([
    'list' => ['/api/v1/occupations', 'OccupationList'],
    'list in a group' => ['/api/v1/occupations?group=71&per_page=1', 'OccupationList'],
    'one' => ['/api/v1/occupations/{slug}', 'OccupationResponse'],
    'fields' => ['/api/v1/fields', 'FieldList'],
    'search' => ['/api/v1/search?q=plumb&locale=en', 'SearchResults'],
    'search in Bangla' => ['/api/v1/search?q=নলমিস্ত্রি', 'SearchResults'],
]);

it('matches error responses', function (string $url, int $status) {
    $response = $this->getJson($url)->assertStatus($status);

    assertMatchesSpec((string) $response->getContent(), 'Error');
})->with([
    'bad group' => ['/api/v1/occupations?group=abc', 422],
    'too many per page' => ['/api/v1/occupations?per_page=500', 422],
    'no query' => ['/api/v1/search', 422],
    'unknown slug' => ['/api/v1/occupations/no-such-job', 404],
]);

it('rejects a schema drift', function () {
    $json = (string) $this->get('/api/v1/fields')->getContent();
    $data = json_decode($json, true);
    $data['data'][0]['author_email'] = 'someone@example.test';

    expect(fn () => assertMatchesSpec((string) json_encode($data), 'FieldList'))->toThrow(Exception::class);
});

it('has a docs page in both languages', function () {
    $this->get('/developers')->assertOk()->assertInertia(fn ($page) => $page
        ->component('publishing/api-docs')
        ->has('docs.operations', 5)
        ->where('docs.specUrl', url('/api/v1/openapi.json')));
    $this->get('/en/developers')->assertOk();
});
