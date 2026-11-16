<?php

namespace App\Modules\Knowledge\Exceptions;

use DomainException;

/**
 * A change that a Knowledge rule forbids. Nothing is written when one is thrown. Messages are for logs
 * and developers; the HTTP layer (M8) maps each class to a translated message.
 */
abstract class KnowledgeRuleViolation extends DomainException {}
