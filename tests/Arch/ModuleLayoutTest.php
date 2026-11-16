<?php

// The Laravel preset's placement rules, applied inside each module: a kind of class lives in its
// module folder, and nowhere else in the module. See app/Modules/README.md.

foreach (atlasModules() as $module) {
    $root = "App\\Modules\\{$module}";

    arch("{$module}: enums live in Enums")
        ->expect($root)
        ->not->toBeEnums()
        ->ignoring("{$root}\\Enums");

    arch("{$module}: Enums holds only enums")
        ->expect("{$root}\\Enums")
        ->toBeEnums();

    arch("{$module}: models live in Models")
        ->expect($root)
        ->not->toExtend('Illuminate\Database\Eloquent\Model')
        ->ignoring("{$root}\\Models");

    arch("{$module}: Models classes are Eloquent models")
        ->expect("{$root}\\Models")
        ->classes()
        ->toExtend('Illuminate\Database\Eloquent\Model')
        ->not->toHaveSuffix('Model');

    arch("{$module}: exceptions live in Exceptions")
        ->expect($root)
        ->not->toImplement(Throwable::class)
        ->ignoring("{$root}\\Exceptions");

    arch("{$module}: Exceptions classes are exceptions")
        ->expect("{$root}\\Exceptions")
        ->classes()
        ->toImplement(Throwable::class);

    arch("{$module}: controllers live in Http\\Controllers")
        ->expect($root)
        ->not->toHaveSuffix('Controller')
        ->ignoring("{$root}\\Http\\Controllers");

    arch("{$module}: Http\\Controllers classes are controllers")
        ->expect("{$root}\\Http\\Controllers")
        ->classes()
        ->toHaveSuffix('Controller');

    arch("{$module}: form requests live in Http\\Requests")
        ->expect($root)
        ->not->toExtend('Illuminate\Foundation\Http\FormRequest')
        ->ignoring("{$root}\\Http\\Requests");

    arch("{$module}: Http\\Requests classes are form requests")
        ->expect("{$root}\\Http\\Requests")
        ->classes()
        ->toExtend('Illuminate\Foundation\Http\FormRequest')
        ->toHaveSuffix('Request');

    arch("{$module}: jobs have a handle method")
        ->expect("{$root}\\Jobs")
        ->classes()
        ->toHaveMethod('handle');

    arch("{$module}: policies end in Policy")
        ->expect("{$root}\\Policies")
        ->classes()
        ->toHaveSuffix('Policy');

    // Commands, mail, notifications and providers stay in app/ (they call module actions).
    arch("{$module}: no commands, mail, notifications or service providers")
        ->expect($root)
        ->not->toExtend('Illuminate\Console\Command')
        ->not->toExtend('Illuminate\Mail\Mailable')
        ->not->toExtend('Illuminate\Notifications\Notification')
        ->not->toExtend('Illuminate\Support\ServiceProvider');
}
