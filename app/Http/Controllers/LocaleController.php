<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use App\Http\Requests\UpdateLocaleRequest;
use Illuminate\Http\RedirectResponse;

/**
 * The language switcher on pages that have one URL for both languages (sign-in, settings,
 * dashboard). Public pages switch language by linking to their other-language URL instead.
 */
final class LocaleController extends Controller
{
    public function update(UpdateLocaleRequest $request): RedirectResponse
    {
        $request->session()->put(SetLocale::SESSION_KEY, $request->string('locale')->toString());

        return back();
    }
}
