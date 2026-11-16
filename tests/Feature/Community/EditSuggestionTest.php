<?php

use App\Models\User;
use App\Modules\Community\Enums\Role;
use App\Modules\Knowledge\Actions\RecordRevision;
use App\Modules\Knowledge\Actions\RecordSource;
use App\Modules\Knowledge\Actions\ReviewRevision;
use App\Modules\Knowledge\Data\RevisionInput;
use App\Modules\Knowledge\Enums\FieldStatus;
use App\Modules\Knowledge\Enums\ReviewDecision;
use App\Modules\Knowledge\Exceptions\InvalidFieldValue;
use App\Modules\Knowledge\Models\FieldDefinition;
use App\Modules\Knowledge\Models\FieldValue;
use App\Modules\Knowledge\Models\Revision;
use App\Modules\Knowledge\Models\Source;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Testing\AssertableInertia as Assert;

/*
 * PLAN M16: a signed-in person suggests a change to one field. It goes through RecordRevision like
 * every other write: a contributor's change is a proposal, a trusted person's applies at once on a
 * non-sensitive field. Sources, a comment and a conflict-of-interest declaration travel with it.
 */

beforeEach(function () {
    wave1Setup();
    $this->occupation = listedOccupation();
    $this->url = "/en/occupations/{$this->occupation->slug}/suggest/physical_workload";
    $this->old = fn (Role $role = Role::Contributor): User => User::factory()->role($role)->create(['created_at' => now()->subDays(30)]);
});

/**
 * Values other than the names the occupation was listed with.
 *
 * @return Builder<FieldValue>
 */
function suggestedValues(): Builder
{
    return FieldValue::query()->whereNot('field_definition_id', FieldDefinition::query()->where('key', 'names')->value('id'));
}

/**
 * Revisions written by people (the listed names come from an AI run).
 *
 * @return Builder<Revision>
 */
function suggestedRevisions(): Builder
{
    return Revision::query()->whereNotNull('user_id');
}

function suggestion(array $input = []): array
{
    return array_replace(['value' => 4, 'sources' => [], 'comment' => null, 'interest' => 'none'], $input);
}

it('sends a guest to the login page', function () {
    $this->get($this->url)->assertRedirect('/login');
    $this->post($this->url, suggestion())->assertRedirect('/login');

    expect(suggestedRevisions()->count())->toBe(0);
});

it('shows the form for the field, with its anchors and the current value', function () {
    $field = FieldDefinition::query()->where('key', 'physical_workload')->sole();
    recordValue($field, 3, occupationId: $this->occupation->id);

    $this->actingAs(($this->old)())
        ->get($this->url)
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex')
        ->assertInertia(fn (Assert $page) => $page
            ->component('occupations/suggest')
            ->where('form.field.key', 'physical_workload')
            ->where('form.field.type', 'scale_1_5')
            ->has('form.field.anchors', 5)
            ->where('form.field.requiresSource', false)
            ->where('form.current.status', 'ai_draft')
            ->where('form.current.withheld', false)
            ->where('form.submitUrl', $this->url)
            ->where('form.pageUrl', "/en/occupations/{$this->occupation->slug}"));
});

it('records a contributor\'s change as a proposal, with sources, comment and declared interest', function () {
    $user = ($this->old)();

    $this->actingAs($user)
        ->post($this->url, suggestion([
            'sources' => [['url' => 'https://example.test/report', 'accessed_on' => '2026-11-01', 'title' => 'A report']],
            'comment' => 'I have done this work for ten years.',
            'interest' => 'declared',
            'interest_note' => 'I run a plumbing training centre.',
        ]))
        ->assertRedirect("/en/occupations/{$this->occupation->slug}")
        ->assertSessionHas('notice', 'contribute.suggest.proposed');

    $revision = suggestedRevisions()->sole();

    expect($revision)
        ->proposed->toBeTrue()
        ->user_id->toBe($user->id)
        ->comment->toBe('I have done this work for ten years.')
        ->declared_interest->toBe('I run a plumbing training centre.')
        ->and($revision->sources()->pluck('url')->all())->toBe(['https://example.test/report'])
        ->and(suggestedValues()->whereNotNull('current_revision_id')->count())->toBe(0);
});

