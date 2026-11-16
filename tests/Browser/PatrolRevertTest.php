<?php

use App\Models\User;
use App\Modules\Community\Enums\Role;
use App\Modules\Knowledge\Actions\RecordRevision;
use App\Modules\Knowledge\Data\Actor;
use App\Modules\Knowledge\Data\RevisionInput;
use App\Modules\Knowledge\Models\FieldDefinition;
use App\Modules\Knowledge\Models\FieldValue;
use App\Modules\Knowledge\Models\Revision;

// PLAN M18: on a phone, a reviewer finds a recent edit on the patrol list, reads its diff and reverts
// it in three clicks, without JavaScript errors or accessibility issues.

beforeEach(function () {
    wave1Setup();
});

test('a reviewer reverts an edit from the patrol list in three clicks', function () {
    $occupation = listedOccupation();
    $field = FieldDefinition::query()->where('key', 'physical_workload')->sole();
    recordValue($field, 2, occupationId: $occupation->id);
    $trusted = User::factory()->role(Role::Trusted)->create(['name' => 'Rahima Akter', 'created_at' => now()->subDays(30)]);
    $edit = app(RecordRevision::class)->handle(new RevisionInput(
        occupationId: $occupation->id,
        fieldDefinitionId: $field->id,
        value: 5,
        actor: Actor::user($trusted),
        comment: 'Heavy lifting every day.',
    ));
    $this->actingAs(User::factory()->role(Role::Reviewer)->create());

    $page = visit('/en/patrol')->on()->mobile();

    $page->assertSee('Patrol recent edits')
        ->assertSee('Rahima Akter')
        ->assertNoJavaScriptErrors()
        ->assertNoAccessibilityIssues()
        ->click('Look at this edit')
        ->assertSee('Heavy lifting every day.')
        ->assertNoAccessibilityIssues()
        ->click('[data-test="revert"]')
        ->click('[data-test="revert-confirm"]')
        ->assertSee('The edit was reverted. The history keeps both.')
        ->assertSee('Every recent edit has been looked at.')
        ->assertNoJavaScriptErrors();

    $current = Revision::query()->findOrFail(FieldValue::query()->findOrFail($edit->field_value_id)->current_revision_id);

    expect($current->reverts_revision_id)->toBe($edit->id)
        ->and($current->new_value)->toBe(2);
});
