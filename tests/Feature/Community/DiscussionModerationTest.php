<?php

use App\Models\User;
use App\Modules\Community\Enums\Role;
use App\Modules\Community\Models\DiscussionModeration;
use App\Modules\Community\Models\DiscussionPost;
use App\Modules\Community\Models\DiscussionThread;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

/*
 * PLAN M19 (D70): moderators hide talk-page posts and threads; nothing is ever deleted. Each hide or
 * show is logged, the database refuses deletes and changes to what a person wrote, and the public
 * sees neither the text nor the author of a hidden post.
 */

beforeEach(function () {
    wave1Setup();
    $this->talkUrl = '/en/occupations/'.listedOccupation()->slug.'/talk';
    $this->author = User::factory()->role(Role::Contributor)->create(['created_at' => now()->subDays(30), 'name' => 'Karim']);
    $this->moderator = User::factory()->role(Role::Reviewer)->create();

    $this->actingAs($this->author)->post($this->talkUrl, ['title' => 'Training costs', 'body' => 'Spam text here.'])->assertSessionHasNoErrors();
    $this->thread = DiscussionThread::query()->sole();
    $this->post = DiscussionPost::query()->sole();
    $this->threadUrl = $this->talkUrl.'/'.$this->thread->id;
});

function asGuest(): void
{
    Auth::logout();
}

/**
 * Runs a statement in a savepoint, so the refused statement does not abort the test's transaction.
 */
function refusedByDatabase(Closure $statement): bool
{
    try {
        DB::transaction($statement);
    } catch (QueryException) {
        return true;
    }

    return false;
}

it('lets a moderator hide a post, keeping the row and logging it', function () {
    $this->actingAs($this->moderator)
        ->from($this->threadUrl)
        ->post('/en/talk/posts/'.$this->post->id.'/hide', ['reason' => 'advertising'])
        ->assertRedirect($this->threadUrl)
        ->assertSessionHas('notice', 'talk.notices.hidden');

    $post = $this->post->fresh();
    expect($post?->hidden_at)->not->toBeNull()
        ->and($post?->hidden_by)->toBe($this->moderator->id)
        ->and($post?->body)->toBe('Spam text here.');

    $log = DiscussionModeration::query()->sole();
    expect($log->action)->toBe('hide')
        ->and($log->discussion_post_id)->toBe($this->post->id)
        ->and($log->reason)->toBe('advertising')
        ->and($log->user_id)->toBe($this->moderator->id);

    $this->get($this->threadUrl)->assertInertia(fn (Assert $page) => $page
        ->where('talk.posts.0.hidden', true)
        ->where('talk.posts.0.body', 'Spam text here.'));

    asGuest();
    $this->get($this->threadUrl)
        ->assertDontSee('Spam text here.')
        ->assertInertia(fn (Assert $page) => $page
            ->where('talk.posts.0.hidden', true)
            ->where('talk.posts.0.body', null)
            ->where('talk.posts.0.author', null)
            ->where('talk.posts.0.hideUrl', null));
});

it('shows a hidden post again and logs that too', function () {
    $this->actingAs($this->moderator)->post('/en/talk/posts/'.$this->post->id.'/hide');
    $this->actingAs($this->moderator)->post('/en/talk/posts/'.$this->post->id.'/show')->assertSessionHas('notice', 'talk.notices.shown');

    expect($this->post->fresh()?->hidden_at)->toBeNull()
        ->and(DiscussionModeration::query()->orderBy('id')->pluck('action')->all())->toBe(['hide', 'show']);
});

it('does nothing when the post is already in that state', function () {
    $this->actingAs($this->moderator)->post('/en/talk/posts/'.$this->post->id.'/show');

    expect(DiscussionModeration::query()->count())->toBe(0);
});

it('hides a whole thread from the public list', function () {
    $this->actingAs($this->moderator)->post('/en/talk/threads/'.$this->thread->id.'/hide')->assertRedirect();

    $this->get($this->talkUrl)->assertInertia(fn (Assert $page) => $page
        ->has('talk.threads', 1)
        ->where('talk.threads.0.hidden', true));

    asGuest();
    $this->get($this->talkUrl)->assertInertia(fn (Assert $page) => $page->has('talk.threads', 0));
    $this->get($this->threadUrl)->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('talk.thread.title', null)
        ->where('talk.replyUrl', null));
});

it('forbids contributors and guests from moderating', function () {
    $this->actingAs(User::factory()->role(Role::Trusted)->create())
        ->post('/en/talk/posts/'.$this->post->id.'/hide')
        ->assertForbidden();
    $this->get('/en/patrol/talk')->assertForbidden();

    asGuest();
    $this->post('/en/talk/threads/'.$this->thread->id.'/hide')->assertRedirect();

    expect($this->post->fresh()?->hidden_at)->toBeNull()
        ->and(DiscussionModeration::query()->count())->toBe(0);
});

it('never deletes posts or threads and never changes what a person wrote', function () {
    expect(refusedByDatabase(fn () => DB::table('discussion_posts')->where('id', $this->post->id)->delete()))->toBeTrue()
        ->and(refusedByDatabase(fn () => DB::table('discussion_threads')->where('id', $this->thread->id)->delete()))->toBeTrue()
        ->and(refusedByDatabase(fn () => DB::table('discussion_posts')->where('id', $this->post->id)->update(['body' => 'Edited'])))->toBeTrue()
        ->and(refusedByDatabase(fn () => DB::table('discussion_threads')->where('id', $this->thread->id)->update(['title' => 'Edited'])))->toBeTrue()
        ->and(refusedByDatabase(fn () => DB::statement('TRUNCATE discussion_posts CASCADE')))->toBeTrue();

    expect(DiscussionPost::query()->sole()->body)->toBe('Spam text here.');
});

it('keeps the moderation log append-only', function () {
    $this->actingAs($this->moderator)->post('/en/talk/posts/'.$this->post->id.'/hide');

    expect(refusedByDatabase(fn () => DB::table('discussion_moderations')->update(['reason' => 'changed'])))->toBeTrue()
        ->and(refusedByDatabase(fn () => DB::table('discussion_moderations')->delete()))->toBeTrue();
});

it('lists recent posts for moderators, flagged ones first', function () {
    $this->actingAs($this->author)->post($this->threadUrl, ['body' => 'Cheap course at https://promo.example.com'])->assertSessionHasNoErrors();

    $this->actingAs($this->moderator)->get('/en/patrol/talk')
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex')
        ->assertInertia(fn (Assert $page) => $page
            ->component('moderation/talk-queue')
            ->has('posts', 2)
            ->where('posts.0.body', 'Cheap course at https://promo.example.com')
            ->where('posts.0.flags.0.reason', 'link')
            ->where('posts.0.author.name', 'Karim')
            ->where('posts.1.flags', []));

    $this->actingAs($this->moderator)->post('/en/talk/posts/'.$this->post->id.'/hide');
    $this->get('/en/patrol/talk')->assertInertia(fn (Assert $page) => $page->has('posts', 1));
});
