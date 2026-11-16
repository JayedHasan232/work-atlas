<?php

use App\Models\User;
use App\Modules\AiPipeline\Data\AiRequest;
use App\Modules\AiPipeline\Drivers\FakeDriver;
use App\Modules\AiPipeline\Models\AiRun;
use App\Modules\Community\Actions\AnswerVerificationTask;
use App\Modules\Community\Actions\GenerateVerificationTasks;
use App\Modules\Community\Enums\Role;
use App\Modules\Community\Enums\TaskAnswer;
use App\Modules\Community\Models\VerificationTask;
use App\Modules\Knowledge\Actions\RecordRevision;
use App\Modules\Knowledge\Actions\SyncFieldDefinitions;
use App\Modules\Knowledge\Data\Actor;
use App\Modules\Knowledge\Data\RevisionInput;
use App\Modules\Knowledge\Data\SourceInput;
use App\Modules\Knowledge\Models\FieldDefinition;
use App\Modules\Knowledge\Models\Revision;
use App\Modules\Taxonomy\Enums\PublishState;
use App\Modules\Taxonomy\Models\IscoGroup;
use App\Modules\Taxonomy\Models\Occupation;
use App\Modules\Taxonomy\Models\OccupationName;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Feature and browser tests boot the application and run against the PostgreSQL test database
| (see phpunit.xml). Arch and unit tests don't touch the database.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function () {
        // Browser tests load the real, built assets.
        $this->withVite();
    })
    ->in('Browser');

/*
|--------------------------------------------------------------------------
| Modules
|--------------------------------------------------------------------------
|
| The modules under app/Modules (see app/Modules/README.md). The arch tests check each one; a test
| fails when this list and the folders disagree.
|
*/

/**
 * @return list<string>
 */
function atlasModules(): array
{
    return ['Taxonomy', 'Knowledge', 'Observations', 'Community', 'AiPipeline', 'Discovery', 'Publishing'];
}

/**
 * The namespaces inside each module that other code must not use.
 *
 * @return list<string>
 */
function modulePrivateNamespaces(string $module): array
{
    return array_map(
        fn (string $part): string => "App\\Modules\\{$module}\\{$part}",
        ['Models', 'Jobs', 'Policies', 'Http', 'Filament'],
    );
}

/*
|--------------------------------------------------------------------------
| Database constraints
|--------------------------------------------------------------------------
*/

/**
 * Runs a statement that the database should reject and returns its error message, or '(accepted)'
 * when it went through. The statement runs in a savepoint, so a rejection doesn't abort the test's
 * transaction (PostgreSQL refuses every later query in an aborted transaction).
 */
function databaseError(Closure $statement): string
{
    try {
        DB::transaction($statement);
    } catch (QueryException $exception) {
        return $exception->getMessage();
    }

    return '(accepted)';
}

/*
|--------------------------------------------------------------------------
| Knowledge write path
|--------------------------------------------------------------------------
|
| Shortcuts for tests that write values through RecordRevision, the only write path.
|
*/

function aiActor(): Actor
{
    return Actor::aiRun(AiRun::factory()->create()->id);
}

/**
 * A new person with the given role (contributor by default: their writes are proposals).
 */
function personActor(Role $role = Role::Contributor): Actor
{
    return Actor::user(User::factory()->role($role)->create());
}

function reviewerActor(): Actor
{
    return personActor(Role::Reviewer);
}

/**
 * Records a value through RecordRevision: by a new AI run (confidence 0.8) unless an actor is given,
 * on a new occupation unless one is given.
 *
 * @param  list<int>  $sourceIds
 */
function recordValue(
    FieldDefinition $field,
    mixed $value,
    ?Actor $actor = null,
    array $sourceIds = [],
    ?int $occupationId = null,
    ?string $locale = null,
): Revision {
    $actor ??= aiActor();

    return app(RecordRevision::class)->handle(new RevisionInput(
        occupationId: $occupationId ?? Occupation::factory()->create()->id,
        fieldDefinitionId: $field->id,
        value: $value,
        actor: $actor,
        locale: $locale,
        sourceIds: $sourceIds,
        confidence: $actor->isAi() ? 0.8 : null,
    ));
}

/*
|--------------------------------------------------------------------------
| Taxonomy import
|--------------------------------------------------------------------------
|
| The fixture in tests/Fixtures/taxonomy has real-shaped ISCO-08 codes with placeholder titles.
|
*/

function taxonomyFixture(string $name = 'isco-subset.csv'): string
{
    return base_path("tests/Fixtures/taxonomy/{$name}");
}

