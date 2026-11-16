<?php

use App\Modules\Community\Enums\Role;
use App\Modules\Knowledge\Actions\ChangeStatus;
use App\Modules\Knowledge\Data\Actor;
use App\Modules\Knowledge\Enums\FieldStatus;
use App\Modules\Knowledge\Models\FieldDefinition;
use App\Modules\Knowledge\Models\Revision;
use App\Modules\Publishing\Actions\EvaluateIndexGate;
use App\Modules\Publishing\IndexGate;
use App\Modules\Publishing\Jobs\RecomputeIndexableJob;
use App\Modules\Taxonomy\Enums\PublishState;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;

/*
 * CLAUDE.md, "Rules enforced in code": pages are noindex (meta and X-Robots-Tag, left out of the
 * sitemap) until their fields pass config('atlas.index_gate'), which is off in Phase 1 (A11a).
 * The page's indexable flag follows every status change while the gate is on (PLAN M14, D55).
 */

beforeEach(function () {
    wave1Setup();
    config([
        'atlas.index_gate.waves' => [2],
        'atlas.index_gate.min_share' => 0.8,
        'atlas.index_gate.min_status' => 'community_supported',
        'atlas.index_gate.required' => ['description' => 'verified'],
    ]);

    $this->occupation = listedOccupation();
    $this->maintainer = personActor(Role::Maintainer);
    $this->definitions = collect([
        FieldDefinition::factory()->text()->create(['key' => 'description', 'wave' => 2, 'group' => 'about']),
        ...FieldDefinition::factory()->count(4)->create(['wave' => 2, 'group' => 'about']),
    ]);
});

/**
 * An AI draft for every wave 2 field, keyed by field key; the description is in English.
 *
 * @return array<string, Revision>
 */
function draftAll(int $occupationId, Collection $definitions): array
{
    return $definitions->mapWithKeys(fn (FieldDefinition $definition): array => [
        $definition->key => recordValue(
            $definition,
            $definition->key === 'description' ? 'A placeholder description.' : 3,
            occupationId: $occupationId,
            locale: $definition->key === 'description' ? 'en' : null,
        ),
    ])->all();
}

function moveStatus(Revision $revision, FieldStatus $status, Actor $actor): void
{
    app(ChangeStatus::class)->handle($revision->field_value_id, $status, $actor);
}

it('decides by the share of checked fields and the reviewed description', function (array $statuses, bool $passes) {
    config(['atlas.index_gate.enabled' => true]);

    expect(app(IndexGate::class)->passes($statuses))->toBe($passes);
})->with([
    'all verified' => [['description' => [FieldStatus::Verified], 'a' => [FieldStatus::Verified], 'b' => [FieldStatus::Verified], 'c' => [FieldStatus::Verified], 'd' => [FieldStatus::Verified]], true],
    'four of five checked, description verified' => [['description' => [FieldStatus::Verified], 'a' => [FieldStatus::CommunitySupported], 'b' => [FieldStatus::CommunitySupported], 'c' => [FieldStatus::CommunitySupported], 'd' => [FieldStatus::AiDraft]], true],
    'three of five checked' => [['description' => [FieldStatus::Verified], 'a' => [FieldStatus::CommunitySupported], 'b' => [FieldStatus::CommunitySupported], 'c' => [FieldStatus::Sourced], 'd' => [FieldStatus::AiDraft]], false],
    'description only community supported' => [['description' => [FieldStatus::CommunitySupported], 'a' => [FieldStatus::Verified], 'b' => [FieldStatus::Verified], 'c' => [FieldStatus::Verified], 'd' => [FieldStatus::Verified]], false],
    'disputed counts as not checked' => [['description' => [FieldStatus::Verified], 'a' => [FieldStatus::Verified], 'b' => [FieldStatus::Disputed], 'c' => [FieldStatus::Disputed], 'd' => [FieldStatus::Verified]], false],
    'a field without a value counts as not checked' => [['description' => [FieldStatus::Verified], 'a' => [], 'b' => [], 'c' => [FieldStatus::Verified], 'd' => [FieldStatus::Verified]], false],
    'one language of the description not verified' => [['description' => [FieldStatus::Verified, FieldStatus::AiDraft], 'a' => [FieldStatus::Verified], 'b' => [FieldStatus::Verified], 'c' => [FieldStatus::Verified], 'd' => [FieldStatus::Verified]], false],
    'no fields' => [[], false],
]);

it('never passes while the gate is off', function () {
    config(['atlas.index_gate.enabled' => false]);

    expect(app(IndexGate::class)->passes(['description' => [FieldStatus::Verified]]))->toBeFalse();
});

it('makes a page indexable when its fields are checked, and not when one is disputed', function () {
    config(['atlas.index_gate.enabled' => true]);
    $revisions = draftAll($this->occupation->id, $this->definitions);

    moveStatus($revisions['description'], FieldStatus::Verified, $this->maintainer);
    $others = array_values(array_diff_key($revisions, ['description' => true]));

    foreach (array_slice($others, 0, 2) as $revision) {
        moveStatus($revision, FieldStatus::CommunitySupported, $this->maintainer);
    }

    expect($this->occupation->fresh()->indexable)->toBeFalse();

    moveStatus($others[2], FieldStatus::CommunitySupported, $this->maintainer);

    expect($this->occupation->fresh()->indexable)->toBeTrue();
    $this->get("/en/occupations/{$this->occupation->slug}")
        ->assertOk()
        ->assertHeaderMissing('X-Robots-Tag')
        ->assertInertia(fn (Assert $page) => $page->where('robots', null));

    moveStatus($others[0], FieldStatus::Disputed, $this->maintainer);

    expect($this->occupation->fresh()->indexable)->toBeFalse();
    $this->get("/en/occupations/{$this->occupation->slug}")->assertHeader('X-Robots-Tag', 'noindex');
});

it('leaves pages noindex and queues nothing while the gate is off', function () {
    Queue::fake();
    $revisions = draftAll($this->occupation->id, $this->definitions);

    foreach ($revisions as $revision) {
        moveStatus($revision, FieldStatus::Verified, $this->maintainer);
    }

    Queue::assertNotPushed(RecomputeIndexableJob::class);
    expect($this->occupation->fresh()->indexable)->toBeFalse();
});

it('keeps a page noindex while the gate is off, even if the occupation is marked indexable', function () {
    $this->occupation->update(['indexable' => true]);

    $this->get("/en/occupations/{$this->occupation->slug}")
        ->assertHeader('X-Robots-Tag', 'noindex')
        ->assertInertia(fn (Assert $page) => $page->where('robots', 'noindex'));
});

it('never marks a candidate occupation indexable', function () {
    config(['atlas.index_gate.enabled' => true]);
    $this->occupation->update(['publish_state' => PublishState::Candidate]);
    Queue::fake();

    foreach (draftAll($this->occupation->id, $this->definitions) as $revision) {
        moveStatus($revision, FieldStatus::Verified, $this->maintainer);
    }

    (new RecomputeIndexableJob($this->occupation->id))->handle(app(EvaluateIndexGate::class));

    expect($this->occupation->fresh()->indexable)->toBeFalse();
});

it('recomputes every listed occupation on demand', function () {
    $revisions = draftAll($this->occupation->id, $this->definitions);

    foreach ($revisions as $revision) {
        moveStatus($revision, FieldStatus::Verified, $this->maintainer);
    }

    config(['atlas.index_gate.enabled' => true]);

    $this->artisan('atlas:index-gate:recompute')->expectsOutputToContain('Queued 1 occupations.')->assertSuccessful();

    expect($this->occupation->fresh()->indexable)->toBeTrue();
});
