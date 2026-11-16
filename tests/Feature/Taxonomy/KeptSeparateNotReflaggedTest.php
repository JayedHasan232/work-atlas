<?php

use App\Models\User;
use App\Modules\Community\Enums\Role;
use App\Modules\Knowledge\Models\FieldDefinition;
use App\Modules\Taxonomy\Actions\DetectDuplicates;
use App\Modules\Taxonomy\Actions\KeepSeparate;
use App\Modules\Taxonomy\Enums\DuplicateStatus;
use App\Modules\Taxonomy\Exceptions\ListReviewNotAllowed;
use App\Modules\Taxonomy\Models\DuplicateCandidate;
use App\Modules\Taxonomy\Models\IscoGroup;

/*
 * PLAN M11, D47: "keep separate" is remembered; the detector never flags the pair again.
 */

beforeEach(function () {
    FieldDefinition::factory()->names()->create();
    IscoGroup::factory()->create(['code' => '7126']);
    occupationNamed(['en' => [['name' => 'Tailor', 'type' => 'primary']]]);
    occupationNamed(['en' => [['name' => 'Tailors', 'type' => 'primary']]]);
    app(DetectDuplicates::class)->handle();
    $this->pair = DuplicateCandidate::query()->sole();
    $this->reviewer = User::factory()->role(Role::Reviewer)->create();
});

it('keeps a decided pair and does not flag it again', function () {
    app(KeepSeparate::class)->handle($this->pair->id, $this->reviewer, 'Ladies and gents tailoring are checked separately.');

    expect(app(DetectDuplicates::class)->handle())->toBe(0);

    $pair = $this->pair->fresh();
    expect($pair->status)->toBe(DuplicateStatus::KeptSeparate)
        ->and($pair->decided_by_id)->toBe($this->reviewer->id)
        ->and($pair->decided_at)->not->toBeNull()
        ->and(DuplicateCandidate::query()->count())->toBe(1);
});

it('refuses a person below reviewer and a pair already decided', function () {
    expect(fn () => app(KeepSeparate::class)->handle($this->pair->id, User::factory()->role(Role::Trusted)->create()))
        ->toThrow(ListReviewNotAllowed::class);

    app(KeepSeparate::class)->handle($this->pair->id, $this->reviewer);

    expect(fn () => app(KeepSeparate::class)->handle($this->pair->id, $this->reviewer))->toThrow(ListReviewNotAllowed::class, 'already decided');
});