function fixtureSource(string $accessedOn = '2026-11-01'): SourceInput
{
    return new SourceInput(
        url: 'https://source.example.test/isco-subset.csv',
        accessedOn: $accessedOn,
        publisher: 'Test publisher',
        title: 'Test ISCO subset (placeholder titles, not ILO data)',
        license: 'CC0-1.0',
    );
}

/**
 * Writes a temporary file for one test and returns its path. The file is removed after the test.
 */
function temporaryFile(string $contents, string $extension = 'csv'): string
{
    $path = sys_get_temp_dir().'/work-atlas-test-'.bin2hex(random_bytes(8)).".{$extension}";
    file_put_contents($path, $contents);
    register_shutdown_function(fn () => @unlink($path));

    return $path;
}

/**
 * @return array<string, IscoGroup>
 */
function iscoGroupsByCode(): array
{
    return IscoGroup::query()->get()->keyBy('code')->all();
}

/*
|--------------------------------------------------------------------------
| Field definition files
|--------------------------------------------------------------------------
*/

/**
 * Writes field files into a new temporary folder and returns it, e.g.
 * fieldDirectory(['wave1.v1.yaml' => $yaml]).
 *
 * @param  array<string, string>  $files
 */
function fieldDirectory(array $files): string
{
    $directory = sys_get_temp_dir().'/work-atlas-fields-'.bin2hex(random_bytes(8));
    mkdir($directory);

    foreach ($files as $name => $contents) {
        file_put_contents("{$directory}/{$name}", $contents);
    }

    register_shutdown_function(function () use ($directory): void {
        array_map('unlink', glob("{$directory}/*") ?: []);
        @rmdir($directory);
    });

    return $directory;
}

/**
 * A small, valid wave 1 file: one 1–5 scale and one enum. $changes replaces parts of the text.
 *
 * @param  array<string, string>  $changes
 */
function sampleFieldFile(array $changes = []): string
{
    $yaml = <<<'YAML'
        wave: 1
        fields:
          - key: sample_scale
            version: 1
            group: workload
            type: scale_1_5
            label: { bn: "নমুনা স্কেল", en: "Sample scale" }
            help: { bn: "নমুনা সাহায্য", en: "Sample help" }
            anchors:
              1: { bn: "এক", en: "One" }
              2: { bn: "দুই", en: "Two" }
              3: { bn: "তিন", en: "Three" }
              4: { bn: "চার", en: "Four" }
              5: { bn: "পাঁচ", en: "Five" }
          - key: sample_choice
            version: 1
            group: conditions
            type: enum
            label: { bn: "নমুনা বাছাই", en: "Sample choice" }
            help: { bn: "নমুনা সাহায্য", en: "Sample help" }
            options:
              - { key: first, bn: "প্রথম", en: "First" }
              - { key: second, bn: "দ্বিতীয়", en: "Second" }

        YAML;

    return strtr($yaml, $changes);
}

/*
|--------------------------------------------------------------------------
| AI gateway
|--------------------------------------------------------------------------
|
| Tests never reach a real provider: fakeAi() binds a FakeDriver, points the registry at the test
| prompts in tests/Fixtures/{prompts,schemas} and removes the retry waits.
|
*/

function fakeAi(): FakeDriver
{
    $fake = new FakeDriver;
    app()->instance(FakeDriver::class, $fake);

    config([
        'atlas.ai.prompts_path' => base_path('tests/Fixtures/prompts'),
        'atlas.ai.schemas_path' => base_path('tests/Fixtures/schemas'),
        'atlas.ai.retry.backoff_ms' => [0, 0],
    ]);

    return $fake;
}

/**
 * A request for the test prompt test/sample v1 through the fake provider.
 *
 * @param  array<string, string>  $values
 */
function sampleAiRequest(array $values = [], string $model = 'fake-model', string $provider = 'fake'): AiRequest
{
    return new AiRequest(
        promptKey: 'test/sample',
        promptVersion: 1,
        values: $values + ['occupation' => 'Electrician', 'isco_code' => '7411'],
        provider: $provider,
        model: $model,
    );
}

/**
 * The fake provider, with the real prompt and schema files (data/prompts, data/schemas).
 */
function fakeExpansionAi(): FakeDriver
{
    $fake = fakeAi();

    config([
        'atlas.ai.prompts_path' => base_path('data/prompts'),
        'atlas.ai.schemas_path' => base_path('data/schemas'),
    ]);

    return $fake;
}

/**
 * A schema-valid output of taxonomy/expand-unit-group v1 with one occupation.
 *
 * @param  list<array<string, string>>|null  $namesEn
 * @return array<string, mixed>
 */
