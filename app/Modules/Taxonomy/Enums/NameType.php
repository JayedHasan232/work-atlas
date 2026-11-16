<?php

namespace App\Modules\Taxonomy\Enums;

/**
 * What kind of name a row in occupation_names is. Regional names carry a region.
 */
enum NameType: string
{
    case Primary = 'primary';
    case Alias = 'alias';
    case Local = 'local';
    case Regional = 'regional';
}
