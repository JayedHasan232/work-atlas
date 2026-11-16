<?php

use App\Modules\Knowledge\Models\FieldValue;
use App\Modules\Knowledge\Models\Revision;
use App\Modules\Knowledge\Models\RevisionReview;

/*
 * One write path (D3): field values, revisions and reviews change only through Knowledge\Actions.
 * Queries read them and the models relate to each other; nothing else touches these classes, not even
 * Knowledge's own controllers, jobs, policies or Filament resources. Other modules are already kept
 * away from Knowledge\Models by ModuleBoundariesTest. Factories and tests build fixtures directly.
 */

arch('only Knowledge actions, queries and models use the value and history models')
    ->expect([FieldValue::class, Revision::class, RevisionReview::class])
    ->toOnlyBeUsedIn([
        'App\Modules\Knowledge\Actions',
        'App\Modules\Knowledge\Queries',
        'App\Modules\Knowledge\Models',
        'Database\Factories\Knowledge',
    ]);

arch('Knowledge actions are final and have one public entry point')
    ->expect('App\Modules\Knowledge\Actions')
    ->toBeFinal()
    ->toHaveMethod('handle');
