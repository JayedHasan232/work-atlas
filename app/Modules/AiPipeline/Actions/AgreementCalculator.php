<?php

namespace App\Modules\AiPipeline\Actions;

use App\Modules\Knowledge\Enums\FieldType;

/**
 * Whether two models' values for one field agree (PLAN M12, D50):
 *
 * - a 1–5 scale: at most 1 point apart;
 * - a band: the same band or a neighbouring one (bands are ranges, and the edges are judgement);
 * - one or more options: at least half of all the options given are given by both (|A ∩ B| / |A ∪ B| ≥ 0.5);
 * - anything else (one option, true/false, text): exactly the same.
 *
 * "unknown" (or null) agrees only with "unknown".
 */
final class AgreementCalculator
{
    /**
     * @param  list<string>  $options  the field's options in order (needed for a band)
     */
    public function handle(FieldType $type, mixed $a, mixed $b, array $options = []): bool
    {
        $aKnown = $a !== null && $a !== 'unknown';
        $bKnown = $b !== null && $b !== 'unknown';

        if (! $aKnown || ! $bKnown) {
            return $aKnown === $bKnown;
        }

        return match ($type) {
            FieldType::Scale1To5 => is_int($a) && is_int($b) && abs($a - $b) <= 1,
            FieldType::Band => $this->neighbours($a, $b, $options),
            FieldType::MultiEnum => $this->overlap($a, $b),
            default => $a === $b,
        };
    }

    /**
     * @param  list<string>  $options
     */
    private function neighbours(mixed $a, mixed $b, array $options): bool
    {
        $i = array_search($a, $options, true);
        $j = array_search($b, $options, true);

        return is_int($i) && is_int($j) && abs($i - $j) <= 1;
    }

    private function overlap(mixed $a, mixed $b): bool
    {
        if (! is_array($a) || ! is_array($b) || $a === [] || $b === []) {
            return false;
        }

        $union = count(array_unique([...$a, ...$b]));
        $both = count(array_intersect(array_unique($a), array_unique($b)));

        return $both / $union >= 0.5;
    }
}
