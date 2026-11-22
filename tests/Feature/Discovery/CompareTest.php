<?php

use App\Models\User;
use App\Modules\Community\Enums\Role;
use App\Modules\Knowledge\Models\FieldDefinition;
use App\Modules\Knowledge\Models\Source;
use App\Modules\Taxonomy\Actions\MergeOccupations;
use App\Modules\Taxonomy\Models\Occupation;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

/*
 * PLAN M20: two or three occupations side by side, each value with its status. The sensitive-field
 * gate holds here as on the occupation page.
 */

beforeEach(function () {
    wave1Setup();
    $this->plumber = listedOccupation();
    $this->tailor = listedOccupation([
        'bn' => [['name' => 'দর্জি', 'type' => 'primary']],
        'en' => [['name' => 'Tailor', 'type' => 'primary']],
    ]);
    $this->driver = listedOccupation([
        'bn' => [['name' => 'গাড়িচালক', 'type' => 'primary']],
        'en' => [['name' => 'Driver', 'type' => 'primary']],
    ]);
    $this->cook = listedOccupation([
        'bn' => [['name' => 'রাঁধুনি', 'type' => 'primary']],
        'en' => [['name' => 'Cook', 'type' => 'primary']],
    ]);
    $workload = FieldDefinition::query()->where('key', 'physical_workload')->firstOrFail();
    recordValue($workload, 4, occupationId: $this->plumber->id);
    recordValue($workload, 2, occupationId: $this->tailor->id);
});

/**
 * @param  list<string>  $slugs
 */
function compareUrl(array $slugs, string $extra = ''): string
{
    return '/en/compare?'.http_build_query(['o' => $slugs]).$extra;
}

/**
 * @return array<string, mixed>
 */
function compareProps(Assert $page): array
{
    return $page->toArray()['props']['compare'];
}

it('puts two occupations side by side with each value\'s status', function () {
    $this->get(compareUrl([$this->plumber->slug, $this->tailor->slug]))->assertOk()->assertInertia(function (Assert $page) {
        $compare = compareProps($page);
        $row = collect($compare['rows'])->firstWhere('key', 'physical_workload');

        expect(array_column($compare['occupations'], 'name'))->toBe(['Plumber', 'Tailor'])
            ->and($row['cells'][0])->toMatchArray(['status' => 'ai_draft', 'withheld' => false])
            ->and($row['cells'][1])->toMatchArray(['status' => 'ai_draft', 'withheld' => false])
            ->and($row['cells'][0]['value'])->not->toBe($row['cells'][1]['value'])
            ->and($compare['occupations'][0]['removeUrl'])->toBe(compareUrl([$this->tailor->slug]));
    });
});

it('shows three at most and offers no search once full', function () {
    $slugs = [$this->plumber->slug, $this->tailor->slug, $this->driver->slug, $this->cook->slug];

    $this->get(compareUrl($slugs, '&q=Cook'))->assertOk()->assertInertia(function (Assert $page) {
        $compare = compareProps($page);

        expect($compare['occupations'])->toHaveCount(3)
            ->and($compare['max'])->toBe(3)
            ->and($compare['hits'])->toBe([]);
    });
});

it('leaves out unknown, unlisted and repeated slugs', function () {
    $draft = Occupation::factory()->create();

    $this->get(compareUrl(['no-such-job', $draft->slug, $this->plumber->slug, $this->plumber->slug]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => expect(array_column(compareProps($page)['occupations'], 'slug'))->toBe([$this->plumber->slug]));
});

it('follows a merged occupation\'s old slug', function () {
    $oldSlug = $this->cook->slug;
    app(MergeOccupations::class)->handle($this->cook->id, $this->tailor->id, User::factory()->role(Role::Reviewer)->create());

    $this->get(compareUrl([$oldSlug, $this->plumber->slug]))->assertOk()->assertInertia(fn (Assert $page) => expect(array_column(compareProps($page)['occupations'], 'slug'))
        ->toBe([$this->tailor->slug, $this->plumber->slug]));
});

it('finds occupations to add, leaving out the chosen ones', function () {
    $this->get(compareUrl([$this->plumber->slug], '&q=নলমিস্ত্রি'))->assertOk()->assertInertia(fn (Assert $page) => expect(compareProps($page)['hits'])->toBe([]));

    $this->get(compareUrl([$this->plumber->slug], '&q=Tailor'))->assertOk()->assertInertia(function (Assert $page) {
        $hits = compareProps($page)['hits'];

        expect($hits)->toHaveCount(1)
            ->and($hits[0]['name'])->toBe('Tailor')
            ->and($hits[0]['addUrl'])->toBe(compareUrl([$this->plumber->slug, $this->tailor->slug]));
    });
});

it('withholds a sensitive value that is not verified', function () {
    $definition = FieldDefinition::factory()->sensitive()->create(['key' => 'risk_level', 'wave' => 1, 'label' => ['bn' => 'ঝুঁকি', 'en' => 'Risk']]);
    recordValue($definition, 5, sourceIds: [Source::factory()->create()->id], occupationId: $this->plumber->id);

    $response = $this->get(compareUrl([$this->plumber->slug, $this->tailor->slug]));

    $response->assertOk()->assertInertia(function (Assert $page) {
        $row = collect(compareProps($page)['rows'])->firstWhere('key', 'risk_level');

        expect($row['cells'][0])->toBe(['value' => null, 'status' => 'sourced', 'withheld' => true])
            ->and($row['cells'][1])->toBeNull();
    });
});

it('starts empty and works in Bangla', function () {
    $this->get('/compare')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('discovery/compare')
        ->where('compare.occupations', [])
        ->where('compare.chosen', []));

    $this->get('/compare?o='.$this->tailor->slug)->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('compare.occupations.0.name', 'দর্জি')
        ->where('compare.rows.0.label', fn (string $label): bool => preg_match('/\p{Bengali}/u', $label) === 1));
});

it('links to compare from the occupation page', function () {
    $this->get("/en/occupations/{$this->plumber->slug}")->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('occupation.compareUrl', compareUrl([$this->plumber->slug])));
});

it('ignores slugs that are not slugs and looks up only a short list', function () {
    $junk = implode('', array_map(fn (int $i): string => "&o[]=nobody-{$i}", range(1, 200)));

    DB::enableQueryLog();
    $this->get("/compare?o[]={$this->plumber->slug}&o[]=Not_A_Slug&o[]=%FF&o[][x]=y{$junk}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('compare.chosen', [$this->plumber->slug]));

    // Without the limit, each of the 200 unknown slugs would cost a query.
    expect(count(DB::getQueryLog()))->toBeLessThan(60);
});
