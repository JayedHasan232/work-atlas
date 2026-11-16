<?php

use App\Models\User;
use App\Modules\Community\Actions\HandleErrorReport;
use App\Modules\Community\Enums\ErrorReportState;
use App\Modules\Community\Enums\Role;
use App\Modules\Community\Exceptions\ContributionRejected;
use App\Modules\Community\Models\ErrorReport;
use App\Modules\Knowledge\Models\FieldDefinition;
use App\Modules\Taxonomy\Enums\PublishState;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;

/*
 * PLAN M16: anyone can report a mistake on an occupation page, about the whole page or one field,
 * without an account. Spam checks: a honeypot field and a rate limit per IP address. No IP address
 * is stored. Reports wait in the admin queue until a reviewer or maintainer handles them.
 */

beforeEach(function () {
    wave1Setup();
    $this->occupation = listedOccupation();
    $this->url = "/occupations/{$this->occupation->slug}/report";
});

it('shows the report form to an anonymous visitor, with the fields of the page', function () {
    $this->get("/en/occupations/{$this->occupation->slug}/report?field=physical_workload")
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex')
        ->assertInertia(fn (Assert $page) => $page
            ->component('occupations/report')
            ->where('form.field', 'physical_workload')
            ->where('form.submitUrl', "/en/occupations/{$this->occupation->slug}/report")
            ->where('form.fields', fn ($fields) => collect($fields)->pluck('key')->contains('physical_workload')
                && ! collect($fields)->pluck('key')->contains('names')));
});

it('stores an anonymous report about one field, without any IP address', function () {
    $this->post($this->url, ['message' => 'The workload is too high.', 'field' => 'physical_workload'])
        ->assertRedirect("/occupations/{$this->occupation->slug}")
        ->assertSessionHas('notice', 'contribute.report.thanks');

    $report = ErrorReport::query()->sole();

    expect($report)
        ->occupation_id->toBe($this->occupation->id)
        ->field_definition_id->toBe(FieldDefinition::query()->where('key', 'physical_workload')->value('id'))
        ->locale->toBe('bn')
        ->message->toBe('The workload is too high.')
        ->user_id->toBeNull()
        ->state->toBe(ErrorReportState::Open)
        ->and(Schema::getColumnListing('error_reports'))->not->toContain('ip', 'ip_address', 'user_agent')
        ->and(json_encode($report->getAttributes()))->not->toContain('127.0.0.1');
});

it('keeps the reporter when they are signed in, and the language they used', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post("/en/occupations/{$this->occupation->slug}/report", ['message' => 'Wrong group.'])
        ->assertRedirect("/en/occupations/{$this->occupation->slug}");

    expect(ErrorReport::query()->sole())
        ->user_id->toBe($user->id)
        ->locale->toBe('en')
        ->field_definition_id->toBeNull();
});

it('thanks a bot that fills the honeypot, and stores nothing', function () {
    $this->post($this->url, ['message' => 'Buy cheap things', 'website' => 'https://spam.example.test'])
        ->assertRedirect("/occupations/{$this->occupation->slug}")
        ->assertSessionHas('notice', 'contribute.report.thanks');

    expect(ErrorReport::query()->count())->toBe(0);
});

it('limits reports per IP address', function () {
    config(['atlas.community.error_reports_per_hour' => 2]);

    $this->post($this->url, ['message' => 'One'])->assertRedirect();
    $this->post($this->url, ['message' => 'Two'])->assertRedirect();
    $this->post($this->url, ['message' => 'Three'])->assertTooManyRequests();

    expect(ErrorReport::query()->count())->toBe(2);
});

it('needs a message, and a field that exists', function (array $input, string $error) {
    $this->post($this->url, $input)->assertSessionHasErrors($error);

    expect(ErrorReport::query()->count())->toBe(0);
})->with([
    'no message' => [['message' => ''], 'message'],
    'too long' => [['message' => str_repeat('a', 2001)], 'message'],
    'unknown field' => [['message' => 'Wrong', 'field' => 'no_such_field'], 'field'],
]);

it('has no report form for an occupation without a public page', function () {
    $this->occupation->update(['publish_state' => PublishState::Candidate]);

    $this->get($this->url)->assertNotFound();
    $this->post($this->url, ['message' => 'Wrong'])->assertNotFound();
});

it('lets a reviewer close a report, not a contributor', function () {
    $report = ErrorReport::factory()->create(['occupation_id' => $this->occupation->id]);
    $reviewer = User::factory()->role(Role::Reviewer)->create();
    $contributor = User::factory()->create();

    expect(fn () => app(HandleErrorReport::class)->handle($report->id, $contributor->id, ErrorReportState::Resolved))
        ->toThrow(ContributionRejected::class);

    app(HandleErrorReport::class)->handle($report->id, $reviewer->id, ErrorReportState::Spam);

    expect($report->fresh())
        ->state->toBe(ErrorReportState::Spam)
        ->handled_by->toBe($reviewer->id)
        ->handled_at->not->toBeNull();
});

it('shows open reports in the admin queue to a maintainer', function () {
    ErrorReport::factory()->create(['occupation_id' => $this->occupation->id, 'message' => 'Queue me']);

    $this->actingAs(User::factory()->role(Role::Maintainer)->create())
        ->get('/admin/error-reports')
        ->assertOk()
        ->assertSee('Queue me');
});

it('shows one report to a maintainer, and not the admin queue to a reviewer', function () {
    $report = ErrorReport::factory()->create(['occupation_id' => $this->occupation->id, 'message' => 'Look at me']);

    $this->actingAs(User::factory()->role(Role::Maintainer)->create())
        ->get("/admin/error-reports/{$report->id}")
        ->assertOk()
        ->assertSee('Look at me');

    $this->actingAs(User::factory()->role(Role::Reviewer)->create())
        ->get('/admin/error-reports')
        ->assertForbidden();
});

it('links the report form from the occupation page', function () {
    $this->get("/en/occupations/{$this->occupation->slug}")
        ->assertInertia(fn (Assert $page) => $page
            ->where('occupation.reportUrl', "/en/occupations/{$this->occupation->slug}/report"));
});
