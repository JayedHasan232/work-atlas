<?php

use App\Models\User;
use App\Modules\AiPipeline\Actions\GenerateWave1;
use App\Modules\Community\Enums\Role;
use App\Modules\Knowledge\Filament\Resources\CrossChecks\Pages\ListCrossChecks;
use App\Modules\Knowledge\Models\FieldValue;
use App\Modules\Knowledge\Queries\DisagreementReport;
use Livewire\Livewire;

/*
 * PLAN M12: the disagreement report in /admin lists the values two models disagree on, and exports
 * them as CSV.
 */

beforeEach(function () {
    $fake = wave1Setup();
    $this->occupation = listedOccupation();
    $fake->respondWithJson(wave1Output(['physical_workload' => 1]))->respondWithJson(wave1Output(['physical_workload' => 5]));
    app(GenerateWave1::class)->handle($this->occupation->id);
    $this->actingAs(User::factory()->role(Role::Maintainer)->create());
});

it('lists only disagreements by default', function () {
    $disagreed = FieldValue::query()->where('cross_check', 'disagreed')->get();
    $agreed = FieldValue::query()->where('cross_check', 'agreed')->first();

    Livewire::test(ListCrossChecks::class)
        ->assertCanSeeTableRecords($disagreed)
        ->assertCanNotSeeTableRecords([$agreed]);
});

it('exports the disagreements as CSV rows', function () {
    $rows = app(DisagreementReport::class)->handle();

    expect($rows[0])->toBe(DisagreementReport::HEADER)
        ->and($rows)->toHaveCount(2)
        ->and($rows[1])->toBe([(string) $this->occupation->id, $this->occupation->slug, 'physical_workload', 'ai_draft', '20', '1', '5']);

    Livewire::test(ListCrossChecks::class)->callAction('exportCsv')->assertFileDownloaded();
});
