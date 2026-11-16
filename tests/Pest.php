<?php

use App\Models\User;
use App\Modules\AiPipeline\Models\AiRun;
use App\Modules\Knowledge\Actions\RecordRevision;
use App\Modules\Knowledge\Data\Actor;
use App\Modules\Knowledge\Data\RevisionInput;
use App\Modules\Knowledge\Data\SourceInput;
use App\Modules\Knowledge\Models\FieldDefinition;
use App\Modules\Knowledge\Models\Revision;
use App\Modules\Taxonomy\Models\IscoGroup;
use App\Modules\Taxonomy\Models\Occupation;
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

function personActor(): Actor
{
    return Actor::user(User::factory()->create());
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
