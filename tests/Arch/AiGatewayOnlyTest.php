<?php

use App\Modules\AiPipeline\AiGateway;
use App\Modules\AiPipeline\Models\AiRun;

/*
 * One AI path (CLAUDE.md): every model call goes through AiPipeline\AiGateway, which logs it in
 * ai_runs (Feature/AiPipeline/AiRunLoggedTest) and checks the budget first (BudgetCapTest).
 *
 * - Only the provider drivers may make HTTP calls or use a provider SDK. No real driver exists yet
 *   (O3); a new one lives in App\Modules\AiPipeline\Drivers.
 * - Only the gateway talks to the drivers, so no code can call a model without a run being logged.
 * - Only the gateway writes ai_runs. Admin resources and models read them; factories build fixtures.
 */

arch('only the AI drivers make HTTP calls or use a provider SDK')
    ->expect('App')
    ->not->toUse([
        'Illuminate\Support\Facades\Http',
        'Illuminate\Http\Client',
        'GuzzleHttp',
        'Psr\Http\Client',
        'Anthropic',
        'OpenAI',
        'Gemini',
        'Google\Cloud\AIPlatform',
        'Aws\BedrockRuntime',
        'Prism',
    ])
    ->ignoring('App\Modules\AiPipeline\Drivers');

arch('only the gateway uses the AI drivers')
    ->expect('App\Modules\AiPipeline\Drivers')
    ->toOnlyBeUsedIn([AiGateway::class, 'App\Modules\AiPipeline\Drivers']);

arch('only the gateway, the AI models and the admin resources use AiRun')
    ->expect(AiRun::class)
    ->toOnlyBeUsedIn([
        AiGateway::class,
        'App\Modules\AiPipeline\Models',
        'App\Modules\AiPipeline\Filament',
        'Database\Factories\AiPipeline',
        'Database\Factories\Knowledge\RevisionFactory',
    ]);

arch('AI jobs and the gateway are final')
    ->expect([AiGateway::class, 'App\Modules\AiPipeline\Jobs', 'App\Modules\AiPipeline\Drivers\FakeDriver'])
    ->toBeFinal();
