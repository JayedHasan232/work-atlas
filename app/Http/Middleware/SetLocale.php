<?php

namespace App\Http\Middleware;

use App\Support\Localization\Locales;
use App\Support\Localization\LocalizedRoutes;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Picks the locale for a web request.
 *
 * 1. A public page's URL decides (decision A10): `/…` is Bangla, `/en/…` is English. The choice is
 *    remembered in the session.
 * 2. Other pages (sign-in, settings, dashboard) have one URL for both languages, so they use the
 *    remembered choice, set by the last public page or by the language switcher.
 * 3. Otherwise the default locale, Bangla.
 */
final class SetLocale
{
    public const string SESSION_KEY = 'locale';

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route();
        $locale = LocalizedRoutes::localeOf($route instanceof Route ? $route : null);

        if ($locale !== null) {
            $request->session()->put(self::SESSION_KEY, $locale);
        } else {
            $remembered = $request->session()->get(self::SESSION_KEY);
            $locale = Locales::isSupported($remembered) ? $remembered : Locales::default();
        }

        App::setLocale($locale);

        return $next($request);
    }
}
