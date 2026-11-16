<?php

use App\Modules\Community\Enums\Role;
use App\Modules\Knowledge\Actions\ChangeStatus;
use App\Modules\Knowledge\Enums\FieldStatus;
use App\Modules\Knowledge\Models\FieldDefinition;
use App\Modules\Knowledge\Models\Source;

/*
 * CLAUDE.md, "Rules enforced in code", for the API (PLAN M21): a sensitive value that is not verified
 * keeps its status, but its value and sources stay out of every response.
 */

beforeEach(function () {
    wave1Setup();
    $this->occupation = listedOccupation();
    $this->source = Source::factory()->create(['url' => 'https://source.example.test/hidden-source']);
    $this->definition = FieldDefinition::factory()->sensitive()->text()->create([
        'key' => 'health_note',
        'wave' => 1,
        'group' => 'health',
        'label' => ['bn' => 'স্বাস্থ্য', 'en' => 'Health'],
    ]);
    $this->revision = recordValue($this->definition, 'SECRET-HEALTH-VALUE', sourceIds: [$this->source->id], occupationId: $this->occupation->id, locale: 'en');
});

/**
 * @return array<string, mixed>
 */
function apiField(string $slug, string $key): array
{
    $fields = test()->getJson("/api/v1/occupations/{$slug}")->assertOk()->json('data.fields');

    return collect($fields)->firstWhere('key', $key);
}

it('withholds an unverified sensitive value and its sources', function (FieldStatus $status) {
    if ($status !== FieldStatus::Sourced) {
        app(ChangeStatus::class)->handle($this->revision->field_value_id, $status, personActor(Role::Maintainer));
    }

    $response = $this->get("/api/v1/occupations/{$this->occupation->slug}")->assertOk();

    expect(apiField($this->occupation->slug, 'health_note')['values'][0])->toMatchArray([
        'status' => $status->value,
        'withheld' => true,
        'value' => null,
        'sources' => [],
    ])->and($response->json('data.sources'))->toBe([]);
    $response->assertDontSee('SECRET-HEALTH-VALUE', false)->assertDontSee('hidden-source', false);
})->with([FieldStatus::Sourced, FieldStatus::CommunitySupported, FieldStatus::Disputed]);

it('shows a verified sensitive value with its sources', function () {
    app(ChangeStatus::class)->handle($this->revision->field_value_id, FieldStatus::Verified, personActor(Role::Maintainer));

    $response = $this->get("/api/v1/occupations/{$this->occupation->slug}")->assertOk();

    expect(apiField($this->occupation->slug, 'health_note')['values'][0])->toMatchArray([
        'status' => 'verified',
        'withheld' => false,
        'value' => 'SECRET-HEALTH-VALUE',
        'sources' => [$this->source->id],
    ])->and($response->json('data.sources.0.url'))->toBe('https://source.example.test/hidden-source');
});

it('never puts a sensitive value in the other endpoints', function (string $url) {
    $this->get(str_replace('{slug}', $this->occupation->slug, $url))->assertOk()
        ->assertDontSee('SECRET-HEALTH-VALUE', false)
        ->assertDontSee('hidden-source', false);
})->with(['/api/v1/occupations', '/api/v1/fields', '/api/v1/search?q=plumber&locale=en']);

it('describes the sensitive field as sensitive', function () {
    $field = collect($this->getJson('/api/v1/fields')->assertOk()->json('data'))->firstWhere('key', 'health_note');

    expect($field)->toMatchArray(['sensitive' => true, 'requires_source' => true]);
});
