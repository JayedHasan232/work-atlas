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
            'auth/*' => ['auth'],
            'dashboard' => ['shell'],
            'settings/*' => ['shell', 'settings'],
        ],
    ],

];
