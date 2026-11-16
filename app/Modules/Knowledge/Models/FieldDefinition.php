<?php

namespace App\Modules\Knowledge\Models;

use App\Modules\Knowledge\Data\FieldDefinitionData;
use App\Modules\Knowledge\Enums\FieldType;
use Database\Factories\Knowledge\FieldDefinitionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * What can be said about an occupation, versioned: a change of meaning, options or anchors is a new
 * version. `anchors` holds the 1–5 scale anchors per locale, keyed by scale point:
 * {"bn": {"1": "…", …, "5": "…"}, "en": {…}}. `label`, `help` and `option_labels` are the words people see
 * (M7), which can be reworded without a new version.
 *
 * @property int $id
 * @property string $key
 * @property int $version
 * @property int $wave
 * @property string $group
 * @property FieldType $type
 * @property list<string>|null $options
 * @property array<string, array<int, string>>|null $anchors
 * @property array<string, string>|null $label
 * @property array<string, string>|null $help
 * @property array<string, array<string, string>>|null $option_labels
 * @property bool $sensitive
 * @property bool $requires_source
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['key', 'version', 'wave', 'group', 'type', 'options', 'anchors', 'label', 'help', 'option_labels', 'sensitive', 'requires_source'])]
#[UseFactory(FieldDefinitionFactory::class)]
class FieldDefinition extends Model
{
    /** @use HasFactory<FieldDefinitionFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'wave' => 'integer',
            'type' => FieldType::class,
            'options' => 'array',
            'anchors' => 'array',
            'label' => 'array',
            'help' => 'array',
            'option_labels' => 'array',
            'sensitive' => 'boolean',
            'requires_source' => 'boolean',
        ];
    }

    /**
     * @return HasMany<FieldValue, $this>
     */
    public function values(): HasMany
    {
        return $this->hasMany(FieldValue::class);
    }

    public function toData(): FieldDefinitionData
    {
        return new FieldDefinitionData(
            key: $this->key,
            version: $this->version,
            wave: $this->wave,
            group: $this->group,
            type: $this->type,
            label: $this->label ?? [],
            help: $this->help ?? [],
            options: $this->options,
            optionLabels: $this->option_labels,
            anchors: $this->anchors,
            sensitive: $this->sensitive,
            requiresSource: $this->requires_source,
        );
    }
}
