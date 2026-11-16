<?php

namespace App\Models;

use App\Modules\Community\Enums\Role;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

/**
 * A person with an account. `role` is never mass-assigned: it changes only through
 * Community\Actions\ChangeRole (atlas:user:promote).
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Role $role
 * @property int $accepted_proposals_count
 * @property int $rejected_proposals_count
 * @property int $confirmed_answers_count
 * @property bool $anonymous_credit
 * @property Carbon|null $blocked_at
 * @property int|null $blocked_by
 * @property string|null $block_reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'role' => 'contributor',
        'accepted_proposals_count' => 0,
        'confirmed_answers_count' => 0,
        'rejected_proposals_count' => 0,
        'anonymous_credit' => false,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'role' => Role::class,
            'accepted_proposals_count' => 'integer',
            'confirmed_answers_count' => 'integer',
            'rejected_proposals_count' => 'integer',
            'anonymous_credit' => 'boolean',
            'blocked_at' => 'datetime',
        ];
    }

    /**
     * The admin panel is for maintainers only (CLAUDE.md: Filament is core team only).
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->role->canUseAdmin() && ! $this->isBlocked();
    }

    /**
     * A maintainer blocked this person (PLAN M18, D69): they can sign in and read, but not contribute
     * or moderate.
     */
    public function isBlocked(): bool
    {
        return $this->blocked_at !== null;
    }

    /**
     * Reviewers and above use the moderation tools (PLAN M18, D66), unless blocked.
     */
    public function canModerate(): bool
    {
        return $this->role->canReview() && ! $this->isBlocked();
    }
}
