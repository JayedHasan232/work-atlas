<?php

use App\Modules\AiPipeline\Models\AiRun;
use App\Modules\Taxonomy\Actions\CreateCandidateOccupation;
use App\Modules\Taxonomy\Enums\PublishState;
use App\Modules\Taxonomy\Exceptions\UnknownUnitGroup;
use App\Modules\Taxonomy\Models\IscoGroup;
use App\Modules\Taxonomy\Models\Occupation;

beforeEach(function () {
    IscoGroup::factory()->create(['code' => '7411']);
});

it('makes a candidate with an ASCII slug that is unique', function () {
    $create = app(CreateCandidateOccupation::class);

    $ids = [
        $create->handle('7411', 'Building Electrician'),
        $create->handle('7411', 'Building electrician'),
        $create->handle('7411', 'Building  electrician!'),
        $create->handle('7411', '— !!'),
        $create->handle('7411', 'Café wiring'),
    ];

    $occupations = Occupation::query()->whereIn('id', $ids)->orderBy('id')->get();

    expect($occupations->pluck('slug')->all())->toBe(['building-electrician', 'building-electrician-2', 'building-electrician-3', 'occupation', 'cafe-wiring'])
        ->and($occupations->pluck('publish_state')->unique()->all())->toBe([PublishState::Candidate]);
});

it('writes a Bangla-only name as an ASCII slug', function () {
    $id = app(CreateCandidateOccupation::class)->handle('7411', 'বিদ্যুৎ মিস্ত্রি');

    expect(Occupation::query()->find($id)->slug)->toMatch('/^[a-z0-9]+(-[a-z0-9]+)*$/');
});

it('links the candidate to the AI run that proposed it', function () {
    $run = AiRun::factory()->create();

    $id = app(CreateCandidateOccupation::class)->handle('7411', 'Electrician', $run->id);

    expect(Occupation::query()->find($id)->ai_run_id)->toBe($run->id);
});

it('refuses a code that is not an imported unit group', function (string $code) {
    app(CreateCandidateOccupation::class)->handle($code, 'Electrician');
})->with(['741', '9999'])->throws(UnknownUnitGroup::class);
