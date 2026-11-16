<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use App\Modules\Community\Actions\RecordContributionConsent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    public function __construct(private readonly RecordContributionConsent $recordConsent) {}

    /**
     * Validate and create a newly registered user, with their agreement to the contribution terms.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
            'contribution_terms' => ['accepted'],
        ], [
            'contribution_terms.accepted' => __('auth.register.consent_required'),
        ])->validate();

        // The account and the agreement are written together: no account without consent.
        return DB::transaction(function () use ($input): User {
            $user = User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => $input['password'],
            ]);

            $this->recordConsent->handle($user->id);

            return $user;
        });
    }
}
