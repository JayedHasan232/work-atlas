<?php

use App\Modules\Knowledge\Models\FieldDefinition;
use App\Modules\Taxonomy\Models\Occupation;
use Inertia\Testing\AssertableInertia as Assert;

/*
 * PLAN M20, rule 11: every match says why it fits, what is not known and the status of the data
 * behind it, and never promises anything.
 */

beforeEach(function () {
    wave1Setup();
    $this->plumber = listedOccupation();
    $this->tailor = listedOccupation([
        'bn' => [['name' => 'দর্জি', 'type' => 'primary']],
        'en' => [['name' => 'Tailor', 'type' => 'primary']],
    ]);
});

/**
 * @param  array<string, mixed>  $values
 */
function guidePageValues(Occupation $occupation, array $values): void
{
    foreach ($values as $key => $value) {
        recordValue(FieldDefinition::query()->where('key', $key)->firstOrFail(), $value, occupationId: $occupation->id);
    }
}

it('shows the questions and no results before any answer', function () {
    $this->get('/en/guide')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('discovery/guide')
        ->has('guide.questions', 9)
        ->where('guide.questions.0.key', 'hours')
        ->where('guide.results', null));
});

it('explains every match with reasons, statuses and limits', function () {
    guidePageValues($this->plumber, ['part_time_possible' => 'yes', 'work_setting' => ['outdoor']]);
    guidePageValues($this->tailor, ['part_time_possible' => 'sometimes', 'work_setting' => ['indoor', 'home_based'], 'formal_training' => 'required']);

    $this->get('/en/guide?hours=part_time&setting=home_based&when=day')->assertOk()->assertInertia(function (Assert $page) {
        $results = $page->toArray()['props']['guide']['results'];

        expect($results['total'])->toBe(1)
            ->and($results['conditions'])->toBe(3)
            ->and($results['limits'])->toHaveCount(3)
            ->and($results['matches'])->toHaveCount(1);

        $match = $results['matches'][0];

        expect($match['occupation']['name'])->toBe('Tailor')
            ->and(array_column($match['reasons'], 'question'))->toBe(['hours', 'setting'])
            ->and(array_column($match['reasons'], 'status'))->toBe(['ai_draft', 'ai_draft'])
            ->and(array_column($match['reasons'], 'outcome'))->toBe(['partly', 'met'])
            ->and($match['limits'])->toContain(
                'Part-time is possible only sometimes.',
                'Not known yet: Working hours',
                'Needs a licence or certificate.',
                'Some reasons rest on data that is not verified yet.',
            );
    });
});

it('says "matches your situation", never a promise', function () {
    $this->get('/en/guide?hours=part_time')->assertOk()
        ->assertSee('not a promise of work or income', false)
        ->assertDontSee('perfect', false);
});

it('works in Bangla', function () {
    $this->get('/guide?hours=part_time')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('guide.questions.0.title', __('guide.questions.hours.title', [], 'bn'))
        ->has('guide.results.matches', 2));
});
