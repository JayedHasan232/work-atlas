<?php

namespace App\Http\Controllers;

use App\Modules\Community\Actions\RecordContributionConsent;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The contribution terms that signup asks people to accept, with the version they would agree to.
 */
final class ContributionTermsController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('contribution-terms', [
            'version' => RecordContributionConsent::currentVersion(),
        ]);
    }
}
