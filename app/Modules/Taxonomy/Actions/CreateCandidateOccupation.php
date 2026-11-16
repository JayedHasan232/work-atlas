<?php

namespace App\Modules\Taxonomy\Actions;

use App\Modules\Taxonomy\Enums\PublishState;
use App\Modules\Taxonomy\Exceptions\UnknownUnitGroup;
use App\Modules\Taxonomy\Models\IscoGroup;
use App\Modules\Taxonomy\Models\Occupation;
use Illuminate\Support\Str;

/**
 * A new occupation in a unit group, waiting for the list review (`candidate`, M11). Its slug is ASCII
 * from the English name (A10), made unique with -2, -3 …. Its names are written separately, through
 * Knowledge (D44).
 */
final class CreateCandidateOccupation
{
    public function handle(string $iscoCode, string $englishName, ?int $aiRunId = null): int
    {
        if (! IscoGroup::query()->where('code', $iscoCode)->where('level', 4)->exists()) {
            throw UnknownUnitGroup::code($iscoCode);
        }

        return Occupation::query()->create([
            'slug' => $this->uniqueSlug($englishName),
            'isco_code' => $iscoCode,
            'publish_state' => PublishState::Candidate,
            'ai_run_id' => $aiRunId,
        ])->id;
    }

    private function uniqueSlug(string $englishName): string
    {
        $base = Str::limit(Str::slug(Str::ascii($englishName)), 80, '');
        $base = trim($base, '-') !== '' ? trim($base, '-') : 'occupation';
        $taken = Occupation::query()->where('slug', $base)->orWhere('slug', 'like', "{$base}-%")->pluck('slug')->all();

        if (! in_array($base, $taken, true)) {
            return $base;
        }

        $suffix = 2;

        while (in_array("{$base}-{$suffix}", $taken, true)) {
            $suffix++;
        }

        return "{$base}-{$suffix}";
    }
}
