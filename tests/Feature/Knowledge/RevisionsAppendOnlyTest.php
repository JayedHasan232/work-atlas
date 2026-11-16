<?php

use App\Modules\Knowledge\Models\Revision;
use App\Modules\Knowledge\Models\RevisionReview;
use App\Modules\Knowledge\Models\Source;
use Illuminate\Support\Facades\DB;

// Rule 5 and D2: the revision history only grows. The model refuses, and so does the database when
// code skips the model.

describe('the model guard', function () {
    test('a saved revision cannot be changed', function () {
        $revision = Revision::factory()->create(['comment' => 'first']);

        $revision->comment = 'rewritten';

        expect(fn () => $revision->save())->toThrow(LogicException::class, 'update is not allowed');
        expect(DB::table('revisions')->where('id', $revision->id)->value('comment'))->toBe('first');
    });

    test('a saved revision cannot be deleted', function () {
        $revision = Revision::factory()->create();

        expect(fn () => $revision->delete())->toThrow(LogicException::class, 'delete is not allowed');
        expect(Revision::query()->whereKey($revision->id)->exists())->toBeTrue();
    });

    test('a saved review cannot be changed or deleted', function () {
        $review = RevisionReview::factory()->create();

        $review->comment = 'changed my mind';

        expect(fn () => $review->save())->toThrow(LogicException::class);
        expect(fn () => $review->delete())->toThrow(LogicException::class);
    });

    test('new revisions and reviews can still be added', function () {
        $first = Revision::factory()->create(['new_value' => 2]);
        $revert = Revision::factory()->create([
            'field_value_id' => $first->field_value_id,
            'old_value' => 2,
            'new_value' => null,
            'reverts_revision_id' => $first->id,
        ]);
        RevisionReview::factory()->create(['revision_id' => $revert->id]);

        expect(Revision::query()->where('field_value_id', $first->field_value_id)->count())->toBe(2)
            ->and($revert->reverts->is($first))->toBeTrue()
            ->and($revert->reviews)->toHaveCount(1);
    });
});

describe('the database trigger', function () {
    test('rejects UPDATE on revisions', function () {
        $revision = Revision::factory()->create(['comment' => 'first']);

        expect(databaseError(fn () => DB::update('UPDATE revisions SET comment = ? WHERE id = ?', ['rewritten', $revision->id])))
            ->toContain('revisions is append-only: UPDATE is not allowed');
        expect(databaseError(fn () => Revision::query()->whereKey($revision->id)->update(['comment' => 'rewritten'])))
            ->toContain('append-only');
        expect(DB::table('revisions')->where('id', $revision->id)->value('comment'))->toBe('first');
    });

    test('rejects DELETE and TRUNCATE on revisions', function () {
        $revision = Revision::factory()->create();

        expect(databaseError(fn () => DB::delete('DELETE FROM revisions WHERE id = ?', [$revision->id])))
            ->toContain('revisions is append-only: DELETE is not allowed');
        expect(databaseError(fn () => Revision::query()->whereKey($revision->id)->delete()))
            ->toContain('append-only');
        expect(databaseError(fn () => DB::statement('TRUNCATE revisions CASCADE')))
            ->toContain('append-only: TRUNCATE is not allowed');
        expect(Revision::query()->whereKey($revision->id)->exists())->toBeTrue();
    });

    test('rejects UPDATE and DELETE on revision reviews', function () {
        $review = RevisionReview::factory()->create();

        expect(databaseError(fn () => DB::update("UPDATE revision_reviews SET decision = 'rejected' WHERE id = ?", [$review->id])))
            ->toContain('revision_reviews is append-only');
        expect(databaseError(fn () => DB::delete('DELETE FROM revision_reviews WHERE id = ?', [$review->id])))
            ->toContain('revision_reviews is append-only');
    });

    test('rejects removing or swapping the sources of a revision', function () {
        $revision = Revision::factory()->create();
        $source = Source::factory()->create();
        $revision->sources()->attach($source);

        expect(databaseError(fn () => $revision->sources()->detach($source)))
            ->toContain('revision_source is append-only: DELETE is not allowed');
        expect(databaseError(fn () => DB::update('UPDATE revision_source SET source_id = ? WHERE revision_id = ?', [Source::factory()->create()->id, $revision->id])))
            ->toContain('revision_source is append-only: UPDATE is not allowed');
        expect($revision->sources()->pluck('sources.id')->all())->toBe([$source->id]);
    });
});
