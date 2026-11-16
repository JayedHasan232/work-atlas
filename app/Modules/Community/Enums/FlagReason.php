<?php

namespace App\Modules\Community\Enums;

/**
 * Why an edit may be promotional (PLAN M18, D67). A flag only sorts the patrol queue; a person decides.
 */
enum FlagReason: string
{
    case Interest = 'interest';
    case Link = 'link';
    case RepeatedDomain = 'repeated_domain';
    case Organisation = 'organisation';
    case RepeatedOrganisation = 'repeated_organisation';
}
