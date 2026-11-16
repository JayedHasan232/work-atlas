<?php

namespace App\Support\Localization;

use Closure;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as Router;

/**
 * Public pages exist once per locale (decision A10): Bangla at the root (`/occupations/electrician`,
 * route name `occupations.show`), English under /en (`/en/occupations/electrician`, route name
 * `en.occupations.show`). The locale is stored on the route itself, so SetLocale can read it.
 */
final class LocalizedRoutes
{
    /**
     * Register the routes defined in the closure once per supported locale.
     */
    public static function register(Closure $routes): void
    {
        foreach (Locales::codes() as $locale) {
            $prefix = Locales::prefix($locale);

            Router::group(array_filter([
                'locale' => $locale,
                'prefix' => $prefix,
                'as' => $prefix === null ? null : "{$prefix}.",
            ]), $routes);
        }
    }

    /**
     * The locale a route was registered for, or null when the route is not localized.
     */
    public static function localeOf(?Route $route): ?string
    {
        $locale = $route?->getAction('locale');

        return Locales::isSupported($locale) ? $locale : null;
    }

    /**
     * The URL of the same page in every locale, keyed by locale, without the query string.
     *
     * @return array<string, string>
     */
    public static function alternates(Route $route): array
    {
        $locale = self::localeOf($route);
        $name = $route->getName();

        if ($locale === null || $name === null) {
            return [];
        }

        $prefix = Locales::prefix($locale);
        $baseName = $prefix === null ? $name : substr($name, strlen($prefix) + 1);

        // Only the URI's own parameters: Route::inertia() keeps the component and props in the
        // route defaults, and route() would append those as a query string.
        $parameters = array_intersect_key($route->parameters(), array_flip($route->parameterNames()));

        $urls = [];

        foreach (Locales::codes() as $code) {
            $codePrefix = Locales::prefix($code);
            $urls[$code] = route($codePrefix === null ? $baseName : "{$codePrefix}.{$baseName}", $parameters);
        }

        return $urls;
    }
}
