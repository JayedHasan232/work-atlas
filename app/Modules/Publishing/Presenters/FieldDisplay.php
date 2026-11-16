<?php

namespace App\Modules\Publishing\Presenters;

use App\Modules\Knowledge\Data\FieldDefinitionData;
use App\Modules\Knowledge\Data\SlotValue;
use App\Modules\Knowledge\Enums\FieldType;
use Illuminate\Support\Facades\Config;

/**
 * How a field value is shown to a reader, shared by the occupation page and the suggestion form: the
 * value in the reader's language, and its display shape (scale point with its anchor, option labels,
 * yes/no, text).
 */
final class FieldDisplay
{
    /**
     * The value in the reader's language, else the one without a language, else any.
     *
     * @param  list<SlotValue>  $values
     */
    public function pick(array $values, string $locale): ?SlotValue
    {
        foreach ([$locale, null] as $wanted) {
            foreach ($values as $value) {
                if ($value->locale === $wanted) {
                    return $value;
                }
            }
        }

        return $values[0] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public function display(FieldDefinitionData $definition, mixed $value, string $locale): array
    {
        if ($value === null) {
            return ['kind' => 'unknown'];
        }

        return match ($definition->type) {
            FieldType::Scale1To5 => [
                'kind' => 'scale',
                'point' => (int) $value,
                'anchor' => $this->text(array_map(
                    fn (array $points): string => $points[(int) $value] ?? '',
                    $definition->anchors ?? [],
                ), $locale),
            ],
            FieldType::Enum, FieldType::Band, FieldType::MultiEnum => [
                'kind' => 'options',
                'labels' => array_map(
                    fn (mixed $option): string => $this->text($definition->optionLabels[(string) $option] ?? [], $locale) ?? (string) $option,
                    is_array($value) ? array_values($value) : [$value],
                ),
            ],
            FieldType::Boolean => ['kind' => 'boolean', 'value' => (bool) $value],
            default => ['kind' => 'text', 'text' => is_scalar($value) ? (string) $value : ''],
        };
    }

    /**
     * @param  array<string, string>  $texts  by locale
     */
    public function text(array $texts, string $locale): ?string
    {
        $text = $texts[$locale] ?? $texts[Config::string('app.fallback_locale')] ?? null;

        return $text === null || $text === '' ? null : $text;
    }
}
