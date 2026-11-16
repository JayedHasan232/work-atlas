<?php

namespace App\Modules\Taxonomy\Actions;

use Illuminate\Support\Facades\DB;

/**
 * Flags possible duplicate occupations (PLAN M11, A8): two occupations in the same ISCO-08 minor group
 * (first three digits) with a name in the same language that is the same after normalization (alias
 * overlap) or similar by trigram (pg_trgm) at or above the threshold. Merged and rejected occupations
 * are left out. A pair is stored once and never flagged again once it exists, so a "keep separate"
 * decision holds (D47). Returns the number of new pairs.
 */
final class DetectDuplicates
{
    public function handle(?float $threshold = null): int
    {
        $threshold ??= (float) config('atlas.taxonomy.duplicate_threshold');

        if ($threshold <= 0 || $threshold > 1) {
            throw new \InvalidArgumentException('The duplicate threshold is a number above 0 and at most 1.');
        }

        return DB::transaction(function () use ($threshold): int {
            // `%` uses the trigram index with this threshold, for this transaction only.
            DB::select("SELECT set_config('pg_trgm.similarity_threshold', ?, true)", [(string) $threshold]);

            return DB::affectingStatement(<<<'SQL'
                INSERT INTO duplicate_candidates
                    (occupation_id, other_occupation_id, similarity, locale, name, other_name, status, created_at, updated_at)
                SELECT DISTINCT ON (a.occupation_id, b.occupation_id)
                    a.occupation_id, b.occupation_id,
                    round(greatest(similarity(a.normalized_name, b.normalized_name),
                        CASE WHEN a.normalized_name = b.normalized_name THEN 1 ELSE 0 END)::numeric, 3),
                    a.locale, a.name, b.name, 'open', now(), now()
                FROM occupation_names a
                JOIN occupation_names b
                    ON b.locale = a.locale
                    AND b.occupation_id > a.occupation_id
                    AND (b.normalized_name = a.normalized_name OR b.normalized_name % a.normalized_name)
                JOIN occupations oa ON oa.id = a.occupation_id AND oa.publish_state NOT IN ('merged', 'rejected')
                JOIN occupations ob ON ob.id = b.occupation_id AND ob.publish_state NOT IN ('merged', 'rejected')
                WHERE left(oa.isco_code, 3) = left(ob.isco_code, 3)
                ORDER BY a.occupation_id, b.occupation_id,
                    (a.normalized_name = b.normalized_name) DESC,
                    similarity(a.normalized_name, b.normalized_name) DESC
                ON CONFLICT (occupation_id, other_occupation_id) DO NOTHING
                SQL);
        });
    }
}
