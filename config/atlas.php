<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Locales
    |--------------------------------------------------------------------------
    |
    | Supported locales in display order. Public pages exist once per locale: Bangla at the root of
    | the URL, English under /en (decision A10). The default and fallback locales are set in
    | config/app.php.
    |
    */

    'locales' => [
        'bn' => ['name' => 'বাংলা', 'prefix' => null],
        'en' => ['name' => 'English', 'prefix' => 'en'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Translations shared with React
    |--------------------------------------------------------------------------
    |
    | Translation groups are the files in lang/<locale>/. "always" is sent with every page; "pages"
    | adds groups by Inertia component name (Str::is patterns), so a page only receives the strings
    | it uses. Laravel's own groups (validation, passwords, pagination) stay on the server.
    |
    */

    'translations' => [
        'always' => ['common'],
        'pages' => [
            'welcome' => ['home'],
            'contribution-terms' => ['terms'],
            'auth/*' => ['auth'],
            'dashboard' => ['shell'],
            'settings/*' => ['shell', 'settings'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Contribution terms
    |--------------------------------------------------------------------------
    |
    | The version of the contribution terms (page contribution-terms, text in the "terms" group)
    | that signup asks people to accept. A new version of the text gets a new value, so every agreement
    | stays tied to the text that was shown.
    |
    */

    'contribution_terms_version' => '2026-11-draft',

];
