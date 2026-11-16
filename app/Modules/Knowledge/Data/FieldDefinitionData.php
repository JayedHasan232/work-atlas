<?php

namespace App\Modules\Knowledge\Data;

use App\Modules\Knowledge\Enums\FieldType;

/**
 * One field definition as written in `data/fields/*.yaml`, checked by ReadFieldFiles.
 */
final readonly class FieldDefinitionData
{
    /**
     * @param  array<string, string>  $label  by locale
     * @param  array<string, string>  $help  by locale
     * @param  list<string>|null  $options  option keys in display order (enum, multi-enum, band)
     * @param  array<string, array<string, string>>|null  $optionLabels  by option key, then locale
     * @param  array<string, array<int, string>>|null  $anchors  by locale, then scale point 1–5
     */
    public function __construct(
        public string $key,
        public int $version,
        public int $wave,
        public string $group,
        public FieldType $type,
        public array $label,
        public array $help,
        public ?array $options = null,
        public ?array $optionLabels = null,
        public ?array $anchors = null,
        public bool $sensitive = false,
        public bool $requiresSource = false,
        public string $file = '',
    ) {}

    /**
     * The columns of `field_definitions`, except key and version.
     *
     * @return array<string, mixed>
     */
    public function attributes(): array
    {
        return [
            'wave' => $this->wave,
            'group' => $this->group,
            'type' => $this->type,
            'options' => $this->options,
            'anchors' => $this->anchors,
            'label' => $this->label,
            'help' => $this->help,
            'option_labels' => $this->optionLabels,
            'sensitive' => $this->sensitive,
            'requires_source' => $this->requiresSource,
        ];
    }
}
