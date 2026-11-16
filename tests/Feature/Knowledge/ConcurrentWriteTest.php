<?php

use App\Modules\Knowledge\Actions\ChangeStatus;
use App\Modules\Knowledge\Actions\LockValueSlot;
use App\Modules\Knowledge\Actions\RecordRevision;
use App\Modules\Knowledge\Actions\RevertRevision;
use App\Modules\Knowledge\Actions\ReviewRevision;
use App\Modules\Knowledge\Data\RevisionInput;
use App\Modules\Knowledge\Enums\FieldStatus;
use App\Modules\Knowledge\Enums\ReviewDecision;
use App\Modules\Knowledge\Models\FieldDefinition;
use App\Modules\Knowledge\Models\FieldValue;
use App\Modules\Knowledge\Models\Revision;
use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

// Two writes to one value slot must run one after the other: each action locks the slot's row first.

/**
 * A second database connection, outside the test's transaction: what it commits is visible to the
 * test, and its locks block the test's connection like another request would.
 */
function otherConnection(): Connection
{
    config(['database.connections.pgsql_other' => config('database.connections.pgsql')]);

    $other = DB::connection('pgsql_other');
    $other->statement("SET lock_timeout = '5s'");

    return $other;
}

it('waits for the slot row lock held by another transaction', function () {
    $other = otherConnection();
    $now = now();
    $code = '9'.random_int(100, 999);
    $ids = [];

    try {
        foreach ([1, 2, 3, 4] as $level) {
            $other->table('isco_groups')->insert([
                'code' => substr($code, 0, $level),
                'level' => $level,
                'parent_code' => $level > 1 ? substr($code, 0, $level - 1) : null,
                'title_en' => "Concurrency test group {$level}",
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $ids['occupation'] = $other->table('occupations')->insertGetId([
            'slug' => 'concurrency-test-'.$code, 'isco_code' => $code, 'created_at' => $now, 'updated_at' => $now,
        ]);
        $ids['field'] = $other->table('field_definitions')->insertGetId([
            'key' => 'concurrency_test_'.$code, 'version' => 1, 'wave' => 1, 'group' => 'filter',
            'type' => 'boolean', 'created_at' => $now, 'updated_at' => $now,
        ]);
        $ids['slot'] = $other->table('field_values')->insertGetId([
            'occupation_id' => $ids['occupation'], 'field_definition_id' => $ids['field'],
            'created_at' => $now, 'updated_at' => $now,
        ]);

        // Another request is writing to this slot and holds its row lock.
        $other->beginTransaction();
        $other->table('field_values')->where('id', $ids['slot'])->lockForUpdate()->first();

        DB::statement("SET LOCAL lock_timeout = '300ms'");

        $actor = aiActor();
        $error = null;

        try {
            app(RecordRevision::class)->handle(new RevisionInput(
                occupationId: $ids['occupation'],
                fieldDefinitionId: $ids['field'],
                value: true,
                actor: $actor,
                confidence: 0.9,
            ));
        } catch (QueryException $exception) {
            $error = $exception;
        }

        // It waited at the locking read, before reading the current value or writing anything.
        expect($error)->not->toBeNull()
            ->and($error?->getMessage())->toContain('lock timeout')
            ->and($error?->getSql())->toStartWith('select * from "field_values"')->toEndWith('for update');

        expect(Revision::query()->where('field_value_id', $ids['slot'])->count())->toBe(0);
    } finally {
        if ($other->transactionLevel() > 0) {
            $other->rollBack();
        }

        $other->table('field_values')->where('id', $ids['slot'] ?? 0)->delete();
        $other->table('field_definitions')->where('id', $ids['field'] ?? 0)->delete();
        $other->table('occupations')->where('id', $ids['occupation'] ?? 0)->delete();
        $other->table('isco_groups')->where('code', 'like', $code[0].'%')->where('title_en', 'like', 'Concurrency test%')->delete();
        DB::purge('pgsql_other');
    }
});

it('locks the slot row in every action that changes it', function (Closure $act) {
    $field = FieldDefinition::factory()->create();
    $current = recordValue($field, 2);
    $proposal = recordValue($field, 4, personActor(), occupationId: $current->fieldValue->occupation_id);

    DB::enableQueryLog();
    $act($current, $proposal);
    $locks = collect(DB::getQueryLog())
        ->pluck('query')
        ->filter(fn (string $sql): bool => str_contains($sql, 'from "field_values"') && str_ends_with($sql, 'for update'));
    DB::disableQueryLog();

    expect($locks)->not->toBeEmpty();
})->with([
    'RecordRevision' => fn (Revision $current) => recordValue(
        $current->fieldValue->definition, 3, occupationId: $current->fieldValue->occupation_id,
    ),
    'ChangeStatus' => fn (Revision $current) => app(ChangeStatus::class)
        ->handle($current->field_value_id, FieldStatus::Verified, personActor()),
    'ReviewRevision' => fn (Revision $current, Revision $proposal) => app(ReviewRevision::class)
        ->handle($proposal->id, personActor(), ReviewDecision::Rejected),
    'RevertRevision' => fn (Revision $current) => app(RevertRevision::class)
        ->handle($current->id, personActor()),
]);

it('creates a slot once, however often it is locked', function () {
    $field = FieldDefinition::factory()->create();
    $occupationId = recordValue($field, 2)->fieldValue->occupation_id;

    $ids = DB::transaction(fn () => [
        app(LockValueSlot::class)->handle($occupationId, $field->id, null, null)->id,
        app(LockValueSlot::class)->handle($occupationId, $field->id, null, null)->id,
    ]);

    expect($ids[0])->toBe($ids[1])
        ->and(FieldValue::query()->where('field_definition_id', $field->id)->count())->toBe(1);
});
