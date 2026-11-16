<?php

use App\Models\User;
use App\Modules\Community\Enums\Role;
use App\Modules\Community\Models\DiscussionPost;
use App\Modules\Community\Models\DiscussionThread;
use App\Modules\Taxonomy\Models\Occupation;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;

/*
 * PLAN M19: a talk page per occupation. Signed-in people with a verified email open threads, about the
 * whole page or one field, and reply. Posts count against the daily limit; blocked people cannot post.
 */

beforeEach(function () {
    wave1Setup();
    $this->occupation = listedOccupation();
    $this->talkUrl = '/en/occupations/'.$this->occupation->slug.'/talk';
    config([
        'atlas.community.new_account_days' => 7,
        'atlas.community.daily_changes' => 3,
    ]);
});

function talkPerson(array $attributes = []): User
{
    return User::factory()->role(Role::Contributor)->create(['created_at' => now()->subDays(30), ...$attributes]);
}

function startTalk(User $user, string $url, array $data = []): TestResponse
{
    return test()->actingAs($user)->post($url, [
        'title' => 'Is night work common?',
        'body' => 'Many plumbers I know also take night calls in winter.',
        ...$data,
    ]);
}

it('opens a thread with its first post and shows it on the talk page', function () {
    $user = talkPerson(['name' => 'Rahima']);

    startTalk($user, $this->talkUrl)->assertSessionHasNoErrors()->assertRedirect();

    $thread = DiscussionThread::query()->sole();
    expect($thread->title)->toBe('Is night work common?')
        ->and($thread->locale)->toBe('en')
        ->and($thread->posts_count)->toBe(1)
        ->and($thread->field_key)->toBeNull()
        ->and(DiscussionPost::query()->sole()->body)->toBe('Many plumbers I know also take night calls in winter.');

    $this->get($this->talkUrl)
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex')
        ->assertInertia(fn (Assert $page) => $page
            ->component('talk/index')
            ->has('talk.threads', 1)
            ->where('talk.threads.0.title', 'Is night work common?')
            ->where('talk.threads.0.author.name', 'Rahima')
            ->where('talk.viewer.canPost', true));
});

it('links a thread to one field and filters by it', function () {
    startTalk(talkPerson(), $this->talkUrl, ['field' => 'physical_workload'])->assertSessionHasNoErrors();
    startTalk(talkPerson(), $this->talkUrl, ['title' => 'Whole page'])->assertSessionHasNoErrors();

    expect(DiscussionThread::query()->where('field_key', 'physical_workload')->count())->toBe(1);

    $this->get($this->talkUrl.'?field=physical_workload')
        ->assertInertia(fn (Assert $page) => $page
            ->where('talk.selectedField', 'physical_workload')
            ->has('talk.threads', 1)
            ->where('talk.threads.0.field.key', 'physical_workload'));
});

it('rejects a field that does not exist', function () {
    startTalk(talkPerson(), $this->talkUrl, ['field' => 'no_such_field'])
        ->assertSessionHasErrors(['field' => __('talk.errors.field', [], 'en')]);

    expect(DiscussionThread::query()->count())->toBe(0);
});

it('adds replies and keeps the post count', function () {
    startTalk(talkPerson(), $this->talkUrl);
    $thread = DiscussionThread::query()->sole();

    $this->actingAs(talkPerson())
        ->post($this->talkUrl.'/'.$thread->id, ['body' => 'Yes, in Dhaka at least.'])
        ->assertSessionHasNoErrors()
        ->assertRedirect($this->talkUrl.'/'.$thread->id);

    expect($thread->fresh()?->posts_count)->toBe(2);

    $this->get($this->talkUrl.'/'.$thread->id)
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('talk/thread')
            ->has('talk.posts', 2)
            ->where('talk.posts.1.body', 'Yes, in Dhaka at least.')
            ->where('talk.posts.1.flags', []));
});

