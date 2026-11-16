<?php

use App\Models\User;
use App\Modules\Community\Enums\Role;
use App\Modules\Knowledge\Enums\FieldStatus;
use App\Modules\Knowledge\Exceptions\InvalidFieldValue;
use App\Modules\Knowledge\Models\FieldDefinition;
use App\Modules\Knowledge\Models\FieldValue;
use App\Modules\Taxonomy\Actions\DetectDuplicates;
use App\Modules\Taxonomy\Actions\MergeOccupations;
use App\Modules\Taxonomy\Enums\DuplicateStatus;
use App\Modules\Taxonomy\Enums\PublishState;
use App\Modules\Taxonomy\Exceptions\ListReviewNotAllowed;
use App\Modules\Taxonomy\Models\DuplicateCandidate;
use App\Modules\Taxonomy\Models\IscoGroup;
use App\Modules\Taxonomy\Models\OccupationName;

/*
 * PLAN M11, D48: a merge moves the names to the target as aliases through a revision, leaves no
 * orphan names, and keeps the merged occupation for its redirect.
 */

beforeEach(function () {
    FieldDefinition::factory()->names()->create();
    IscoGroup::factory()->create(['code' => '7126']);
    $this->reviewer = User::factory()->role(Role::Reviewer)->create();
    $this->into = occupationNamed([
        'bn' => [['name' => 'প্লাম্বার', 'type' => 'primary']],
        'en' => [['name' => 'Plumber', 'type' => 'primary']],
    ], ['publish_state' => PublishState::Listed]);
    $this->from = occupationNamed([
        'bn' => [['name' => 'নলমিস্ত্রি', 'type' => 'primary'], ['name' => 'পানির মিস্ত্রি', 'type' => 'regional', 'region' => 'sylhet']],
        'en' => [['name' => 'plumber', 'type' => 'primary'], ['name' => 'Pipe fitter', 'type' => 'alias']],
    ]);
});

it('adds the merged names to the target as aliases and keeps local and regional types', function () {
    app(MergeOccupations::class)->handle($this->from->id, $this->into->id, $this->reviewer, 'Same work.');

    expect(searchNamesOf($this->into->id))->toBe([
        ['bn', 'প্লাম্বার', 'primary', null],
        ['bn', 'নলমিস্ত্রি', 'alias', null],
        ['bn', 'পানির মিস্ত্রি', 'regional', 'sylhet'],
        ['en', 'Plumber', 'primary', null],
        ['en', 'Pipe fitter', 'alias', null],
    ]);

    $value = FieldValue::query()->where('occupation_id', $this->into->id)->where('locale', 'en')->sole();
    expect($value->status)->toBe(FieldStatus::CommunitySupported)
        ->and($value->currentRevision->user_id)->toBe($this->reviewer->id)
        ->and($value->currentRevision->comment)->toContain($this->from->slug);
});

it('marks the merged occupation, leaves no orphan names and closes its pairs', function () {
    $other = occupationNamed(['en' => [['name' => 'Pipe fitters', 'type' => 'primary']]]);
    app(DetectDuplicates::class)->handle();
    expect(DuplicateCandidate::query()->count())->toBe(2);

    app(MergeOccupations::class)->handle($this->from->id, $this->into->id, $this->reviewer);

    $from = $this->from->fresh();
    expect($from->publish_state)->toBe(PublishState::Merged)
        ->and($from->merged_into_id)->toBe($this->into->id)
        ->and($from->indexable)->toBeFalse()
        ->and($from->reviewed_by_id)->toBe($this->reviewer->id)
        ->and(OccupationName::query()->where('occupation_id', $this->from->id)->count())->toBe(0)
        ->and(DuplicateCandidate::query()->where('status', DuplicateStatus::Merged)->count())->toBe(1)
        ->and(DuplicateCandidate::query()->where('status', DuplicateStatus::Open)->count())->toBe(0)
        ->and($other->fresh()->publish_state)->toBe(PublishState::Candidate);
});

it('points earlier merges at the new target, so a redirect is one step', function () {
    $older = occupationNamed(['en' => [['name' => 'Water fitter', 'type' => 'primary']]]);
    app(MergeOccupations::class)->handle($older->id, $this->from->id, $this->reviewer);

    app(MergeOccupations::class)->handle($this->from->id, $this->into->id, $this->reviewer);

    expect($older->fresh()->merged_into_id)->toBe($this->into->id)
        ->and(OccupationName::query()->where('occupation_id', $this->into->id)->where('name', 'Water fitter')->exists())->toBeTrue();
});

it('keeps the target names when they are all there already', function () {
    $same = occupationNamed(['en' => [['name' => 'PLUMBER', 'type' => 'primary']]]);
    $before = FieldValue::query()->where('occupation_id', $this->into->id)->where('locale', 'en')->sole()->current_revision_id;

    app(MergeOccupations::class)->handle($same->id, $this->into->id, $this->reviewer);

    expect(FieldValue::query()->where('occupation_id', $this->into->id)->where('locale', 'en')->sole()->current_revision_id)->toBe($before);
});

it('refuses a merge into itself, into a merged or rejected occupation, and below reviewer', function () {
    $merge = app(MergeOccupations::class);
    $rejected = occupationNamed(['en' => [['name' => 'Not an occupation', 'type' => 'primary']]], ['publish_state' => PublishState::Rejected]);

    expect(fn () => $merge->handle($this->from->id, $this->from->id, $this->reviewer))->toThrow(ListReviewNotAllowed::class, 'itself')
        ->and(fn () => $merge->handle($this->from->id, $rejected->id, $this->reviewer))->toThrow(ListReviewNotAllowed::class, 'rejected')
        ->and(fn () => $merge->handle($this->from->id, $this->into->id, User::factory()->role(Role::Trusted)->create()))->toThrow(ListReviewNotAllowed::class);

    $merge->handle($this->from->id, $this->into->id, $this->reviewer);

    expect(fn () => $merge->handle($rejected->id, $this->from->id, $this->reviewer))->toThrow(ListReviewNotAllowed::class)
        ->and($this->from->fresh()->merged_into_id)->toBe($this->into->id);
});

it('writes nothing when the moved names break a rule', function () {
    $many = occupationNamed(['en' => array_merge(
        [['name' => 'Fitter', 'type' => 'primary']],
        array_map(fn (int $i) => ['name' => "Fitter {$i}", 'type' => 'alias'], range(1, 49)),
    )]);

    expect(fn () => app(MergeOccupations::class)->handle($many->id, $this->into->id, $this->reviewer))->toThrow(InvalidFieldValue::class, '1 to 50 names')
        ->and($many->fresh()->publish_state)->toBe(PublishState::Candidate)
        ->and(searchNamesOf($this->into->id))->toHaveCount(2);
});
