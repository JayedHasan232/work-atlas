<?php

namespace App\Modules\Community\Actions;

use App\Modules\Community\Models\ContributionConsent;

/**
 * Records that a person agreed to the current contribution terms (`atlas.contribution_terms_version`).
 * Agreeing to the same version again keeps the first time.
 */
final class RecordContributionConsent
{
    public function handle(int $userId, ?string $termsVersion = null): void
    {
        ContributionConsent::query()->firstOrCreate(
            ['user_id' => $userId, 'terms_version' => $termsVersion ?? self::currentVersion()],
            ['accepted_at' => now()],
        );
    }

    public static function currentVersion(): string
    {
        return (string) config('atlas.contribution_terms_version');
    }
}
