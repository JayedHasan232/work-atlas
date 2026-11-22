<?php

use App\Models\User;
use App\Modules\Community\Enums\Role;
use App\Modules\Community\Enums\TaskAnswer;
use App\Modules\Knowledge\Actions\ReviewRevision;
use App\Modules\Knowledge\Data\Actor;
use App\Modules\Knowledge\Enums\ReviewDecision;
use App\Modules\Knowledge\Models\FieldDefinition;

/*
 * PLAN M17, A11c, D65: a contributor becomes trusted after 50 accepted contributions (accepted
 * proposals plus answers that agreed with a reached consensus) and 30 days. Only contributors move.
 */

beforeEach(function () {
    wave1Setup();
});

function contributor(int $proposals, int $answers, int $days = 30, Role $role = Role::Contributor): User
{
    return User::factory()->role($role)->create([
        'accepted_proposals_count' => $proposals,
        'confirmed_answers_count' => $answers,
        'created_at' => now()->subDays($days),
    ]);
}

it('promotes contributors daily once they qualify', function () {
    $ready = contributor(20, 30);
    $short = contributor(20, 29);
    $young = contributor(40, 10, days: 29);
    $reviewer = contributor(60, 0, role: Role::Reviewer);

    $this->artisan('atlas:trust:promote')->assertSuccessful();

    expect($ready->refresh()->role)->toBe(Role::Trusted)
        ->and($short->refresh()->role)->toBe(Role::Contributor)
        ->and($young->refresh()->role)->toBe(Role::Contributor)
        ->and($reviewer->refresh()->role)->toBe(Role::Reviewer);
});

it('promotes a contributor whose answer completes a consensus', function () {
    $user = contributor(0, 49);
    $task = taskFor();
    answerTask($task, TaskAnswer::Agree);
    answerTask($task, TaskAnswer::Agree);

    answerTask($task, TaskAnswer::Agree, $user);

    expect($user->refresh())
        ->confirmed_answers_count->toBe(50)
        ->role->toBe(Role::Trusted);
});

it('promotes a contributor whose proposal is accepted', function () {
    $user = contributor(49, 0);
    $revision = recordValue(FieldDefinition::query()->where('key', 'physical_workload')->sole(), 4, Actor::user($user), occupationId: listedOccupation()->id);

    app(ReviewRevision::class)->handle($revision->id, reviewerActor(), ReviewDecision::Accepted);

    expect($user->refresh())
        ->accepted_proposals_count->toBe(50)
        ->role->toBe(Role::Trusted);
});

it('uses the configured thresholds', function () {
    config(['atlas.community.trusted_after_contributions' => 5, 'atlas.community.trusted_after_days' => 7]);
    $user = contributor(5, 0, days: 7);

    $this->artisan('atlas:trust:promote')->assertSuccessful();

    expect($user->refresh()->role)->toBe(Role::Trusted);
});

it('does not promote a blocked contributor', function () {
    $blocked = contributor(40, 30);
    $blocked->forceFill(['blocked_at' => now(), 'block_reason' => 'Spam.'])->save();

    $this->artisan('atlas:trust:promote')->assertSuccessful();

    expect($blocked->refresh()->role)->toBe(Role::Contributor);
});
