<?php

use App\Models\User;
use App\Modules\AiPipeline\Models\AiRun;
use App\Modules\Community\Enums\Role;
use App\Modules\Community\Models\ErrorReport;
use App\Modules\Knowledge\Actions\RecordRevision;
use App\Modules\Knowledge\Data\Actor;
use App\Modules\Knowledge\Data\RevisionInput;
use App\Modules\Knowledge\Models\FieldDefinition;

/*
 * PLAN M21, rule 8: the API has no personal data — no names, emails or ids of people, no revision
 * authors or comments, no error reports — and no raw AI output.
 */

beforeEach(function () {
    wave1Setup();
    $this->occupation = listedOccupation();
    $this->person = User::factory()->role(Role::Reviewer)->create([
        'name' => 'Rahima Private-Name',
        'email' => 'private.person@example.test',
    ]);
    app(RecordRevision::class)->handle(new RevisionInput(
        occupationId: $this->occupation->id,
        fieldDefinitionId: FieldDefinition::query()->where('key', 'work_time')->firstOrFail()->id,
        value: ['day', 'night'],
        actor: Actor::user($this->person),
        comment: 'PRIVATE-REVISION-COMMENT',
        declaredInterest: 'PRIVATE-INTEREST',
    ));
    $run = AiRun::factory()->create(['raw_output' => '{"secret":"RAW-AI-OUTPUT"}', 'parsed_output' => ['note' => 'PARSED-AI-OUTPUT']]);
    recordValue(FieldDefinition::query()->where('key', 'seasonal')->firstOrFail(), 'year_round', Actor::aiRun($run->id), occupationId: $this->occupation->id);
    ErrorReport::factory()->create(['occupation_id' => $this->occupation->id, 'message' => 'PRIVATE-ERROR-REPORT', 'user_id' => $this->person->id]);
});

it('leaves people and AI runs out of every response', function (string $url) {
    $response = $this->get(str_replace('{slug}', $this->occupation->slug, $url))->assertOk();

    foreach (['Rahima', 'private.person@example.test', 'PRIVATE-REVISION-COMMENT', 'PRIVATE-INTEREST', 'RAW-AI-OUTPUT', 'PARSED-AI-OUTPUT', 'PRIVATE-ERROR-REPORT', 'user_id', 'ai_run', 'author', 'email', 'comment', 'confidence'] as $private) {
        $response->assertDontSee($private, false);
    }
})->with([
    '/api/v1/occupations',
    '/api/v1/occupations/{slug}',
    '/api/v1/fields',
    '/api/v1/search?q=plumber&locale=en',
    '/api/v1/openapi.json',
]);

it('still shows the values themselves', function () {
    $fields = collect($this->getJson("/api/v1/occupations/{$this->occupation->slug}")->assertOk()->json('data.fields'));

    expect($fields->firstWhere('key', 'work_time')['values'][0]['value'])->toBe(['day', 'night'])
        ->and($fields->firstWhere('key', 'seasonal')['values'][0]['value'])->toBe('year_round');
});

it('sets no cookies', function () {
    $response = $this->get('/api/v1/occupations')->assertOk();

    expect($response->headers->getCookies())->toBe([]);
});
