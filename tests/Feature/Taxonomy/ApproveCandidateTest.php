<?php

use App\Models\User;
use App\Modules\Community\Enums\Role;
use App\Modules\Knowledge\Models\FieldDefinition;
use App\Modules\Taxonomy\Actions\ApproveCandidate;
use App\Modules\Taxonomy\Actions\DetectDuplicates;
use App\Modules\Taxonomy\Actions\RejectCandidate;
use App\Modules\Taxonomy\Actions\UpdateOccupationNames;
use App\Modules\Taxonomy\Enums\PublishState;
use App\Modules\Taxonomy\Exceptions\ListReviewNotAllowed;
use App\Modules\Taxonomy\Models\DuplicateCandidate;
use App\Modules\Taxonomy\Models\IscoGroup;

/*
 * PLAN M11: the list review. A reviewer approves a candidate (listed), rejects it with a reason, or
 * corrects its names through a revision.
 */

beforeEach(function () {
    FieldDefinition::factory()->names()->create();
    IscoGroup::factory()->create(['code' => '7126']);
    $this->reviewer = User::factory()->role(Role::Reviewer)->create();
    $this->candidate = occupationNamed([
        'bn' => [['name' => 'প্লাম্বার', 'type' => 'primary']],
        'en' => [['name' => 'Plumber', 'type' => 'primary']],
    ]);
});

it('lists an approved candidate and records who approved it', function () {
    app(ApproveCandidate::class)->handle($this->candidate->id, $this->reviewer, 'Clear, common work.');

    $occupation = $this->candidate->fresh();
    expect($occupation->publish_state)->toBe(PublishState::Listed)
        ->and($occupation->reviewed_by_id)->toBe($this->reviewer->id)
        ->and($occupation->reviewed_at)->not->toBeNull()
        ->and($occupation->review_note)->toBe('Clear, common work.');
});

it('needs a primary name in Bangla and English before listing', function () {
    $onlyEnglish = occupationNamed(['en' => [['name' => 'Pipe fitter', 'type' => 'primary']]]);

    expect(fn () => app(ApproveCandidate::class)->handle($onlyEnglish->id, $this->reviewer))
        ->toThrow(ListReviewNotAllowed::class, 'primary name in bn')
        ->and($onlyEnglish->fresh()->publish_state)->toBe(PublishState::Candidate);
});

it('lets only reviewers and above review the list', function (Role $role) {
    $person = User::factory()->role($role)->create();

    expect(fn () => app(ApproveCandidate::class)->handle($this->candidate->id, $person))->toThrow(ListReviewNotAllowed::class)
        ->and(fn () => app(RejectCandidate::class)->handle($this->candidate->id, $person, 'No.'))->toThrow(ListReviewNotAllowed::class)
        ->and($this->candidate->fresh()->publish_state)->toBe(PublishState::Candidate);
})->with([Role::Contributor, Role::Trusted]);

it('rejects a candidate with a reason, drops its open pairs, and can approve it later', function () {
    occupationNamed(['en' => [['name' => 'Plumber', 'type' => 'primary']]]);
    app(DetectDuplicates::class)->handle();

    expect(fn () => app(RejectCandidate::class)->handle($this->candidate->id, $this->reviewer, '  '))->toThrow(ListReviewNotAllowed::class, 'needs a reason');

    app(RejectCandidate::class)->handle($this->candidate->id, $this->reviewer, 'Not a separate occupation.');

    expect($this->candidate->fresh()->publish_state)->toBe(PublishState::Rejected)
        ->and($this->candidate->fresh()->review_note)->toBe('Not a separate occupation.')
        ->and(DuplicateCandidate::query()->count())->toBe(0);

    app(ApproveCandidate::class)->handle($this->candidate->id, $this->reviewer);
    expect($this->candidate->fresh()->publish_state)->toBe(PublishState::Listed);
});

it('refuses to approve or reject an occupation that is not a candidate', function () {
    app(ApproveCandidate::class)->handle($this->candidate->id, $this->reviewer);

    expect(fn () => app(ApproveCandidate::class)->handle($this->candidate->id, $this->reviewer))->toThrow(ListReviewNotAllowed::class)
        ->and(fn () => app(RejectCandidate::class)->handle($this->candidate->id, $this->reviewer, 'Late.'))->toThrow(ListReviewNotAllowed::class);
});

it('corrects names through a revision by the reviewer, and the search copy follows', function () {
    app(UpdateOccupationNames::class)->handle($this->candidate->id, 'bn', [
        ['name' => 'নলমিস্ত্রি', 'type' => 'primary'],
        ['name' => 'প্লাম্বার', 'type' => 'alias'],
    ], $this->reviewer, 'The local name is the common one.');

    expect(searchNamesOf($this->candidate->id))->toBe([
        ['bn', 'নলমিস্ত্রি', 'primary', null],
        ['bn', 'প্লাম্বার', 'alias', null],
        ['en', 'Plumber', 'primary', null],
    ]);
});
