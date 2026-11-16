<?php

namespace App\Modules\Taxonomy\Queries;

use App\Modules\Taxonomy\Data\OccupationContext;
use App\Modules\Taxonomy\Enums\NameType;
use App\Modules\Taxonomy\Models\Occupation;
use App\Modules\Taxonomy\Models\OccupationName;

final class OccupationContextById
{
    public function __construct(private readonly IscoHierarchy $hierarchy) {}

    public function handle(int $occupationId): ?OccupationContext
    {
        $occupation = Occupation::query()->find($occupationId);

        if ($occupation === null) {
            return null;
        }

        $names = [];

        foreach (OccupationName::query()->where('occupation_id', $occupation->id)->orderBy('id')->get() as $name) {
            $label = match ($name->type) {
                NameType::Primary => $name->name,
                NameType::Regional => "{$name->name} (regional, {$name->region})",
                default => "{$name->name} ({$name->type->value})",
            };

            $names[$name->locale] ??= [];

            if ($name->type === NameType::Primary) {
                array_unshift($names[$name->locale], $label);
            } else {
                $names[$name->locale][] = $label;
            }
        }

        return new OccupationContext(
            occupationId: $occupation->id,
            slug: $occupation->slug,
            iscoCode: $occupation->isco_code,
            hierarchy: $this->hierarchy->handle($occupation->isco_code),
            names: $names,
        );
    }
}
