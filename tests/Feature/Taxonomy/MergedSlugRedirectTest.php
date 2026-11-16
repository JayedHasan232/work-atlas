<?php

use App\Models\User;
use App\Modules\Community\Enums\Role;
use App\Modules\Knowledge\Models\FieldDefinition;
use App\Modules\Taxonomy\Actions\MergeOccupations;
use App\Modules\Taxonomy\Enums\PublishState;
use App\Modules\Taxonomy\Models\IscoGroup;

/*
 * D5: a merged occupation's old slug redirects (301) to the occupation it was merged into, in the
 * same language. Any other slug either has its own page (M13) or is not found.
 */

beforeEach(function () {
    FieldDefinition::factory()->names()->create();
    IscoGroup::factory()->create(['code' => '7126']);
    $this->into = occupationNamed(['en' => [['name' => 'Plumber', 'type' => 'primary']]], ['slug' => 'plumber', 'publish_state' => PublishState::Listed]);
    $this->from = occupationNamed(['en' => [['name' => 'Pipe fitter', 'type' => 'primary']]], ['slug' => 'pipe-fitter']);
    app(MergeOccupations::class)->handle($this->from->id, $this->into->id, User::factory()->role(Role::Reviewer)->create());
});

it('redirects a merged slug permanently, in Bangla and in English', function () {
    $this->get('/occupations/pipe-fitter')->assertStatus(301)->assertRedirect('/occupations/plumber');
    $this->get('/en/occupations/pipe-fitter')->assertStatus(301)->assertRedirect('/en/occupations/plumber');
});

it('does not redirect an unknown slug or an occupation that was not merged', function () {
    $this->get('/occupations/nobody')->assertNotFound();
    $this->get('/occupations/plumber')->assertOk();
    $this->get('/occupations/Not_A_Slug')->assertNotFound();
});
