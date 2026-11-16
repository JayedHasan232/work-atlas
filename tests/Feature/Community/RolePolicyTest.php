<?php

use App\Models\User;
use App\Modules\Community\Enums\Role;
use App\Modules\Knowledge\Actions\ChangeStatus;
use App\Modules\Knowledge\Actions\RevertRevision;
use App\Modules\Knowledge\Actions\ReviewRevision;
use App\Modules\Knowledge\Data\Actor;
use App\Modules\Knowledge\Enums\FieldStatus;
use App\Modules\Knowledge\Enums\ReviewDecision;
use App\Modules\Knowledge\Exceptions\RevertNotAllowed;
use App\Modules\Knowledge\Exceptions\ReviewNotAllowed;
use App\Modules\Knowledge\Exceptions\StatusTransitionNotAllowed;
use App\Modules\Knowledge\Models\FieldDefinition;
use App\Modules\Knowledge\Models\FieldValue;
use App\Modules\Knowledge\Models\Source;

/*
 * The role × action matrix (PLAN M8, D36), checked through the Knowledge actions themselves.
 */

/**
 * A value written by an AI run, so the slot has a current value to act on.
 *
 * @return array{0: FieldDefinition, 1: int}
 */
function slotWithAiValue(bool $sensitive = false): array
{
    $field = $sensitive ? FieldDefinition::factory()->sensitive()->create() : FieldDefinition::factory()->create();
    $sources = $sensitive ? [Source::factory()->create()->id] : [];

    return [$field, recordValue($field, 2, sourceIds: $sources)->field_value_id];
}

describe('writes', function () {
    it('applies a write directly only for trusted people and above, on non-sensitive fields', function (Role $role, bool $sensitive, bool $applied) {
        [$field, $slotId] = slotWithAiValue($sensitive);
        $slot = FieldValue::query()->findOrFail($slotId);
        $sources = $sensitive ? [Source::factory()->create()->id] : [];

        $revision = recordValue($field, 4, personActor($role), $sources, $slot->occupation_id);

        expect($revision->proposed)->toBe(! $applied)
            ->and($slot->fresh()->current_revision_id === $revision->id)->toBe($applied);
    })->with([
        'contributor, descriptive' => [Role::Contributor, false, false],
        'trusted, descriptive' => [Role::Trusted, false, true],
        'reviewer, descriptive' => [Role::Reviewer, false, true],
        'expert, descriptive' => [Role::Expert, false, true],
        'maintainer, descriptive' => [Role::Maintainer, false, true],
        'contributor, sensitive' => [Role::Contributor, true, false],
        'trusted, sensitive' => [Role::Trusted, true, false],
        'reviewer, sensitive' => [Role::Reviewer, true, false],
        'expert, sensitive' => [Role::Expert, true, false],
        'maintainer, sensitive' => [Role::Maintainer, true, false],
    ]);

    it('sets a direct write by a person without a source to community_supported, with one to sourced', function (bool $withSource, FieldStatus $status) {
        [$field, $slotId] = slotWithAiValue();
        $slot = FieldValue::query()->findOrFail($slotId);

        recordValue($field, 4, personActor(Role::Trusted), $withSource ? [Source::factory()->create()->id] : [], $slot->occupation_id);

        expect($slot->fresh()->status)->toBe($status);
    })->with([
        'no source' => [false, FieldStatus::CommunitySupported],
        'with a source' => [true, FieldStatus::Sourced],
    ]);
});

describe('status changes', function () {
    it('follows the role matrix', function (Role $role, FieldStatus $target, bool $sensitive, bool $allowed) {
        [, $slotId] = slotWithAiValue($sensitive);
        $change = fn () => app(ChangeStatus::class)->handle($slotId, $target, personActor($role));

        $allowed
            ? expect($change()->status)->toBe($target)
            : expect($change)->toThrow(StatusTransitionNotAllowed::class, "a {$role->value} cannot set");
    })->with([
        'contributor disputes' => [Role::Contributor, FieldStatus::Disputed, false, true],
        'contributor disputes a sensitive value' => [Role::Contributor, FieldStatus::Disputed, true, true],
        'contributor supports' => [Role::Contributor, FieldStatus::CommunitySupported, false, false],
        'contributor verifies' => [Role::Contributor, FieldStatus::Verified, false, false],
        'trusted supports' => [Role::Trusted, FieldStatus::CommunitySupported, false, true],
        'trusted supports a sensitive value' => [Role::Trusted, FieldStatus::CommunitySupported, true, false],
        'trusted verifies' => [Role::Trusted, FieldStatus::Verified, false, false],
        'reviewer supports a sensitive value' => [Role::Reviewer, FieldStatus::CommunitySupported, true, true],
        'reviewer verifies' => [Role::Reviewer, FieldStatus::Verified, false, true],
        'reviewer verifies a sensitive value' => [Role::Reviewer, FieldStatus::Verified, true, true],
        'expert verifies a sensitive value' => [Role::Expert, FieldStatus::Verified, true, true],
        'maintainer verifies' => [Role::Maintainer, FieldStatus::Verified, false, true],
    ]);
});

