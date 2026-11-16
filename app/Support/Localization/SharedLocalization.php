<?php

namespace App\Support\Localization;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;
use Inertia\ProvidesInertiaProperties;
use Inertia\RenderContext;

/**
 * The locale, language-switch URLs and translation strings shared with every Inertia page.
 *
 * Resolved when the page renders, not when the middleware runs, so it knows the page component
 * (to send only the translation groups that page needs) and the final locale.
 */
final class SharedLocalization implements ProvidesInertiaProperties
{
    /**
     * @return array<string, mixed>
     */
    public function toInertiaProperties(RenderContext $context): array
    {
        $locale = App::getLocale();
        $route = $context->request->route();
        $alternates = $route instanceof Route ? LocalizedRoutes::alternates($route) : [];

        return [
            'localization' => [
                'locale' => $locale,
                'prefix' => ($prefix = Locales::prefix($locale)) === null ? '' : "/{$prefix}",
                'canonical' => $alternates[$locale] ?? null,
                'locales' => array_map(fn (string $code): array => [
                    'code' => $code,
                    'name' => Locales::name($code),
                    'url' => $alternates[$code] ?? null,
                ], Locales::codes()),
            ],
            'translations' => $this->translations($context->component, $locale),
        ];
    }

    /**
     * The translation groups a page needs, with any key missing in the locale taken from the
     * fallback locale (the CI parity check keeps that from happening).
     *
     * @return array<string, mixed>
     */
    private function translations(string $component, string $locale): array
    {
        $fallback = Config::string('app.fallback_locale');
        $translations = [];

        foreach ($this->groupsFor($component) as $group) {
            $lines = Lang::get($group, [], $locale, false);
            $fallbackLines = Lang::get($group, [], $fallback, false);

            $translations[$group] = array_replace_recursive(
                is_array($fallbackLines) ? $fallbackLines : [],
                is_array($lines) ? $lines : [],
            );
        }

        return $translations;
    }

    /**
     * @return list<string>
     */
    private function groupsFor(string $component): array
    {
        /** @var list<string> $groups */
        $groups = Config::array('atlas.translations.always');

        /** @var array<string, list<string>> $pages */
        $pages = Config::array('atlas.translations.pages');

        foreach ($pages as $pattern => $pageGroups) {
            if (Str::is($pattern, $component)) {
                $groups = [...$groups, ...$pageGroups];
            }
        }

        return array_values(array_unique($groups));
    }
}
