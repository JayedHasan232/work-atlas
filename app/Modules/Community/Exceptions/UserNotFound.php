<?php

namespace App\Modules\Community\Exceptions;

use RuntimeException;

final class UserNotFound extends RuntimeException
{
    public static function withEmail(string $email): self
    {
        return new self("No account has the email {$email}.");
    }
}