function expansionOutput(?array $namesEn = null): array
{
    return [
        'occupations' => [[
            'names_bn' => [['name' => 'প্লাম্বার', 'type' => 'primary'], ['name' => 'নলমিস্ত্রি', 'type' => 'local']],
            'names_en' => $namesEn ?? [['name' => 'Plumber', 'type' => 'primary']],
            'specializations' => [['bn' => 'স্যানিটারি মিস্ত্রি', 'en' => 'Sanitary fitter'], ['bn' => 'নলমিস্ত্রি', 'en' => 'plumber']],
            'work_arrangements' => ['self_employed', 'daily_wage'],
            'separate_because' => 'Installs and repairs water pipes; different tasks and training from electricians.',
            'confidence' => 0.7,
        ]],
        'note' => '',
    ];
}

/**
 * An occupation with its names written through Knowledge (the `names` field must exist).
 *
 * @param  array<string, list<array<string, string>>>  $names  per locale
 * @param  array<string, mixed>  $attributes
 */
function occupationNamed(array $names, array $attributes = []): Occupation
{
    $occupation = Occupation::factory()->create($attributes + ['isco_code' => '7126']);
    $field = FieldDefinition::query()->where('key', 'names')->sole();

    foreach ($names as $locale => $list) {
        recordValue($field, $list, occupationId: $occupation->id, locale: $locale);
    }

    return $occupation;
}

/**
 * @return list<array{0: string, 1: string, 2: string, 3: string|null}> [locale, name, type, region]
 */
function searchNamesOf(int $occupationId): array
{
    return OccupationName::query()->where('occupation_id', $occupationId)->orderBy('locale')->orderBy('id')->get()
        ->map(fn (OccupationName $name) => [$name->locale, $name->name, $name->type->value, $name->region])->all();
}

/**
 * A listed occupation with Bangla and English names, ready for Wave 1, with the real field definitions
 * (data/fields) and the real prompts (data/prompts) on the fake driver.
 */
function wave1Setup(): FakeDriver
{
    $fake = fakeExpansionAi();
    app(SyncFieldDefinitions::class)->handle(base_path('data/fields'));
    IscoGroup::factory()->create(['code' => '7126']);

    return $fake;
}

/**
 * @param  array<string, mixed>  $names
 */
function listedOccupation(array $names = []): Occupation
{
    $occupation = occupationNamed($names ?: [
        'bn' => [['name' => 'প্লাম্বার', 'type' => 'primary'], ['name' => 'নলমিস্ত্রি', 'type' => 'local']],
        'en' => [['name' => 'Plumber', 'type' => 'primary']],
    ]);
    $occupation->update(['publish_state' => PublishState::Listed]);

    return $occupation;
}

/**
 * A schema-valid output of wave1/fields v1. Each value can be replaced: ['physical_workload' => 5].
 *
 * @param  array<string, mixed>  $values
 * @return array<string, mixed>
 */
function wave1Output(array $values = []): array
{
    $defaults = [
        'education_required' => 'none',
        'formal_training' => 'helpful',
        'physical_workload' => 3,
        'mental_workload' => 3,
        'work_setting' => ['indoor', 'outdoor'],
        'work_time' => ['day'],
        'seasonal' => 'year_round',
        'time_to_learn' => 'months_3_12',
        'time_to_first_income' => 'months_1_6',
        'part_time_possible' => 'sometimes',
        'available_in' => ['rural', 'urban'],
    ];

    return ['fields' => array_map(
        fn (mixed $value): array => ['value' => $value, 'confidence' => 0.6],
        array_replace($defaults, $values),
    )];
}

/*
|--------------------------------------------------------------------------
| Micro-tasks (PLAN M17)
|--------------------------------------------------------------------------
|
| Call wave1Setup() first: the tasks ask about real Wave 1 fields on a listed occupation.
|
*/

/**
 * Records a value of a Wave 1 field (by a new AI run unless an actor is given) on a listed occupation
 * and makes the micro-tasks. Returns the open task about that value.
 */
function taskFor(string $field = 'physical_workload', mixed $value = 3, ?Actor $actor = null, ?Occupation $occupation = null): VerificationTask
{
    $occupation ??= listedOccupation();
    $revision = recordValue(FieldDefinition::query()->where('key', $field)->sole(), $value, $actor, occupationId: $occupation->id);
    app(GenerateVerificationTasks::class)->handle();

    return VerificationTask::query()->where('revision_id', $revision->id)->sole();
}

/**
 * One answer by a person (a new contributor unless one is given). Returns that person.
 */
function answerTask(VerificationTask $task, TaskAnswer $answer, ?User $user = null): User
{
    $user ??= User::factory()->create();
    app(AnswerVerificationTask::class)->handle($task->id, $user->id, $answer);

    return $user;
}
