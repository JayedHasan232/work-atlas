<?php

use App\Modules\Community\Enums\Role;
use App\Modules\Knowledge\Actions\ChangeStatus;
use App\Modules\Knowledge\Enums\FieldStatus;
use App\Modules\Knowledge\Models\FieldDefinition;
use App\Modules\Knowledge\Models\Source;
use Inertia\Testing\AssertableInertia as Assert;

/*
 * CLAUDE.md, "Rules enforced in code": a sensitive field (income, health, legal) is not rendered
 * publicly unless it is verified. Neither its value nor its sources reach the Inertia props or the
 * HTML; the field shows only its status. The API has its own test, ApiSensitiveGateTest (M21).
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
    $this->revision = recordValue(
        $this->definition,
        'SECRET-HEALTH-VALUE',
        sourceIds: [$this->source->id],
        occupationId: $this->occupation->id,
        locale: 'en',
    );
});

/**
 * @return array<string, mixed>
 */
function gatedField(Assert $page): array
{
    $section = collect($page->toArray()['props']['occupation']['sections'])->firstWhere('key', 'health');

    return collect($section['fields'])->firstWhere('key', 'health_note');
}

it('leaves an unverified sensitive value out of the props', function () {
    $this->get("/en/occupations/{$this->occupation->slug}")->assertInertia(function (Assert $page) {
        expect(gatedField($page))->toMatchArray([
            'status' => 'sourced',
            'withheld' => true,
            'value' => null,
            'sources' => [],
        ])->and($page->toArray()['props']['occupation']['sources'])->toBe([]);
    });
});

it('leaves an unverified sensitive value out of the HTML', function () {
    foreach (["/occupations/{$this->occupation->slug}", "/en/occupations/{$this->occupation->slug}"] as $url) {
        $this->get($url)
            ->assertOk()
            ->assertDontSee('SECRET-HEALTH-VALUE', false)
            ->assertDontSee('hidden-source', false);
    }
});

it('keeps a disputed sensitive value out too', function () {
    app(ChangeStatus::class)->handle($this->revision->field_value_id, FieldStatus::Disputed, personActor(Role::Maintainer));

    $this->get("/en/occupations/{$this->occupation->slug}")
        ->assertDontSee('SECRET-HEALTH-VALUE', false)
        ->assertInertia(fn (Assert $page) => expect(gatedField($page)['withheld'])->toBeTrue());
});

it('shows a sensitive value once it is verified', function () {
    app(ChangeStatus::class)->handle($this->revision->field_value_id, FieldStatus::Verified, personActor(Role::Maintainer));

    $this->get("/en/occupations/{$this->occupation->slug}")
        ->assertSee('SECRET-HEALTH-VALUE', false)
        ->assertInertia(fn (Assert $page) => expect(gatedField($page))->toMatchArray([
            'status' => 'verified',
            'withheld' => false,
            'value' => ['kind' => 'text', 'text' => 'SECRET-HEALTH-VALUE'],
            'valueLocale' => 'en',
            'sources' => [1],
        ]));
});

it('marks a health group as not medical advice', function () {
    $this->get("/occupations/{$this->occupation->slug}")->assertInertia(function (Assert $page) {
        $sections = collect($page->toArray()['props']['occupation']['sections']);

        expect($sections->firstWhere('key', 'health')['medical'])->toBeTrue()
            ->and($sections->firstWhere('key', 'workload')['medical'])->toBeFalse();
    });
});
