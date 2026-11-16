<?php

use App\Models\User;
use App\Modules\Community\Enums\Role;
use App\Modules\Knowledge\Actions\ChangeStatus;
use App\Modules\Knowledge\Enums\FieldStatus;
use App\Modules\Knowledge\Models\FieldDefinition;
use App\Modules\Knowledge\Models\Source;
use App\Modules\Taxonomy\Actions\RejectCandidate;
use Inertia\Testing\AssertableInertia as Assert;

/*
 * PLAN M13: the public occupation page, in Bangla at the root and in English under /en. Every field
 * shows its status; the page is noindex during the Phase 1 beta (rule 12).
 */

beforeEach(function () {
    wave1Setup();
    $this->occupation = listedOccupation();
    $this->field = fn (string $key): FieldDefinition => FieldDefinition::query()->where('key', $key)->sole();
});

/**
 * @return array<string, mixed>
 */
function pageField(Assert $page, string $key): array
{
    foreach ($page->toArray()['props']['occupation']['sections'] as $section) {
        foreach ($section['fields'] as $field) {
            if ($field['key'] === $key) {
                return $field;
            }
        }
    }

    throw new RuntimeException("No field {$key} on the page.");
}

it('renders a listed occupation in Bangla and in English', function () {
    $this->get("/occupations/{$this->occupation->slug}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('occupations/show')
            ->where('occupation.name', 'প্লাম্বার')
            ->where('occupation.nameLocale', 'bn')
            ->where('occupation.iscoCode', '7126')
            ->where('occupation.names.0.locale', 'bn')
            ->where('occupation.names.0.items.1', ['name' => 'নলমিস্ত্রি', 'type' => 'local', 'region' => null])
            ->where('occupation.names.1.locale', 'en')
            ->where('occupation.groups.3.code', '7126')
            ->where('localization.locale', 'bn'));

    $this->get("/en/occupations/{$this->occupation->slug}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('occupations/show')
            ->where('occupation.name', 'Plumber')
            ->where('occupation.names.0.locale', 'en')
            ->where('localization.locale', 'en'));
});

it('shows the status of every field, and no status for a field without a value', function () {
    recordValue(($this->field)('physical_workload'), 4, occupationId: $this->occupation->id);
    $seasonal = recordValue(($this->field)('seasonal'), 'year_round', occupationId: $this->occupation->id);
    app(ChangeStatus::class)->handle($seasonal->field_value_id, FieldStatus::Verified, reviewerActor());

    $this->get("/en/occupations/{$this->occupation->slug}")->assertInertia(function (Assert $page) {
        $workload = pageField($page, 'physical_workload');
        expect($workload['status'])->toBe('ai_draft')
            ->and($workload['value']['kind'])->toBe('scale')
            ->and($workload['value']['point'])->toBe(4)
            ->and($workload['value']['anchor'])->toBeString()->not->toBeEmpty();

        expect(pageField($page, 'seasonal'))->toMatchArray([
            'status' => 'verified',
            'value' => ['kind' => 'options', 'labels' => ['Year-round']],
        ]);

        expect(pageField($page, 'mental_workload'))->toMatchArray(['status' => null, 'value' => null, 'withheld' => false]);
        expect($page->toArray()['props']['occupation']['unverified'])->toBeTrue();
    });
});

it('shows "unknown" as not known, and option labels in the reader language', function () {
    recordValue(($this->field)('work_time'), ['day', 'night'], occupationId: $this->occupation->id);
    recordValue(($this->field)('formal_training'), 'unknown', occupationId: $this->occupation->id);

    $this->get("/occupations/{$this->occupation->slug}")->assertInertia(function (Assert $page) {
        expect(pageField($page, 'work_time')['value'])->toBe(['kind' => 'options', 'labels' => ['দিনে', 'রাতে']])
            ->and(pageField($page, 'formal_training')['value'])->toBe(['kind' => 'unknown']);
    });
});

it('numbers the cited sources once each', function () {
    $source = Source::factory()->create(['title' => 'Labour Force Survey']);
    recordValue(($this->field)('physical_workload'), 3, sourceIds: [$source->id], occupationId: $this->occupation->id);
    recordValue(($this->field)('mental_workload'), 2, sourceIds: [$source->id], occupationId: $this->occupation->id);

    $this->get("/occupations/{$this->occupation->slug}")->assertInertia(function (Assert $page) use ($source) {
        $sources = $page->toArray()['props']['occupation']['sources'];
        expect($sources)->toHaveCount(1)
            ->and($sources[0])->toMatchArray(['number' => 1, 'url' => $source->url, 'title' => 'Labour Force Survey'])
            ->and(pageField($page, 'physical_workload')['sources'])->toBe([1])
            ->and(pageField($page, 'physical_workload')['status'])->toBe('sourced')
            ->and(pageField($page, 'mental_workload')['sources'])->toBe([1]);
    });
});

it('is kept out of search engines', function () {
    $this->get("/occupations/{$this->occupation->slug}")
        ->assertHeader('X-Robots-Tag', 'noindex')
        ->assertInertia(fn (Assert $page) => $page->where('robots', 'noindex'));
});

it('has no page for a candidate, a rejected or an unknown occupation', function () {
    $candidate = occupationNamed(['en' => [['name' => 'Pipe fitter', 'type' => 'primary']]]);
    $rejected = occupationNamed(['en' => [['name' => 'Water carrier', 'type' => 'primary']]]);
    app(RejectCandidate::class)->handle($rejected->id, User::factory()->role(Role::Reviewer)->create(), 'Not an occupation');

    $this->get("/occupations/{$candidate->slug}")->assertNotFound();
    $this->get("/en/occupations/{$rejected->slug}")->assertNotFound();
    $this->get('/occupations/nobody')->assertNotFound();
});
