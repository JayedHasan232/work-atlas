<?php

namespace App\Modules\Community\Enums;

/**
 * Where an error report stands in the moderation queue (PLAN M16). A report is open until a reviewer
 * or maintainer handles it: resolved (the page was fixed or a change was proposed), dismissed (nothing
 * wrong) or spam.
 */
enum ErrorReportState: string
{
    case Open = 'open';
    case Resolved = 'resolved';
    case Dismissed = 'dismissed';
    case Spam = 'spam';
}
