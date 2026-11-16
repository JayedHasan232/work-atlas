<?php

namespace App\Modules\Publishing;

use App\Modules\Knowledge\Enums\FieldStatus;
use Illuminate\Support\Facades\Config;

/**
 * The A11a rule for search engines (PLAN M14, D55). Off during the Phase 1 beta: nothing is
 * indexable. When on, an occupation page is indexable once enough of its fields are checked by
 * people and the required fields (the description) are reviewed.
 */
final class IndexGate
{
    public function enabled(): bool
    {
        return Config::boolean('atlas.index_gate.enabled');
    }

    /**
     * @return list<int>
     */
    public function waves(): array
    {
        /** @var list<int> $waves */
        $waves = Config::array('atlas.index_gate.waves');

        return $waves;
    }

    /**
     * Whether a page with these fields passes. Each field key maps to the statuses of its current
     * values (empty: no value yet); a field reaches a status when it has a value and all its values
     * have reached it.
     *
     * @param  array<string, list<FieldStatus>>  $fields
     */
    public function passes(array $fields): bool
    {
        if (! $this->enabled() || $fields === []) {
            return false;
        }

        $minimum = FieldStatus::from(Config::string('atlas.index_gate.min_status'));
        $reached = count(array_filter($fields, fn (array $statuses): bool => $this->reached($statuses, $minimum)));

        if ($reached / count($fields) < Config::float('atlas.index_gate.min_share')) {
            return false;
        }

        /** @var array<string, string> $required */
        $required = Config::array('atlas.index_gate.required');

        foreach ($required as $key => $status) {
            if (! $this->reached($fields[$key] ?? [], FieldStatus::from($status))) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<FieldStatus>  $statuses
     */
    private function reached(array $statuses, FieldStatus $minimum): bool
    {
        foreach ($statuses as $status) {
            if (! $status->atLeast($minimum)) {
                return false;
            }
        }

        return $statuses !== [];
    }
}
