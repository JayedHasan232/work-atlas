<?php

use App\Models\User;
use App\Modules\AiPipeline\Models\AiRun;
use App\Modules\Knowledge\Models\FieldValue;
use App\Modules\Knowledge\Models\Revision;
use Illuminate\Support\Facades\DB;

// Every revision has exactly one author: a person or an AI run (rule 5). Checked by the database,
// so no code path can write an anonymous or doubly-authored revision.

function insertRevision(?int $userId, ?int $aiRunId): string
{
    $fieldValue = FieldValue::factory()->create();

    return databaseError(fn () => DB::table('revisions')->insert([
        'field_value_id' => $fieldValue->id,
        'new_value' => json_encode(3),
        'user_id' => $userId,
        'ai_run_id' => $aiRunId,
    ]));
}

test('a revision written by a user is accepted', function () {
    expect(insertRevision(User::factory()->create()->id, null))->toBe('(accepted)');
});

test('a revision written by an AI run is accepted', function () {
    expect(insertRevision(null, AiRun::factory()->create()->id))->toBe('(accepted)');
});

test('a revision without an author is rejected', function () {
    expect(insertRevision(null, null))->toContain('revisions_one_author_check');
});

test('a revision with both a user and an AI run is rejected', function () {
    expect(insertRevision(User::factory()->create()->id, AiRun::factory()->create()->id))
        ->toContain('revisions_one_author_check');
});

test('the factory writes one author either way', function () {
    $byUser = Revision::factory()->create();
    $byAi = Revision::factory()->byAiRun()->create();

    expect($byUser->user_id)->not->toBeNull()
        ->and($byUser->ai_run_id)->toBeNull()
        ->and($byAi->user_id)->toBeNull()
        ->and($byAi->ai_run_id)->not->toBeNull();
});

test('an author with revisions cannot be deleted from under them', function () {
    $revision = Revision::factory()->create();

    expect(databaseError(fn () => DB::delete('DELETE FROM users WHERE id = ?', [$revision->user_id])))
        ->toContain('revisions_user_id_foreign');
    expect(databaseError(fn () => DB::delete('DELETE FROM ai_runs WHERE id = ?', [Revision::factory()->byAiRun()->create()->ai_run_id])))
        ->toContain('revisions_ai_run_id_foreign');
});
