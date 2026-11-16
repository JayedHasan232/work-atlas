<?php

namespace App\Modules\Taxonomy\Queries;

use App\Modules\Taxonomy\Actions\NormalizeForSearch;
use App\Modules\Taxonomy\Data\SearchHit;
use App\Modules\Taxonomy\Enums\PublishState;
use App\Modules\Taxonomy\Enums\SearchMatch;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use stdClass;

/**
 * Finds public occupations by any of their names in any language: primary, alias, local and
 * regional (rule 10, PLAN M15, D59). Both the names and the query go through NormalizeForSearch.
 * A name matches exactly, by prefix (of the name or of one of its words, through the `simple`
 * full-text index) or by trigram similarity (misspellings): word similarity of at least 0.6 (pg_trgm's
 * default), or whole-name similarity of at least `atlas.search.min_similarity`. Each occupation counts once, by its
 * best name; results are ranked exact > prefix > similar, then by similarity, then a primary name and
 * a name in the reader's language first.
 */
final class SearchOccupations
{
    public function __construct(
        private readonly NormalizeForSearch $normalize,
        private readonly PublicOccupations $occupations,
    ) {}

    /**
     * @return list<SearchHit>
     */
    public function handle(string $query, string $locale, int $limit = 20): array
    {
        $key = $this->normalize->handle($query);

        if ($key === '' || $limit < 1) {
            return [];
        }

        // Every word as a prefix. The key has no punctuation or symbols, so no tsquery syntax.
        $words = implode(' & ', array_map(fn (string $word): string => $word.':*', explode(' ', $key)));

        $rows = DB::select(<<<'SQL'
            WITH q AS (SELECT ?::text AS key, to_tsquery('simple', ?) AS words)
            SELECT occupation_id, name, locale, tier
            FROM (
                SELECT DISTINCT ON (n.occupation_id)
                    n.occupation_id, n.name, n.locale, n.type, n.search_key,
                    CASE
                        WHEN n.search_key = q.key THEN 3
                        WHEN n.search_key LIKE q.key || '%' OR to_tsvector('simple', n.search_key) @@ q.words THEN 2
                        ELSE 1
                    END AS tier,
                    greatest(word_similarity(q.key, n.search_key), similarity(q.key, n.search_key)) AS score
                FROM occupation_names n
                JOIN occupations o ON o.id = n.occupation_id
                CROSS JOIN q
                WHERE o.publish_state = ANY (?::text[])
                    AND (
                        n.search_key = q.key
                        OR n.search_key LIKE q.key || '%'
                        OR to_tsvector('simple', n.search_key) @@ q.words
                        OR q.key <% n.search_key
                        OR (n.search_key % q.key AND similarity(q.key, n.search_key) >= ?)
                    )
                ORDER BY n.occupation_id, tier DESC, score DESC, (n.type = 'primary') DESC, (n.locale = ?) DESC, length(n.search_key)
            ) best
            ORDER BY tier DESC, score DESC, (type = 'primary') DESC, (locale = ?) DESC, length(search_key), occupation_id
            LIMIT ?
            SQL, [$key, $words, $this->publicStates(), Config::float('atlas.search.min_similarity'), $locale, $locale, $limit]);

        $matches = array_values(array_map(fn (stdClass $row): array => [
            'id' => (int) $row->occupation_id,
            'name' => (string) $row->name,
            'locale' => (string) $row->locale,
            'tier' => (int) $row->tier,
        ], $rows));

        $items = [];

        foreach ($this->occupations->findMany(array_column($matches, 'id')) as $item) {
            $items[$item->id] = $item;
        }

        $hits = [];

        foreach ($matches as $match) {
            $item = $items[$match['id']] ?? null;

            if ($item !== null) {
                $hits[] = new SearchHit($item, $match['name'], $match['locale'], SearchMatch::fromTier($match['tier']));
            }
        }

        return $hits;
    }

    /** A Postgres array literal of the public states, e.g. `{listed,published}`. */
    private function publicStates(): string
    {
        return '{'.implode(',', array_map(fn (PublishState $state): string => $state->value, PublishState::public())).'}';
    }
}
