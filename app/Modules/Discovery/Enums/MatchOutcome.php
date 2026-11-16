<?php

namespace App\Modules\Discovery\Enums;

/**
 * How one occupation fares on one answer of the guided filter (PLAN M20). An occupation whose known
 * value goes against an answer is left out, so it has no outcome.
 */
enum MatchOutcome: string
{
    /** The known value fits the answer. */
    case Met = 'met';

    /** The value fits only in part (e.g. part-time "sometimes", or a certificate still to earn). */
    case Partly = 'partly';

    /** No value yet, "unknown", or a disputed value: it neither counts for nor against. */
    case Unknown = 'unknown';
}
