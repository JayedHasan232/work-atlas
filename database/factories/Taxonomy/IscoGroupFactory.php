<?php

namespace Database\Factories\Taxonomy;

use App\Modules\Taxonomy\Models\IscoGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * A unit group (level 4) by default, with its major, sub-major and minor groups created as needed.
 * Titles are placeholders, not ISCO-08 text; the real list comes from the M6 import.
 *
 * @extends Factory<IscoGroup>
 */
class IscoGroupFactory extends Factory
{
    protected $model = IscoGroup::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->numerify('####'),
            'level' => fn (array $attributes): int => strlen($attributes['code']),
            'parent_code' => fn (array $attributes): ?string => self::ensureParents($attributes['code']),
            'title_en' => fn (array $attributes): string => "Test group {$attributes['code']}",
            'title_bn' => null,
            'bsco_code' => null,
        ];
    }

    /**
     * A group at a given level (1–4).
     */
    public function level(int $level): static
    {
        return $this->state(fn (): array => [
            'code' => fake()->unique()->numerify(str_repeat('#', $level)),
        ]);
    }

    /**
     * Creates the missing ancestors of a code and returns the parent's code.
     */
    private static function ensureParents(string $code): ?string
    {
        if (strlen($code) === 1) {
            return null;
        }

        $parent = null;

        for ($level = 1; $level < strlen($code); $level++) {
            $ancestor = substr($code, 0, $level);

            IscoGroup::query()->firstOrCreate(
                ['code' => $ancestor],
                ['level' => $level, 'parent_code' => $parent, 'title_en' => "Test group {$ancestor}"],
            );

            $parent = $ancestor;
        }

        return $parent;
    }
}
