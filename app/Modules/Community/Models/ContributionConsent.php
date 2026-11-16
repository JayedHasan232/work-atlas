<?php

namespace App\Modules\Community\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * A person's agreement to the contribution terms (contributions are published under CC BY-SA 4.0),
 * with the terms version and the time. One row per person and version.
 *
 * @property int $id
 * @property int $user_id
 * @property string $terms_version
 * @property CarbonImmutable $accepted_at
 */
final class ContributionConsent extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'terms_version', 'accepted_at'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['accepted_at' => 'immutable_datetime'];
    }
}
