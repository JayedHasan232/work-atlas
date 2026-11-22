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
            'tasks/*' => ['tasks', 'occupations'],
            'moderation/*' => ['moderation', 'occupations', 'talk'],
            'talk/*' => ['talk', 'occupations', 'moderation'],
            'discovery/*' => ['guide', 'occupations'],
            'settings/contributions' => ['contribute'],
            'search/*' => ['search'],
            'publishing/*' => ['api'],
            'publishing/polic*' => ['policies', 'terms'],
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
    | Micro-tasks (PLAN M17, A11b, A11c, D64–D65): questions are made from the values of these waves; a
    | value becomes community_supported after this many agreeing answers with no dissent. A contributor
    | becomes trusted after this many accepted contributions (accepted proposals plus answers that
    | agreed with a reached consensus) and this many days since signup.
    |
    | Rate limits by trust level (PLAN M18, D68): a trusted person may write more changes a day than
    | a contributor; reviewers and above have no daily limit.
    |
    */

    'community' => [
        'new_account_days' => 7,
        'daily_changes' => 50,
        'daily_changes_trusted' => 200,
        'daily_changes_new_account' => 10,
        'error_reports_per_hour' => 10,
        'suggestions_per_minute' => 6,
        'task_waves' => [1],
        'consensus_agreements' => 3,
        'answers_per_minute' => 20,
        'trusted_after_contributions' => 50,
        'trusted_after_days' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Moderation
    |--------------------------------------------------------------------------
    |
    | PLAN M18, D66–D67. The patrol queue lists people's edits of the last `patrol_days` days. An edit
    | is flagged as possibly promotional when it declares an interest, carries links, names an
    | organisation (one of `organisation_terms`, matched case-insensitively, or a phone number), or
    | repeats a link domain or an organisation name the same person added on at least `repeat_after`
    | other occupations in the last `repeat_days` days. Flags only sort the queue; a person decides.
    |
    */

    'moderation' => [
        'patrol_days' => 30,
        'repeat_days' => 30,
        'repeat_after' => 2,
        'organisation_terms' => [
            'ltd', 'limited', 'institute', 'academy', 'training center', 'training centre', 'coaching',
            'agency', 'enterprise', 'company', 'call now', 'contact us',
            'লিমিটেড', 'ইনস্টিটিউট', 'একাডেমি', 'ট্রেনিং সেন্টার', 'প্রশিক্ষণ কেন্দ্র', 'কোচিং', 'এজেন্সি',
            'এন্টারপ্রাইজ', 'কোম্পানি', 'যোগাযোগ করুন',
        ],
    ],

    /*
    | The public read-only API v1 (PLAN M21, D75). Requests per minute per IP address; the license and
    | attribution go into every response's meta.
    */
    'api' => [
        'per_minute' => (int) env('ATLAS_API_PER_MINUTE', 60),
        'license' => 'CC BY-SA 4.0',
        'license_url' => 'https://creativecommons.org/licenses/by-sa/4.0/',
        'attribution' => 'Work Atlas contributors',
        'notice' => 'Every value has a status. ai_draft values are unchecked AI drafts. Sensitive values (income, health, legal) appear only once verified. Not medical, legal or financial advice.',
    ],

    /*
    | Self-hosted Umami (PLAN M22, A12): the only analytics, off until both values are set. It never
    | sends query strings (the guide's answers live there, D73) and honours Do Not Track.
    */
    'analytics' => [
        'umami_script' => env('UMAMI_SCRIPT_URL'),
        'umami_website_id' => env('UMAMI_WEBSITE_ID'),
    ],

    /*
    | Staging (PLAN M22): with APP_ENV=staging every request except `/up` needs HTTP basic auth, and
    | every response is `noindex`. Without a user and password set, staging lets nobody in.
    */
    'staging' => [
        'basic_auth_user' => env('STAGING_BASIC_AUTH_USER'),
        'basic_auth_password' => env('STAGING_BASIC_AUTH_PASSWORD'),
    ],

    /*
    | Security (PLAN M23, D81): account signups and password-reset mails per IP address and hour.
    */
    'security' => [
        'account_forms_per_hour' => 10,
    ],

    'publishing' => [
        'page_waves' => [0, 1],
        'medical_groups' => ['health'],
        'per_page' => 50,
    ],

    /*
    |--------------------------------------------------------------------------
    | Discovery
    |--------------------------------------------------------------------------
    |
    | The guided filter and compare (PLAN M20, D72–D73). `max_matches` is how many matches the filter
    | shows; `compare_waves` are the waves whose fields the compare page puts side by side.
    |
    */

    'discovery' => [
        'max_matches' => 30,
        'compare_waves' => [1],
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
