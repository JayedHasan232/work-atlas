<?php

use App\Models\User;
use App\Modules\AiPipeline\Models\AiRun;
use App\Modules\Community\Enums\Role;
use App\Modules\Knowledge\Models\FieldDefinition;
use App\Modules\Knowledge\Models\Source;
use App\Modules\Taxonomy\Models\Occupation;

/*
 * The admin panel (/admin) and Horizon (/horizon) are for maintainers only (PLAN M8).
 */

dataset('non-maintainers', [
    'contributor' => [Role::Contributor],
    'trusted' => [Role::Trusted],
    'reviewer' => [Role::Reviewer],
    'expert' => [Role::Expert],
]);

it('sends a guest to the site login', function (string $path) {
    $this->get($path)->assertRedirect(route('login'));
})->with(['/admin', '/admin/sources']);

it('refuses the admin panel to everyone but maintainers', function (Role $role) {
    $this->actingAs(User::factory()->role($role)->create())
        ->get('/admin')
        ->assertForbidden();
})->with('non-maintainers');

it('refuses Horizon to guests and everyone but maintainers', function (?Role $role) {
    if ($role !== null) {
        $this->actingAs(User::factory()->role($role)->create());
    }

    $this->get('/horizon')->assertForbidden();
})->with([
    'guest' => [null],
    'contributor' => [Role::Contributor],
    'trusted' => [Role::Trusted],
    'reviewer' => [Role::Reviewer],
    'expert' => [Role::Expert],
]);

it('opens Horizon for a maintainer', function () {
    $this->actingAs(User::factory()->role(Role::Maintainer)->create())
        ->get('/horizon')
        ->assertOk();
});

it('shows every admin list and record page to a maintainer', function () {
    $revision = recordValue(FieldDefinition::factory()->create(), 3);
    $pages = [
        '/admin',
        '/admin/occupations',
        '/admin/occupations/'.Occupation::factory()->create()->id,
        '/admin/sources',
        '/admin/sources/'.Source::factory()->create()->id,
        '/admin/revisions',
        "/admin/revisions/{$revision->id}",
        '/admin/ai-runs',
        '/admin/ai-runs/'.AiRun::factory()->create()->id,
    ];

    $this->actingAs(User::factory()->role(Role::Maintainer)->create());

    foreach ($pages as $page) {
        $this->get($page)->assertOk();
    }
});

it('shows the admin panel in the remembered language', function () {
    $this->actingAs(User::factory()->role(Role::Maintainer)->create())
        ->withSession(['locale' => 'en'])
        ->get('/admin/sources')
        ->assertOk()
        ->assertSee('Sources');

    $this->withSession(['locale' => 'bn'])
        ->get('/admin/sources')
        ->assertSee('উৎস');
});
