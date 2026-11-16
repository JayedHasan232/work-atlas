<?php

namespace App\Modules\Community\Actions;

use App\Models\User;
use App\Modules\Community\Enums\Role;
use App\Modules\Community\Exceptions\UserNotFound;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Gives a person a role, found by their email (case does not matter). The only code that changes
 * `users.role`; the change is written to the log. Returns the role the person had before.
 */
final class ChangeRole
{
    public function handle(string $email, Role $role): Role
    {
        return DB::transaction(function () use ($email, $role): Role {
            $user = User::query()
                ->whereRaw('lower(email) = ?', [mb_strtolower(trim($email))])
                ->lockForUpdate()
                ->first() ?? throw UserNotFound::withEmail($email);

            $previous = $user->role;

            if ($previous !== $role) {
                $user->forceFill(['role' => $role])->save();
                Log::notice('Role changed', ['user_id' => $user->id, 'from' => $previous->value, 'to' => $role->value]);
            }

            return $previous;
        });
    }
}
