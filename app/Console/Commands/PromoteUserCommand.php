<?php

namespace App\Console\Commands;

use App\Modules\Community\Actions\ChangeRole;
use App\Modules\Community\Enums\Role;
use App\Modules\Community\Exceptions\UserNotFound;
use Illuminate\Console\Command;

/**
 * Gives a person a role (contributor, trusted, reviewer, expert, maintainer). Also lowers a role.
 */
final class PromoteUserCommand extends Command
{
    protected $signature = 'atlas:user:promote {email : The account\'s email} {role : contributor, trusted, reviewer, expert or maintainer}';

    protected $description = 'Give a person a role';

    public function handle(ChangeRole $changeRole): int
    {
        $role = Role::tryFrom((string) $this->argument('role'));

        if ($role === null) {
            $this->components->error('Unknown role. Use one of: '.implode(', ', array_column(Role::cases(), 'value')).'.');

            return self::FAILURE;
        }

        $email = (string) $this->argument('email');

        try {
            $previous = $changeRole->handle($email, $role);
        } catch (UserNotFound $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info($previous === $role
            ? "{$email} is already a {$role->value}."
            : "{$email} is now a {$role->value} (was {$previous->value}).");

        return self::SUCCESS;
    }
}
