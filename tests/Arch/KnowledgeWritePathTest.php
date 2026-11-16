<?php

use App\Modules\Knowledge\Models\FieldValue;
use App\Modules\Knowledge\Models\Revision;
use App\Modules\Knowledge\Models\RevisionReview;

/*
 * One write path (D3): field values, revisions and reviews change only through Knowledge\Actions.
 * Queries read them and the models relate to each other; nothing else touches these classes, not even
 * Knowledge's own controllers, jobs or policies. Other modules are already kept away from
 * Knowledge\Models by ModuleBoundariesTest. Factories and tests build fixtures directly.
 *
 * The one exception is Knowledge's admin resources (M8, D37): Filament needs the model class to list
 * and show revisions. They only read: each extends ReadOnlyResource, and
 * Feature/Admin/ReadOnlyResourcesTest checks they have no create, edit or delete.
 */

arch('only Knowledge actions, queries and models use the value and history models')
    ->expect([FieldValue::class, Revision::class, RevisionReview::class])
    ->toOnlyBeUsedIn([
        'App\Modules\Knowledge\Actions',
        'App\Modules\Knowledge\Queries',
        'App\Modules\Knowledge\Models',
        'App\Modules\Knowledge\Filament',
        'Database\Factories\Knowledge',
    ]);

arch('Knowledge actions are final and have one public entry point')
    ->expect('App\Modules\Knowledge\Actions')
    ->toBeFinal()
    ->toHaveMethod('handle');
