<?php

namespace App\Modules\Taxonomy\Queries;

use App\Modules\Taxonomy\Models\IscoGroup;

/**
 * An ISCO-08 code with its parents and English titles, for a prompt: "7 T > 71 T > 712 T > 7126 T".
 */
final class IscoHierarchy
{
    public function handle(string $code): string
    {
        $codes = array_map(fn (int $length): string => substr($code, 0, $length), range(1, strlen($code)));
        $titles = IscoGroup::query()->whereIn('code', $codes)->pluck('title_en', 'code');

        return implode(' > ', array_map(
            fn (string $part): string => trim($part.' '.($titles[$part] ?? '')),
            $codes,
        ));
    }
}
