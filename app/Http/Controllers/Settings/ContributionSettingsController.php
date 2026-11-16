<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Modules\Community\Actions\SetAnonymousCredit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Settings → Contributions (PLAN M16): whether the person's name appears in the public credit.
 */
class ContributionSettingsController extends Controller
{
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/contributions', [
            'anonymous' => (bool) $request->user()?->anonymous_credit,
        ]);
    }

    public function update(Request $request, SetAnonymousCredit $setAnonymous): RedirectResponse
    {
        $data = $request->validate(['anonymous' => ['required', 'boolean']]);
        $setAnonymous->handle((int) $request->user()?->getAuthIdentifier(), (bool) $data['anonymous']);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('contribute.settings.saved')]);

        return to_route('contributions.edit');
    }
}
