<?php

namespace App\Modules\Community\Actions;

use App\Models\User;

/**
 * A person chooses whether their name appears in the public credit (PLAN M16). Anonymous credit still
 * counts their contributions, without the name, and hides their profile page.
 */
final class SetAnonymousCredit
{
    public function handle(int $userId, bool $anonymous): void
    {
        $user = User::query()->findOrFail($userId);
        $user->forceFill(['anonymous_credit' => $anonymous])->save();
    }
}
