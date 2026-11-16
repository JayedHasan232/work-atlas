<?php

namespace App\Modules\Knowledge\Models;

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
 * {"bn": {"1": "…", …, "5": "…"}, "en": {…}}.
 *
 * @property int $id
 * @property string $key
 * @property int $version
 * @property int $wave
 * @property string $group
 * @property FieldType $type
 * @property list<string>|null $options
 * @property array<string, array<int, string>>|null $anchors
 * @property bool $sensitive
 * @property bool $requires_source
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['key', 'version', 'wave', 'group', 'type', 'options', 'anchors', 'sensitive', 'requires_source'])]
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
}
