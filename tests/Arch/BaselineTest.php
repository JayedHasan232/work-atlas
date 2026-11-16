<?php

arch()->preset()->php();

arch()->preset()->security();

// The Laravel preset assumes a flat app (every enum in App\Enums, every model in App\Models, …).
// Modules keep these inside their own folders (app/Modules/README.md), so they are left out here and
// Arch/ModuleLayoutTest applies the same rules per module.
arch()->preset()->laravel()->ignoring('App\Modules');

arch('debugging calls are not left in the code')
    ->expect(['dd', 'ddd', 'dump', 'ray', 'var_dump'])
    ->not->toBeUsed();
