<?php

namespace App\Modules\Taxonomy\Actions;

use App\Modules\AiPipeline\Data\AiRequest;
use App\Modules\Taxonomy\Enums\NameType;
use App\Modules\Taxonomy\Enums\PublishState;
use App\Modules\Taxonomy\Exceptions\UnknownUnitGroup;
use App\Modules\Taxonomy\Models\IscoGroup;
use App\Modules\Taxonomy\Models\Occupation;
use App\Modules\Taxonomy\Models\OccupationName;

/**
 * The AI request that asks which occupations in Bangladesh belong to one unit group (PLAN M10,
 * prompt taxonomy/expand-unit-group). The context lists only occupations a person has already
 * accepted (listed or published): candidates would change the input on every run, so a rerun could
 * never be recognised as the same request (D45).
 */
final class BuildExpansionRequest
{
    public const string PROMPT_KEY = 'taxonomy/expand-unit-group';

    public const int PROMPT_VERSION = 1;

    public function handle(string $iscoCode, ?string $provider = null, ?string $model = null): AiRequest
    {
        $group = IscoGroup::query()->where('code', $iscoCode)->where('level', 4)->first()
            ?? throw UnknownUnitGroup::code($iscoCode);

        return new AiRequest(
            promptKey: self::PROMPT_KEY,
            promptVersion: self::PROMPT_VERSION,
            values: [
                'isco_code' => $group->code,
                'group_title_en' => $group->title_en,
                'group_title_bn' => $group->title_bn ?? 'unknown',
                'hierarchy' => $this->hierarchy($group),
                'existing_occupations' => $this->existing($group->code),
            ],
            provider: $provider ?? (string) config('atlas.ai.tasks.taxonomy_expand.provider'),
            model: $model ?? (string) config('atlas.ai.tasks.taxonomy_expand.model'),
        );
    }

    private function hierarchy(IscoGroup $group): string
    {
        $codes = array_map(fn (int $length): string => substr($group->code, 0, $length), [1, 2, 3, 4]);
        $titles = IscoGroup::query()->whereIn('code', $codes)->pluck('title_en', 'code');

        return implode(' > ', array_map(
            fn (string $code): string => trim($code.' '.($titles[$code] ?? '')),
            $codes,
        ));
    }

    private function existing(string $iscoCode): string
    {
        $ids = Occupation::query()
            ->where('isco_code', $iscoCode)
            ->whereIn('publish_state', [PublishState::Listed, PublishState::Published])
            ->orderBy('slug')
            ->pluck('id')
            ->all();

        if ($ids === []) {
            return 'none';
        }

        $names = OccupationName::query()
            ->whereIn('occupation_id', $ids)
            ->where('type', NameType::Primary)
            ->get()
            ->groupBy('occupation_id');

        return implode("\n", array_map(function (int $id) use ($names): string {
            $primary = $names->get($id)?->pluck('name', 'locale');

            return '- '.($primary['en'] ?? 'unknown').' / '.($primary['bn'] ?? 'unknown');
        }, $ids));
    }
}