describe('reviews', function () {
    it('lets only reviewers and above review a proposal', function (Role $role, bool $allowed) {
        [$field, $slotId] = slotWithAiValue();
        $slot = FieldValue::query()->findOrFail($slotId);
        $proposal = recordValue($field, 4, personActor(), occupationId: $slot->occupation_id);
        $review = fn () => app(ReviewRevision::class)->handle($proposal->id, personActor($role), ReviewDecision::Accepted);

        $allowed
            ? expect($review()->decision)->toBe(ReviewDecision::Accepted)
            : expect($review)->toThrow(ReviewNotAllowed::class, "a {$role->value} cannot review");
    })->with([
        'contributor' => [Role::Contributor, false],
        'trusted' => [Role::Trusted, false],
        'reviewer' => [Role::Reviewer, true],
        'expert' => [Role::Expert, true],
        'maintainer' => [Role::Maintainer, true],
    ]);

    it('refuses a review of your own proposal, except by a maintainer (A13)', function (Role $role, bool $allowed) {
        [$field, $slotId] = slotWithAiValue(sensitive: true);
        $slot = FieldValue::query()->findOrFail($slotId);
        $author = personActor($role);
        $proposal = recordValue($field, 4, $author, [Source::factory()->create()->id], $slot->occupation_id);
        $review = fn () => app(ReviewRevision::class)->handle($proposal->id, $author, ReviewDecision::Accepted);

        $allowed
            ? expect($review()->decision)->toBe(ReviewDecision::Accepted)
            : expect($review)->toThrow(ReviewNotAllowed::class, 'it is your own proposal');
    })->with([
        'reviewer' => [Role::Reviewer, false],
        'expert' => [Role::Expert, false],
        'maintainer' => [Role::Maintainer, true],
    ]);

    it('counts reviewed proposals on the author', function () {
        [$field, $slotId] = slotWithAiValue();
        $slot = FieldValue::query()->findOrFail($slotId);
        $author = User::factory()->create();
        $first = recordValue($field, 4, Actor::user($author), occupationId: $slot->occupation_id);
        $second = recordValue($field, 5, Actor::user($author), occupationId: $slot->occupation_id);

        app(ReviewRevision::class)->handle($first->id, reviewerActor(), ReviewDecision::Rejected);
        app(ReviewRevision::class)->handle($second->id, reviewerActor(), ReviewDecision::Accepted);

        $author->refresh();
        expect($author->accepted_proposals_count)->toBe(1)
            ->and($author->rejected_proposals_count)->toBe(1);
    });
});

describe('reverts', function () {
    it('needs a trusted person for a descriptive value and a reviewer for a verified or sensitive one', function (Role $role, string $value, bool $allowed) {
        [, $slotId] = slotWithAiValue($value === 'sensitive');
        $slot = FieldValue::query()->findOrFail($slotId);

        if ($value === 'verified') {
            app(ChangeStatus::class)->handle($slotId, FieldStatus::Verified, reviewerActor());
        }

        $revert = fn () => app(RevertRevision::class)->handle((int) $slot->current_revision_id, personActor($role));

        $allowed
            ? expect($revert()->reverts_revision_id)->toBe($slot->current_revision_id)
            : expect($revert)->toThrow(RevertNotAllowed::class, "a {$role->value} cannot revert");
    })->with([
        'contributor, descriptive' => [Role::Contributor, 'descriptive', false],
        'trusted, descriptive' => [Role::Trusted, 'descriptive', true],
        'trusted, verified' => [Role::Trusted, 'verified', false],
        'trusted, sensitive' => [Role::Trusted, 'sensitive', false],
        'reviewer, verified' => [Role::Reviewer, 'verified', true],
        'reviewer, sensitive' => [Role::Reviewer, 'sensitive', true],
    ]);
});

it('lets only maintainers use the admin panel and Horizon', function (Role $role, bool $allowed) {
    expect($role->canUseAdmin())->toBe($allowed);
})->with([
    'contributor' => [Role::Contributor, false],
    'trusted' => [Role::Trusted, false],
    'reviewer' => [Role::Reviewer, false],
    'expert' => [Role::Expert, false],
    'maintainer' => [Role::Maintainer, true],
]);
