<?php

use App\Models\User;
use App\Modules\Community\Actions\BlockUser;
use App\Modules\Community\Enums\Role;
use App\Modules\Community\Enums\TaskAnswer;
use App\Modules\Community\Exceptions\ModerationNotAllowed;
use App\Modules\Community\Filament\Resources\Users\Pages\ListUsers;
use App\Modules\Community\Models\VerificationAnswer;
use App\Modules\Knowledge\Models\Revision;
use Illuminate\Support\Facades\Gate;
use Inertia\Testing\AssertableInertia as Assert;
use Livewire\Livewire;

/*
 * PLAN M18 (D69): a maintainer can block a person. A blocked person keeps reading but cannot suggest,
 * answer tasks, send signed-in error reports, moderate or open the admin panel. Unblocking restores it.
 */

beforeEach(function () {
    wave1Setup();
    $this->occupation = listedOccupation();
    $this->maintainer = User::factory()->role(Role::Maintainer)->create();
    $this->person = User::factory()->role(Role::Trusted)->create(['created_at' => now()->subDays(30)]);
});

it('stops a blocked person from writing, and lets them write again once unblocked', function () {
    app(BlockUser::class)->handle($this->person->id, $this->maintainer, 'Spam links on many pages.');
    $this->person->refresh();

    expect($this->person->isBlocked())->toBeTrue()
        ->and($this->person->blocked_by)->toBe($this->maintainer->id)
        ->and($this->person->block_reason)->toBe('Spam links on many pages.');

    $suggest = "/en/occupations/{$this->occupation->slug}/suggest/physical_workload";
    $this->actingAs($this->person)->post($suggest, ['value' => 4, 'interest' => 'none'])
        ->assertSessionHasErrors(['value' => __('contribute.errors.blocked', [], 'en')]);
    $this->actingAs($this->person)->post("/en/occupations/{$this->occupation->slug}/report", ['message' => 'Wrong group.'])
        ->assertSessionHasErrors('message');

    $task = taskFor(occupation: listedOccupation());
    $this->actingAs($this->person)->post("/en/tasks/{$task->id}", ['answer' => 'agree'])
        ->assertSessionHas('notice', 'tasks.blocked');

    expect(Revision::query()->where('user_id', $this->person->id)->count())->toBe(0)
        ->and(VerificationAnswer::query()->where('user_id', $this->person->id)->count())->toBe(0);

    app(BlockUser::class)->unblock($this->person->id, $this->maintainer);

    $this->actingAs($this->person->refresh())->post($suggest, ['value' => 4, 'interest' => 'none'])->assertSessionHasNoErrors();
    answerTask($task, TaskAnswer::Agree, $this->person);

    expect($this->person->refresh()->isBlocked())->toBeFalse()
        ->and(Revision::query()->where('user_id', $this->person->id)->count())->toBe(1);
});

it('lets a blocked person keep reading', function () {
    app(BlockUser::class)->handle($this->person->id, $this->maintainer);

    $this->actingAs($this->person->refresh())->get("/en/occupations/{$this->occupation->slug}")->assertOk();
});

it('lets only a maintainer block, and never themself', function () {
    $reviewer = User::factory()->role(Role::Reviewer)->create();

    expect(fn () => app(BlockUser::class)->handle($this->person->id, $reviewer))->toThrow(ModerationNotAllowed::class)
        ->and(fn () => app(BlockUser::class)->handle($this->maintainer->id, $this->maintainer))->toThrow(ModerationNotAllowed::class)
        ->and($this->person->refresh()->isBlocked())->toBeFalse();
});

it('closes the admin panel to a blocked maintainer', function () {
    $other = User::factory()->role(Role::Maintainer)->create();
    app(BlockUser::class)->handle($other->id, $this->maintainer);

    $this->actingAs($other->refresh())->get('/admin')->assertForbidden();
    $this->actingAs($this->maintainer)->get('/admin')->assertSuccessful();
});

it('blocks and unblocks from the admin list', function () {
    $this->actingAs($this->maintainer);

    Livewire::test(ListUsers::class)
        ->callTableAction('block', $this->person, ['reason' => 'Promotional edits.'])
        ->assertHasNoTableActionErrors();

    expect($this->person->refresh()->block_reason)->toBe('Promotional edits.');

    Livewire::test(ListUsers::class)->callTableAction('unblock', $this->person);

    expect($this->person->refresh()->isBlocked())->toBeFalse();
});

it('keeps a blocked maintainer out of Horizon as well as the admin panel', function () {
    $other = User::factory()->role(Role::Maintainer)->create();
    expect(Gate::forUser($other)->allows('viewHorizon'))->toBeTrue();

    app(BlockUser::class)->handle($other->id, $this->maintainer, 'Account taken over.');

    expect(Gate::forUser($other->refresh())->allows('viewHorizon'))->toBeFalse();
});

it('does not send the reason for a block, the role or the counters to the browser', function () {
    app(BlockUser::class)->handle($this->person->id, $this->maintainer, 'Spam links on many pages.');

    $this->actingAs($this->person->refresh())->get('/dashboard')->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.user.name', $this->person->name)
            ->where('auth.user.email', $this->person->email)
            ->missing('auth.user.block_reason')
            ->missing('auth.user.blocked_by')
            ->missing('auth.user.role')
            ->missing('auth.user.accepted_proposals_count'))
        ->assertDontSee('Spam links on many pages.');
});

it('answers an unknown edit on the patrol page with a notice, not an error page', function () {
    $reviewer = User::factory()->role(Role::Reviewer)->create();

    $this->actingAs($reviewer)->from('/en/patrol')->post('/en/patrol/999999/patrolled')
        ->assertRedirect('/en/patrol')
        ->assertSessionHas('notice', 'moderation.notices.refused');
    $this->actingAs($reviewer)->post('/en/patrol/99999999999999999999/patrolled')->assertNotFound();
});
