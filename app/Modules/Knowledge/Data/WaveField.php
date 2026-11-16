<?php

namespace App\Modules\Knowledge\Data;

use App\Modules\Knowledge\Enums\FieldType;

/**
 * A field definition as a generation prompt needs it (PLAN M12): what it means, its options and its
 * anchors, in both languages.
 */
final readonly class WaveField
{
    /**
     * @param  array<string, string>  $label  by locale
     * @param  array<string, string>  $help  by locale
     * @param  list<string>  $options
     * @param  array<string, array<string, string>>  $optionLabels  by option key, then locale
     * @param  array<string, array<int, string>>  $anchors  by locale, then scale point 1–5
     */
    public function __construct(
        public int $id,
        public string $key,
        public int $version,
        public FieldType $type,
        public array $label,
        public array $help,
        public array $options = [],
        public array $optionLabels = [],
        public array $anchors = [],
    ) {}
}
