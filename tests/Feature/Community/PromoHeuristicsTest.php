<?php

use App\Models\User;
use App\Modules\Community\Actions\FlagPromotionalEdit;
use App\Modules\Community\Models\RevisionFlag;
use App\Modules\Knowledge\Models\FieldDefinition;
use App\Modules\Knowledge\Models\Revision;

/*
 * PLAN M18 (D67): rule-based flags for edits that may be promotional, such as a training centre adding
 * itself to occupation pages. A flag explains itself and only sorts the patrol queue; it never blocks
 * or changes the edit.
 */

beforeEach(function () {
    wave1Setup();
    config(['atlas.moderation.repeat_after' => 2]);
    $this->person = User::factory()->create(['created_at' => now()->subDays(30)]);
});

/**
 * One suggestion on a new listed occupation; returns its flags as reason => detail.
 *
 * @return array<string, string|null>
 */
function promoFlags(User $user, array $input = []): array
{
    $slug = listedOccupation()->slug;
    test()->actingAs($user)
        ->post("/en/occupations/{$slug}/suggest/physical_workload", array_replace(['value' => 4, 'interest' => 'none'], $input))
        ->assertSessionHasNoErrors();
    $revision = Revision::query()->where('user_id', $user->id)->latest('id')->firstOrFail();

    return RevisionFlag::query()->where('revision_id', $revision->id)->orderBy('reason')->pluck('detail', 'reason')->all();
}

it('does not flag a plain edit', function () {
    expect(promoFlags($this->person, ['comment' => 'From my own work as a plumber in Khulna.']))->toBe([]);
});

it('flags a declared interest', function () {
    expect(promoFlags($this->person, ['interest' => 'declared', 'interest_note' => 'I teach this work.']))
        ->toBe(['interest' => 'I teach this work.']);
});

it('flags links in sources and in the text, by domain', function () {
    expect(promoFlags($this->person, [
        'sources' => [['url' => 'https://www.Example.test/page', 'accessed_on' => '2026-11-01']],
        'comment' => 'More at www.other.test/info',
    ]))->toBe(['link' => 'example.test, other.test']);
});

it('flags an organisation name or a phone number', function (string $comment, string $detail) {
    expect(promoFlags($this->person, ['comment' => $comment]))->toBe(['organisation' => $detail]);
})->with([
    'English name' => ['Learn it at Dhaka Skill Institute.', 'at dhaka skill institute'],
    'Bangla name' => ['রূপসা ট্রেনিং সেন্টার এ শিখুন', 'রূপসা ট্রেনিং সেন্টার'],
    'phone number' => ['Call 01712-345678 for a seat', '01712345678'],
    'phone number in Bangla digits' => ['ফোন করুন ০১৭১২৩৪৫৬৭৮', '01712345678'],
]);

it('flags a domain the same person keeps adding on other occupations', function () {
    $link = ['sources' => [['url' => 'https://course.example.test/a', 'accessed_on' => '2026-11-01']]];

    expect(promoFlags($this->person, $link))->not->toHaveKey('repeated_domain')
        ->and(promoFlags($this->person, $link))->not->toHaveKey('repeated_domain')
        ->and(promoFlags($this->person, $link))->toMatchArray(['repeated_domain' => 'course.example.test'])
        ->and(promoFlags(User::factory()->create(['created_at' => now()->subDays(30)]), $link))->not->toHaveKey('repeated_domain');
});

it('flags an organisation the same person keeps naming, whatever the words before it', function () {
    promoFlags($this->person, ['comment' => 'Learn at Dhaka Skill Institute']);
    promoFlags($this->person, ['comment' => 'Join Dhaka Skill Institute today']);

    expect(promoFlags($this->person, ['comment' => 'Best is Dhaka Skill Institute']))
        ->toMatchArray(['repeated_organisation' => 'dhaka skill institute']);
});

it('does not take a bare term as the same organisation', function () {
    promoFlags($this->person, ['comment' => 'Learn at a training centre']);
    promoFlags($this->person, ['comment' => 'Or at any training centre']);

    expect(promoFlags($this->person, ['comment' => 'Some training centre']))->not->toHaveKey('repeated_organisation');
});

it('never flags an AI edit', function () {
    $revision = recordValue(FieldDefinition::query()->where('key', 'physical_workload')->sole(), 3);

    expect(app(FlagPromotionalEdit::class)->handle($revision->id))->toBe([])
        ->and(RevisionFlag::query()->count())->toBe(0);
});
