<?php

namespace Database\Factories\Knowledge;

use App\Modules\Knowledge\Enums\FieldType;
use App\Modules\Knowledge\Models\FieldDefinition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * A 1–5 scale by default. The anchors are placeholders for tests, not the Wave 1 anchors (O2, M7).
 *
 * @extends Factory<FieldDefinition>
 */
class FieldDefinitionFactory extends Factory
{
    protected $model = FieldDefinition::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => 'test_'.fake()->unique()->lexify('????????'),
            'version' => 1,
            'wave' => 1,
            'group' => 'filter',
            'type' => FieldType::Scale1To5,
            'options' => null,
            'anchors' => self::placeholderAnchors(),
            'sensitive' => false,
            'requires_source' => false,
        ];
    }

    /**
     * @param  list<string>  $options
     */
    public function enum(array $options = ['a', 'b', 'c']): static
    {
        return $this->state(['type' => FieldType::Enum, 'options' => $options, 'anchors' => null]);
    }

    public function text(): static
    {
        return $this->state(['type' => FieldType::Text, 'options' => null, 'anchors' => null]);
    }

    /**
     * The taxonomy `names` field (wave 0, D44).
     */
    public function names(): static
    {
        return $this->state(['key' => 'names', 'version' => 1, 'wave' => 0, 'group' => 'names', 'type' => FieldType::NameList, 'options' => null, 'anchors' => null]);
    }

    /**
     * Income-like: hidden until verified and never saved without a source.
     */
    public function sensitive(): static
    {
        return $this->state(['wave' => 3, 'group' => 'money', 'sensitive' => true, 'requires_source' => true]);
    }

    /**
     * @return array<string, array<int, string>>
     */
    private static function placeholderAnchors(): array
    {
        $anchors = [];

        foreach (['bn', 'en'] as $locale) {
            foreach (range(1, 5) as $point) {
                $anchors[$locale][$point] = "Test anchor {$point} ({$locale})";
            }
        }

        return $anchors;
    }
}