it('applies a trusted person\'s change at once', function () {
    $this->actingAs(($this->old)(Role::Trusted))
        ->post($this->url, suggestion(['value' => 5]))
        ->assertSessionHas('notice', 'contribute.suggest.applied');

    $revision = suggestedRevisions()->sole();

    expect($revision)
        ->proposed->toBeFalse()
        ->new_value->toBe(5)
        ->and(suggestedValues()->sole()->current_revision_id)->toBe($revision->id);
});

it('needs a source on a requires_source field, and writes nothing without one', function () {
    $field = FieldDefinition::factory()->create(['key' => 'licence_needed', 'requires_source' => true]);

    $this->actingAs(($this->old)())
        ->post("/en/occupations/{$this->occupation->slug}/suggest/{$field->key}", suggestion(['value' => 2]))
        ->assertSessionHasErrors(['sources' => __('contribute.errors.source_required', [], 'en')]);

    expect(suggestedRevisions()->count())->toBe(0)
        ->and(Source::query()->count())->toBe(0);
});

it('refuses a value that does not fit the field', function (mixed $value) {
    $this->actingAs(($this->old)())
        ->post($this->url, suggestion(['value' => $value]))
        ->assertSessionHasErrors('value');

    expect(suggestedRevisions()->count())->toBe(0);
})->with([6, 0, 'heavy']);

it('needs a note when a conflict of interest is declared, and a valid source', function (array $input, string $error) {
    $this->actingAs(($this->old)())
        ->post($this->url, suggestion($input))
        ->assertSessionHasErrors($error);

    expect(suggestedRevisions()->count())->toBe(0);
})->with([
    'no note' => [['interest' => 'declared', 'interest_note' => ''], 'interest_note'],
    'bad url' => [['sources' => [['url' => 'not a url', 'accessed_on' => '2026-11-01']]], 'sources.0.url'],
    'future date' => [['sources' => [['url' => 'https://example.test', 'accessed_on' => '2099-01-01']]], 'sources.0.accessed_on'],
]);

it('has no suggestion form for the name list or an unknown field', function (string $field) {
    $this->actingAs(($this->old)())->get("/en/occupations/{$this->occupation->slug}/suggest/{$field}")->assertNotFound();
    $this->actingAs(($this->old)())->post("/en/occupations/{$this->occupation->slug}/suggest/{$field}", suggestion())->assertNotFound();
})->with(['names', 'no_such_field']);

it('does not show a sensitive value in the form until it is verified', function () {
    config(['atlas.publishing.page_waves' => [0, 1, 3]]);
    $field = FieldDefinition::factory()->sensitive()->create(['key' => 'income_range']);
    $source = app(RecordSource::class)->handle(fixtureSource());
    recordValue($field, 4, sourceIds: [$source], occupationId: $this->occupation->id);

    $this->actingAs(($this->old)())
        ->get("/en/occupations/{$this->occupation->slug}/suggest/income_range")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('form.field.sensitive', true)
            ->where('form.current.withheld', true)
            ->where('form.current.value', null));
});

it('does not let an AI run declare an interest or ask for a review', function (array $extra) {
    $field = FieldDefinition::query()->where('key', 'physical_workload')->sole();

    expect(fn () => app(RecordRevision::class)->handle(new RevisionInput(...[
        'occupationId' => $this->occupation->id,
        'fieldDefinitionId' => $field->id,
        'value' => 3,
        'actor' => aiActor(),
        'confidence' => 0.5,
        ...$extra,
    ])))->toThrow(InvalidFieldValue::class);
})->with([
    'interest' => [['declaredInterest' => 'none']],
    'review' => [['needsReview' => true]],
]);

it('keeps a status the page can show after a proposal is accepted', function () {
    $this->actingAs(($this->old)())->post($this->url, suggestion());
    $revision = suggestedRevisions()->sole();

    app(ReviewRevision::class)->handle($revision->id, reviewerActor(), ReviewDecision::Accepted);

    expect(suggestedValues()->sole())
        ->current_revision_id->toBe($revision->id)
        ->status->toBe(FieldStatus::Verified);
});
