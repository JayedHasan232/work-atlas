<?php

use App\Modules\Taxonomy\Enums\PublishState;
use App\Modules\Taxonomy\Models\IscoGroup;
use Illuminate\Support\Facades\Config;
use Inertia\Testing\AssertableInertia as Assert;

/*
 * PLAN M13: the occupation index and browsing by ISCO-08 group. Only listed and published
 * occupations appear; candidates do not.
 */

beforeEach(function () {
    wave1Setup();
    IscoGroup::factory()->create(['code' => '2', 'title_en' => 'Professionals', 'title_bn' => 'পেশাজীবী']);
    $this->plumber = listedOccupation();
    $this->published = listedOccupation([
        'bn' => [['name' => 'গ্যাস মিস্ত্রি', 'type' => 'primary']],
        'en' => [['name' => 'Gas fitter', 'type' => 'primary']],
    ]);
    $this->published->update(['publish_state' => PublishState::Published]);
    occupationNamed(['en' => [['name' => 'Pipe layer', 'type' => 'primary']]]);
});

it('lists the major groups with their public occupations, and every public occupation', function () {
    $this->get('/occupations')
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex')
        ->assertInertia(fn (Assert $page) => $page
            ->component('occupations/index')
            ->has('groups', 2)
            ->where('groups.0', fn ($group) => $group['code'] === '2' && $group['occupations'] === 0 && $group['title'] === 'পেশাজীবী')
            ->where('groups.1.code', '7')
            ->where('groups.1.occupations', 2)
            ->where('occupations', fn ($items) => collect($items)->pluck('name')->sort()->values()->all() === ['গ্যাস মিস্ত্রি', 'প্লাম্বার'])
            ->where('occupations.0.url', fn (string $url) => str_starts_with($url, '/occupations/'))
            ->where('pagination.total', 2));
});

it('shows English names and English links under /en', function () {
    $this->get('/en/occupations')->assertInertia(fn (Assert $page) => $page
        ->where('occupations.0.name', 'Gas fitter')
        ->where('occupations.1.name', 'Plumber')
        ->where('occupations.0.url', fn (string $url) => str_starts_with($url, '/en/occupations/'))
        ->where('groups.0.title', 'Professionals'));
});

it('pages the index', function () {
    Config::set('atlas.publishing.per_page', 1);

    $this->get('/en/occupations?page=2')->assertInertia(fn (Assert $page) => $page
        ->has('occupations', 1)
        ->where('occupations.0.name', 'Plumber')
        ->where('pagination.page', 2)
        ->where('pagination.lastPage', 2)
        ->where('pagination.previous', '/en/occupations')
        ->where('pagination.next', null));
});

it('shows the child groups of a group, with counts', function () {
    $this->get('/occupations/groups/71')->assertInertia(fn (Assert $page) => $page
        ->component('occupations/group')
        ->where('group.code', '71')
        ->where('path', fn ($path) => collect($path)->pluck('code')->all() === ['7', '71'])
        ->has('subgroups', 1)
        ->where('subgroups.0.code', '712')
        ->where('subgroups.0.occupations', 2)
        ->where('occupations', []));
});

it('lists the public occupations of a unit group', function () {
    $this->get('/en/occupations/groups/7126')->assertInertia(fn (Assert $page) => $page
        ->where('group.level', 4)
        ->where('subgroups', [])
        ->where('occupations', fn ($items) => collect($items)->pluck('name')->all() === ['Gas fitter', 'Plumber']));
});

it('has no page for an unknown group', function () {
    $this->get('/occupations/groups/9999')->assertNotFound();
    $this->get('/occupations/groups/12345')->assertNotFound();
});
