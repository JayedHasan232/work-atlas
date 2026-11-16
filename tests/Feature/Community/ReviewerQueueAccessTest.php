<?php

use App\Models\User;
use App\Modules\Community\Enums\Role;
use App\Modules\Community\Models\RevisionPatrol;
use App\Modules\Knowledge\Actions\RecordSource;
use App\Modules\Knowledge\Enums\FieldStatus;
use App\Modules\Knowledge\Models\FieldDefinition;
use App\Modules\Knowledge\Models\FieldValue;
use App\Modules\Knowledge\Models\Revision;
use App\Modules\Knowledge\Models\RevisionReview;
use Inertia\Testing\AssertableInertia as Assert;

/*
 * PLAN M18 (D66): the review queue lives in the public site, for reviewers and above. A reviewer sees
 * sensitive values that are not verified yet (the public never does), accepts or rejects suggested
 * changes and sets the status. Contributors, trusted contributors and blocked reviewers cannot.
 */

beforeEach(function () {
    wave1Setup();
    $this->occupation = listedOccupation();
    $this->sensitive = FieldDefinition::factory()->text()->sensitive()->create();
    $source = app(RecordSource::class)->handle(fixtureSource());
    $this->current = recordValue($this->sensitive, 'Current legal note', sourceIds: [$source], occupationId: $this->occupation->id, locale: 'en');
    $this->proposal = recordValue($this->sensitive, 'Suggested legal note', personActor(), [$source], $this->occupation->id, 'en');
    $this->slot = FieldValue::query()->findOrFail($this->current->field_value_id);
    $this->reviewer = User::factory()->role(Role::Reviewer)->create();
});

it('keeps the moderation pages from guests, contributors, trusted contributors and blocked reviewers', function () {
    $this->get('/en/review')->assertRedirect('/login');
    $this->get('/en/patrol')->assertRedirect('/login');

    foreach ([Role::Contributor, Role::Trusted] as $role) {
        $user = User::factory()->role($role)->create();
        $this->actingAs($user)->get('/en/review')->assertForbidden();
        $this->actingAs($user)->get("/en/review/{$this->slot->id}")->assertForbidden();
        $this->actingAs($user)->get('/en/patrol')->assertForbidden();
        $this->actingAs($user)->post("/en/review/{$this->slot->id}/proposals/{$this->proposal->id}", ['decision' => 'accepted'])->assertForbidden();
    }

    $blocked = User::factory()->role(Role::Reviewer)->create(['blocked_at' => now()]);
    $this->actingAs($blocked)->get('/en/review')->assertForbidden();

    expect($this->slot->refresh()->current_revision_id)->toBe($this->current->id);
});

it('lists waiting values for a reviewer, sensitive ones included, and shares the moderate flag', function () {
    $this->actingAs($this->reviewer)
        ->get('/en/review')
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex')
        ->assertInertia(fn (Assert $page) => $page
            ->component('moderation/review-queue')
            ->where('auth.canModerate', true)
            ->where('items.0.fieldValueId', $this->slot->id)
            ->where('items.0.reason', 'pending_proposal')
            ->where('items.0.proposals', 1)
            ->where('items.0.url', "/en/review/{$this->slot->id}"));
});

it('shows a reviewer the sensitive value that is not verified, with the suggested change and its diff', function () {
    $this->actingAs($this->reviewer)
        ->get("/en/review/{$this->slot->id}")
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex')
        ->assertInertia(fn (Assert $page) => $page
            ->component('moderation/review-slot')
            ->where('slot.field.sensitive', true)
            ->where('slot.status', 'sourced')
            ->where('slot.current', ['kind' => 'text', 'text' => 'Current legal note'])
            ->where('slot.proposals.0.revisionId', $this->proposal->id)
            ->where('slot.proposals.0.value', ['kind' => 'text', 'text' => 'Suggested legal note'])
            ->where('slot.proposals.0.kind', 'proposal')
            ->where('slot.proposals.0.diff', [['op' => '-', 'line' => '"Current legal note"'], ['op' => '+', 'line' => '"Suggested legal note"']])
            ->where('slot.statuses', ['community_supported', 'verified', 'disputed']));
});

it('lets a reviewer accept a suggested change, and marks it patrolled', function () {
    $this->actingAs($this->reviewer)
        ->post("/en/review/{$this->slot->id}/proposals/{$this->proposal->id}", ['decision' => 'accepted', 'comment' => 'Checked the source.'])
        ->assertRedirect("/en/review/{$this->slot->id}")
        ->assertSessionHas('notice', 'moderation.notices.accepted');

    expect(Revision::query()->findOrFail($this->slot->refresh()->current_revision_id)->new_value)->toBe('Suggested legal note')
        ->and(RevisionPatrol::query()->where('revision_id', $this->proposal->id)->value('user_id'))->toBe($this->reviewer->id);
});

it('lets a reviewer reject a suggested change', function () {
    $this->actingAs($this->reviewer)
        ->post("/en/review/{$this->slot->id}/proposals/{$this->proposal->id}", ['decision' => 'rejected'])
        ->assertSessionHas('notice', 'moderation.notices.rejected');

    expect($this->slot->refresh()->current_revision_id)->toBe($this->current->id)
        ->and(RevisionReview::query()->where('revision_id', $this->proposal->id)->sole()->decision->value)->toBe('rejected');
});

it('lets a reviewer verify a value', function () {
    $this->actingAs($this->reviewer)
        ->post("/en/review/{$this->slot->id}/status", ['status' => 'verified'])
        ->assertRedirect("/en/review/{$this->slot->id}")
        ->assertSessionHas('notice', 'moderation.notices.status');

    expect($this->slot->refresh()->status)->toBe(FieldStatus::Verified);
});

it('refuses a revision that belongs to another value', function () {
    $other = recordValue(FieldDefinition::query()->where('key', 'physical_workload')->sole(), 4, personActor(), occupationId: $this->occupation->id);

    $this->actingAs($this->reviewer)
        ->post("/en/review/{$this->slot->id}/proposals/{$other->id}", ['decision' => 'accepted'])
        ->assertSessionHas('notice', 'moderation.notices.refused');

    expect(RevisionReview::query()->where('revision_id', $other->id)->exists())->toBeFalse();
});

it('validates the decision and the status', function () {
    $this->actingAs($this->reviewer)
        ->post("/en/review/{$this->slot->id}/proposals/{$this->proposal->id}", ['decision' => 'maybe'])
        ->assertSessionHasErrors('decision');
    $this->actingAs($this->reviewer)
        ->post("/en/review/{$this->slot->id}/status", ['status' => 'perfect'])
        ->assertSessionHasErrors('status');
});

it('answers 404 for a value or edit that does not exist', function () {
    $this->actingAs($this->reviewer)->get('/en/review/999999')->assertNotFound();
    $this->actingAs($this->reviewer)->get('/en/patrol/999999')->assertNotFound();
});
