<?php

use App\Models\User;
use App\Modules\Community\Enums\Role;
use App\Modules\Community\Models\RevisionPatrol;
use App\Modules\Knowledge\Models\FieldDefinition;
use App\Modules\Knowledge\Models\FieldValue;
use App\Modules\Knowledge\Models\Revision;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;

/*
 * PLAN M18 (D66): any recent edit can be found, read as a diff and reverted in three clicks:
 * the patrol list, the edit, revert. The revert is a new revision (rules 4–5), and both edits count
 * as patrolled. Marking an edit patrolled takes it off the list.
 */

beforeEach(function () {
    wave1Setup();
    $this->occupation = listedOccupation();
    $this->field = FieldDefinition::query()->where('key', 'physical_workload')->sole();
    $this->original = recordValue($this->field, 2, occupationId: $this->occupation->id);
    $this->reviewer = User::factory()->role(Role::Reviewer)->create();
});

function patrolEditBy(User $user, string $slug, int $value = 5, array $input = []): Revision
{
    test()->actingAs($user)
        ->post("/en/occupations/{$slug}/suggest/physical_workload", array_replace(['value' => $value, 'interest' => 'none'], $input))
        ->assertSessionHasNoErrors();

    return Revision::query()->where('user_id', $user->id)->latest('id')->firstOrFail();
}

function patrolList(User $user): TestResponse
{
    return test()->actingAs($user)->get('/en/patrol');
}

it('finds, shows and reverts an edit in three clicks', function () {
    $trusted = User::factory()->role(Role::Trusted)->create(['created_at' => now()->subDays(30)]);
    $edit = patrolEditBy($trusted, $this->occupation->slug);
    $url = "/en/patrol/{$edit->id}";

    // 1. The patrol list.
    patrolList($this->reviewer)
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex')
        ->assertInertia(fn (Assert $page) => $page
            ->component('moderation/patrol-queue')
            ->has('entries', 1)
            ->where('entries.0.revisionId', $edit->id)
            ->where('entries.0.kind', 'applied')
            ->where('entries.0.author.name', $trusted->name)
            ->where('entries.0.url', $url));

    // 2. The edit, with its diff.
    $this->actingAs($this->reviewer)
        ->get($url)
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('moderation/patrol-edit')
            ->where('edit.old', fn ($old) => $old['kind'] === 'scale' && $old['point'] === 2)
            ->where('edit.value', fn ($value) => $value['kind'] === 'scale' && $value['point'] === 5)
            ->where('edit.diff', [['op' => '-', 'line' => '2'], ['op' => '+', 'line' => '5']])
            ->where('edit.current', true)
            ->where('edit.canRevert', true)
            ->where('edit.patrolled', false)
            ->where('edit.revertUrl', "{$url}/revert"));

    // 3. Revert.
    $this->actingAs($this->reviewer)
        ->post("{$url}/revert", ['comment' => 'Not right for this work.'])
        ->assertRedirect('/en/patrol')
        ->assertSessionHas('notice', 'moderation.notices.reverted');

    $slot = FieldValue::query()->findOrFail($edit->field_value_id);
    $revert = Revision::query()->findOrFail($slot->current_revision_id);

    expect($revert->new_value)->toBe(2)
        ->and($revert->reverts_revision_id)->toBe($edit->id)
        ->and($revert->user_id)->toBe($this->reviewer->id)
        ->and($revert->comment)->toBe('Not right for this work.')
        ->and(Revision::query()->whereKey($edit->id)->exists())->toBeTrue()
        ->and(RevisionPatrol::query()->whereIn('revision_id', [$edit->id, $revert->id])->count())->toBe(2);

    patrolList($this->reviewer)->assertInertia(fn (Assert $page) => $page->has('entries', 0));
});

it('takes an edit off the list when it is marked patrolled, once', function () {
    $edit = patrolEditBy(User::factory()->role(Role::Trusted)->create(['created_at' => now()->subDays(30)]), $this->occupation->slug);

    $this->actingAs($this->reviewer)
        ->post("/en/patrol/{$edit->id}/patrolled")
        ->assertRedirect('/en/patrol')
        ->assertSessionHas('notice', 'moderation.notices.patrolled');
    $this->actingAs(User::factory()->role(Role::Reviewer)->create())
        ->post("/en/patrol/{$edit->id}/patrolled")
        ->assertSessionHas('notice', 'moderation.notices.patrolled');

    expect(RevisionPatrol::query()->where('revision_id', $edit->id)->sole()->user_id)->toBe($this->reviewer->id);
    patrolList($this->reviewer)->assertInertia(fn (Assert $page) => $page->has('entries', 0));
});

it('lists edits by people only, recent ones, flagged ones first', function () {
    $old = User::factory()->role(Role::Trusted)->create(['created_at' => now()->subDays(60)]);
    $this->travel(-40)->days();
    patrolEditBy($old, $this->occupation->slug, 3);
    $this->travelBack();

    $plain = patrolEditBy($old, $this->occupation->slug, 4);
    $flagged = patrolEditBy(User::factory()->create(['created_at' => now()->subDays(30)]), listedOccupation()->slug, 4, [
        'interest' => 'declared',
        'interest_note' => 'I run a plumbing course.',
    ]);
    $later = patrolEditBy($old, $this->occupation->slug, 5);

    patrolList($this->reviewer)->assertInertia(fn (Assert $page) => $page
        ->has('entries', 3)
        ->where('entries.0.revisionId', $flagged->id)
        ->where('entries.0.flags.0.reason', 'interest')
        ->where('entries.0.kind', 'proposal')
        ->where('entries.1.revisionId', $later->id)
        ->where('entries.2.revisionId', $plain->id));
});

it('refuses to revert an edit that is no longer the current value', function () {
    $user = User::factory()->role(Role::Trusted)->create(['created_at' => now()->subDays(30)]);
    $first = patrolEditBy($user, $this->occupation->slug, 4);
    patrolEditBy($user, $this->occupation->slug, 5);

    $this->actingAs($this->reviewer)
        ->get("/en/patrol/{$first->id}")
        ->assertInertia(fn (Assert $page) => $page->where('edit.current', false)->where('edit.canRevert', false));
    $this->actingAs($this->reviewer)
        ->post("/en/patrol/{$first->id}/revert")
        ->assertSessionHas('notice', 'moderation.notices.refused');

    expect(Revision::query()->whereNotNull('reverts_revision_id')->count())->toBe(0);
});

it('lets a reviewer revert a verified value', function () {
    $reviewer = User::factory()->role(Role::Reviewer)->create();
    $edit = patrolEditBy($reviewer, $this->occupation->slug, 4, ['sources' => [['url' => 'https://example.test/a', 'accessed_on' => '2026-11-01']]]);
    $slot = FieldValue::query()->findOrFail($edit->field_value_id);
    $this->actingAs($reviewer)->post("/en/review/{$slot->id}/status", ['status' => 'verified'])->assertSessionHas('notice', 'moderation.notices.status');

    $this->actingAs($this->reviewer)
        ->get("/en/patrol/{$edit->id}")
        ->assertInertia(fn (Assert $page) => $page->where('edit.status', 'verified')->where('edit.canRevert', true));
});
