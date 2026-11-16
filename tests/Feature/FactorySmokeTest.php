<?php

use App\Modules\AiPipeline\Enums\AiRunStatus;
use App\Modules\AiPipeline\Models\AiRun;
use App\Modules\Knowledge\Enums\FieldStatus;
use App\Modules\Knowledge\Enums\FieldType;
use App\Modules\Knowledge\Enums\ReviewDecision;
use App\Modules\Knowledge\Models\FieldDefinition;
use App\Modules\Knowledge\Models\FieldValue;
use App\Modules\Knowledge\Models\Revision;
use App\Modules\Knowledge\Models\RevisionReview;
use App\Modules\Knowledge\Models\Source;
use App\Modules\Taxonomy\Enums\NameType;
use App\Modules\Taxonomy\Enums\PublishState;
use App\Modules\Taxonomy\Models\IscoGroup;
use App\Modules\Taxonomy\Models\Occupation;
use App\Modules\Taxonomy\Models\OccupationName;
use Illuminate\Database\Eloquent\Model;

// Each factory writes a row that passes every database constraint, and reads back with its casts.

dataset('core models', [
    IscoGroup::class,
    Occupation::class,
    OccupationName::class,
    FieldDefinition::class,
    FieldValue::class,
    Source::class,
    AiRun::class,
    Revision::class,
    RevisionReview::class,
]);

test('the factory creates a valid row', function (string $model) {
    /** @var class-string<Model> $model */
    $created = $model::factory()->count(2)->create();

    expect($created)->toHaveCount(2)
        ->and($model::query()->whereKey($created->modelKeys())->count())->toBe(2);
})->with('core models');

test('the factory states create valid rows', function () {
    $target = Occupation::factory()->published()->create();
    $merged = Occupation::factory()->mergedInto($target)->create();
    $regional = OccupationName::factory()->regional('chattogram')->bangla('নলমিস্ত্রি')->create();
    $primary = OccupationName::factory()->primary()->create();
    $sensitive = FieldDefinition::factory()->enum()->sensitive()->create();
    $failedRun = AiRun::factory()->failed()->create();
    $rejected = RevisionReview::factory()->rejected()->create();

    expect($target->refresh()->publish_state)->toBe(PublishState::Published)
        ->and($merged->refresh()->mergedInto?->is($target))->toBeTrue()
        ->and($regional->refresh()->type)->toBe(NameType::Regional)
        ->and($regional->locale)->toBe('bn')
        ->and($primary->refresh()->type)->toBe(NameType::Primary)
        ->and($sensitive->refresh()->type)->toBe(FieldType::Enum)
        ->and($sensitive->requires_source)->toBeTrue()
        ->and($failedRun->refresh()->status)->toBe(AiRunStatus::Failed)
        ->and($rejected->refresh()->decision)->toBe(ReviewDecision::Rejected);
});

test('relations between the core models resolve', function () {
    $occupation = Occupation::factory()->create();
    $name = OccupationName::factory()->primary()->create(['occupation_id' => $occupation->id]);
    $value = FieldValue::factory()->create(['occupation_id' => $occupation->id]);
    $revision = Revision::factory()->create(['field_value_id' => $value->id, 'new_value' => ['indoor', 'outdoor']]);
    $source = Source::factory()->create();
    $revision->sources()->attach($source);

    expect($occupation->iscoGroup->level)->toBe(IscoGroup::UNIT_GROUP_LEVEL)
        ->and($occupation->iscoGroup->parent?->parent?->parent?->level)->toBe(1)
        ->and($occupation->names->first()?->is($name))->toBeTrue()
        ->and($value->status)->toBe(FieldStatus::AiDraft)
        ->and($value->definition->type)->toBe(FieldType::Scale1To5)
        ->and($value->revisions->first()?->new_value)->toBe(['indoor', 'outdoor'])
        ->and($revision->sources->first()?->is($source))->toBeTrue()
        ->and($source->revisions->first()?->is($revision))->toBeTrue();
});
