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
            'occupations/*' => ['occupations', 'contribute'],
            'contributors/*' => ['contribute'],
            'settings/contributions' => ['contribute'],
            'search/*' => ['search'],
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
    | Occupation list review
    |--------------------------------------------------------------------------
    |
    | Two occupations in the same ISCO-08 minor group are flagged as a possible duplicate when a name
    | in the same language is the same after normalization, or this similar by trigram (pg_trgm, 0–1)
    | (PLAN M11, D47). A person decides every pair.
    |
    */

    'taxonomy' => [
        'duplicate_threshold' => 0.5,
    ],

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

        // Provider and model per task. Fake until the providers are chosen (O3).
        'tasks' => [
            'taxonomy_expand' => [
                'provider' => env('AI_EXPAND_PROVIDER', 'fake'),
                'model' => env('AI_EXPAND_MODEL', 'fake-model'),
            ],
            // Wave 1 runs the same prompt on two different models (PLAN M12, D50). Model A's value
            // becomes the draft; model B's is the cross-check.
            'wave1' => [
                'a' => [
                    'provider' => env('AI_WAVE1_A_PROVIDER', 'fake'),
                    'model' => env('AI_WAVE1_A_MODEL', 'fake-model-a'),
                ],
                'b' => [
                    'provider' => env('AI_WAVE1_B_PROVIDER', 'fake'),
                    'model' => env('AI_WAVE1_B_MODEL', 'fake-model-b'),
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Public pages
    |--------------------------------------------------------------------------
    |
    | What the public occupation page shows (PLAN M13): the field waves, the field groups that carry
    | the "not medical advice" note (rule 7), and the size of an index page. Whether a page may be
    | indexed is the index gate's decision (below).
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Search
    |--------------------------------------------------------------------------
    |
    | Occupation search (PLAN M15, D59). A name this similar to the query by trigram (0–1) counts as a
    | misspelling of it. To be tuned with the search benchmark; results per page.
    |
    */

    'search' => [
        'min_similarity' => 0.4,
        'per_page' => 20,
    ],

    /*
    |--------------------------------------------------------------------------
    | Community limits
    |--------------------------------------------------------------------------
    |
    | Spam limits (PLAN M16, A15, D62). An account is new for its first days; a new account may write
    | fewer changes a day, and its links wait for a review. Reviewers and above have no daily limit.
    | Error reports are limited per IP address (kept only in the cache), suggestions per person.
    | Starting values, to be tuned with real use.
    |
    */

    'community' => [
        'new_account_days' => 7,
        'daily_changes' => 50,
        'daily_changes_new_account' => 10,
        'error_reports_per_hour' => 10,
        'suggestions_per_minute' => 6,
    ],

    'publishing' => [
        'page_waves' => [0, 1],
        'medical_groups' => ['health'],
        'per_page' => 50,
    ],

    /*
    |--------------------------------------------------------------------------
    | Index gate
    |--------------------------------------------------------------------------
    |
    | A11a: during the Phase 1 beta every page is noindex and the sitemap lists nothing (`enabled`
    | false). From Phase 2 an occupation page is indexed when at least `min_share` of the fields of
    | `waves` have reached `min_status`, and every field in `required` has reached its status (the
    | description reviewed). A field without a value counts as not reached. PLAN M14, D55.
    |
    */

    'index_gate' => [
        'enabled' => (bool) env('ATLAS_INDEX_GATE', false),
        'waves' => [1, 2],
        'min_share' => 0.8,
        'min_status' => 'community_supported',
        'required' => ['description' => 'verified'],
    ],

];
