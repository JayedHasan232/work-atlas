<?php

use App\Modules\AiPipeline\Drivers\FakeDriver;

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

    /*
    |--------------------------------------------------------------------------
    | AI gateway
    |--------------------------------------------------------------------------
    |
    | Every model call goes through App\Modules\AiPipeline\AiGateway (PLAN M9). Only the fake driver
    | exists until the providers are chosen (O3). Prices are USD per million tokens and are added only
    | from the provider's own price page, with the date checked; a model without a price is refused,
    | because its cost can't be checked against the cap. The cap is per batch (a single call counts as
    | a batch of one) and is 0 until the maintainer sets AI_MAX_BATCH_USD.
    |
    */

    'ai' => [
        'prompts_path' => base_path('data/prompts'),
        'schemas_path' => base_path('data/schemas'),

        'providers' => [
            'fake' => ['driver' => FakeDriver::class],
        ],

        'pricing' => [
            'fake' => ['*' => ['input_per_million' => 0, 'output_per_million' => 0]],
        ],

        'max_batch_usd' => (float) env('AI_MAX_BATCH_USD', 0),

        // Characters per token for the cost estimate. Kept low so the estimate runs high: Bangla text
        // uses more tokens per character than English.
        'chars_per_token' => 1,

        // Waits before the second and third attempt after a retryable provider error.
        'retry' => ['backoff_ms' => [1000, 5000]],

        'batch_poll_seconds' => 60,
    ],

];
