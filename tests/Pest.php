<?php

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
