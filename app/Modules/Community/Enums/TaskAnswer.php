<?php

namespace App\Modules\Community\Enums;

/**
 * A person's answer to "Is this right?". Skip ("not sure") is kept so the question is not asked again,
 * and never counts towards a consensus.
 */
enum TaskAnswer: string
{
    case Agree = 'agree';
    case Disagree = 'disagree';
    case Skip = 'skip';
}
