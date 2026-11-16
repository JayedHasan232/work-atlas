<?php

namespace App\Modules\Knowledge\Enums;

/**
 * How a field's value is shaped. Scales use the anchors in the field definition; bands are ranges (D7).
 * A name list is an occupation's names in one language (D44).
 */
enum FieldType: string
{
    case Scale1To5 = 'scale_1_5';
    case Enum = 'enum';
    case MultiEnum = 'multi_enum';
    case Boolean = 'boolean';
    case Band = 'band';
    case Text = 'text';
    case NameList = 'name_list';

    /**
     * Text and name lists differ by language, so their values carry a locale; every other type is the
     * same in every language (D22).
     */
    public function isPerLanguage(): bool
    {
        return $this === self::Text || $this === self::NameList;
    }
}
