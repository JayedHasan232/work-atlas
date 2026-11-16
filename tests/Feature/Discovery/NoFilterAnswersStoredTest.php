<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;

/*
 * PLAN M20, rule 8, D73: the guided filter's answers live only in the address bar. Nothing about them
 * is written to the database or the session, and answered guides are never indexed.
 */

beforeEach(function () {
    wave1Setup();
    $this->plumber = listedOccupation();
    $this->tailor = listedOccupation([
        'bn' => [['name' => 'দর্জি', 'type' => 'primary']],
        'en' => [['name' => 'Tailor', 'type' => 'primary']],
    ]);
});

/**
 * Row counts of every table in the public schema.
 *
 * @return array<string, int>
 */
function tableCounts(): array
{
    $counts = [];

    foreach (DB::select("select tablename from pg_tables where schemaname = 'public' order by tablename") as $table) {
        $counts[$table->tablename] = (int) DB::table($table->tablename)->count();
    }

    return $counts;
}

it('stores nothing about the answers', function () {
    $user = User::factory()->create();
    $before = tableCounts();

    $this->get('/en/guide?hours=part_time&when=night&education=primary&area=rural')->assertOk();
    $response = $this->actingAs($user)->get('/guide?hours=part_time&when=night&education=primary&area=rural')->assertOk();

    expect(tableCounts())->toBe($before)
        ->and(json_encode(session()->all()))->not->toContain('part_time')
        ->and(json_encode(session()->all()))->not->toContain('rural');
    $response->assertHeader('X-Robots-Tag', 'noindex');
});

it('keeps the guide noindex even when the index gate is on', function (string $url) {
    config(['atlas.index_gate.enabled' => true]);

    $this->get($url)->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex')
        ->assertSee('<meta name="robots" content="noindex">', false);
})->with(['/guide', '/en/guide?hours=part_time', '/compare', '/en/compare?o[]=x']);

it('keeps answered guides and compare pages out of robots.txt crawling', function () {
    $this->get('/robots.txt')->assertOk()
        ->assertSee('Disallow: /guide?', false)
        ->assertSee('Disallow: /en/guide?', false)
        ->assertSee('Disallow: /compare', false);
});