it('sends guests to sign in and asks for a verified email', function () {
    $this->post($this->talkUrl, ['title' => 'x', 'body' => 'y'])->assertRedirect();
    expect(DiscussionThread::query()->count())->toBe(0);

    startTalk(talkPerson(['email_verified_at' => null]), $this->talkUrl)->assertRedirect();
    expect(DiscussionThread::query()->count())->toBe(0);
});

it('counts posts against the daily limit', function () {
    $user = talkPerson();
    startTalk($user, $this->talkUrl)->assertSessionHasNoErrors();
    $thread = DiscussionThread::query()->sole();

    foreach (range(1, 2) as $ignored) {
        $this->actingAs($user)->post($this->talkUrl.'/'.$thread->id, ['body' => 'More.'])->assertSessionHasNoErrors();
    }

    $this->actingAs($user)->post($this->talkUrl.'/'.$thread->id, ['body' => 'One too many.'])
        ->assertSessionHasErrors(['body' => __('talk.errors.limit', [], 'en')]);

    expect(DiscussionPost::query()->count())->toBe(3);
});

it('does not let a blocked person post', function () {
    startTalk(talkPerson(['blocked_at' => now()]), $this->talkUrl)
        ->assertSessionHasErrors(['body' => __('talk.errors.blocked', [], 'en')]);

    expect(DiscussionThread::query()->count())->toBe(0);
});

it('refuses replies to a hidden thread', function () {
    startTalk(talkPerson(), $this->talkUrl);
    $thread = DiscussionThread::query()->sole();
    $thread->update(['hidden_at' => now(), 'hidden_by' => User::factory()->role(Role::Reviewer)->create()->id]);

    $this->actingAs(talkPerson())->post($this->talkUrl.'/'.$thread->id, ['body' => 'Hello?'])
        ->assertSessionHasErrors(['body' => __('talk.errors.hidden', [], 'en')]);

    $this->get($this->talkUrl)->assertInertia(fn (Assert $page) => $page->has('talk.threads', 0));
});

it('keeps anonymous contributors anonymous', function () {
    startTalk(talkPerson(['name' => 'Hidden Name', 'anonymous_credit' => true]), $this->talkUrl);
    $thread = DiscussionThread::query()->sole();

    $this->get($this->talkUrl.'/'.$thread->id)
        ->assertInertia(fn (Assert $page) => $page->where('talk.posts.0.author', null));
});

it('flags links and organisation names in posts for moderators', function () {
    startTalk(talkPerson(), $this->talkUrl, ['body' => 'Join the course at Dhaka Skill Institute, see https://example.com/course']);

    $flags = collect(DiscussionPost::query()->sole()->flags)->pluck('reason')->all();
    expect($flags)->toContain('link')->toContain('organisation');
});

it('answers 404 for an unknown thread or one from another occupation', function () {
    $this->get($this->talkUrl.'/999')->assertNotFound();

    $other = listedOccupation([
        'bn' => [['name' => 'রাজমিস্ত্রি', 'type' => 'primary']],
        'en' => [['name' => 'Mason', 'type' => 'primary']],
    ]);
    startTalk(talkPerson(), '/en/occupations/'.$other->slug.'/talk');
    $thread = DiscussionThread::query()->sole();

    $this->get($this->talkUrl.'/'.$thread->id)->assertNotFound();
    $this->actingAs(talkPerson())->post($this->talkUrl.'/'.$thread->id, ['body' => 'x'])->assertNotFound();
});

it('links the occupation page and its fields to the talk page', function () {
    startTalk(talkPerson(), $this->talkUrl);

    $this->get('/en/occupations/'.$this->occupation->slug)
        ->assertInertia(fn (Assert $page) => $page
            ->where('occupation.talkUrl', $this->talkUrl)
            ->where('occupation.talkCount', 1));
});

it('keeps an unlisted occupation out of the talk pages', function () {
    $draft = Occupation::factory()->create();

    $this->get('/en/occupations/'.$draft->slug.'/talk')->assertNotFound();
});
