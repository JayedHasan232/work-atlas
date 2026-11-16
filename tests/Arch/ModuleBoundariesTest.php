<?php

/*
 * A module uses another module's Actions, Queries, Data, Enums and Events, never its Models, Jobs,
 * Policies, Http or Filament classes (app/Modules/README.md).
 */

test('the module list matches the folders in app/Modules', function () {
    $folders = array_map('basename', glob(dirname(__DIR__, 2).'/app/Modules/*', GLOB_ONLYDIR) ?: []);

    expect($folders)->toEqualCanonicalizing(atlasModules());
});

foreach (atlasModules() as $module) {
    $others = array_values(array_diff(atlasModules(), [$module]));

    arch("{$module} uses only the public classes of other modules")
        ->expect("App\\Modules\\{$module}")
        ->not->toUse(array_merge(...array_map(modulePrivateNamespaces(...), $others)));
}

// Code outside app/Modules (kit code, providers, shared support) follows the same rule.
$outside = array_values(array_map(
    fn (string $folder): string => 'App\\'.basename($folder),
    array_filter(
        glob(dirname(__DIR__, 2).'/app/*', GLOB_ONLYDIR) ?: [],
        fn (string $folder): bool => basename($folder) !== 'Modules',
    ),
));

arch('code outside the modules uses only their public classes')
    ->expect($outside)
    ->not->toUse(array_merge(...array_map(modulePrivateNamespaces(...), atlasModules())));
