<?php

/*
 * Controllers stay thin: they validate, call an Action or Query, and return a response. They never
 * query the database themselves (CLAUDE.md, "Module layout").
 */

$controllers = [
    'App\Http\Controllers',
    ...array_map(fn (string $module): string => "App\\Modules\\{$module}\\Http\\Controllers", atlasModules()),
];

arch('controllers do not query the database')
    ->expect($controllers)
    ->not->toUse([
        'Illuminate\Support\Facades\DB',
        'Illuminate\Database\Query\Builder',
        'Illuminate\Database\Eloquent\Builder',
        'Illuminate\Database\Eloquent\Model',
        'App\Models',
        ...array_map(fn (string $module): string => "App\\Modules\\{$module}\\Models", atlasModules()),
    ]);
