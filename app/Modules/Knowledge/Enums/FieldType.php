<?php

namespace App\Modules\Knowledge\Enums;

/**
 * How a field's value is shaped. Scales use the anchors in the field definition; bands are ranges (D7).
 */
enum FieldType: string
{
    case Scale1To5 = 'scale_1_5';
    case Enum = 'enum';
    case MultiEnum = 'multi_enum';
    case Boolean = 'boolean';
    case Band = 'band';
    case Text = 'text';
}
