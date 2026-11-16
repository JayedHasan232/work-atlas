<?php

namespace App\Modules\Taxonomy\Actions;

use App\Modules\Taxonomy\Enums\PublishState;
use App\Modules\Taxonomy\Models\Occupation;
use Illuminate\Support\Facades\DB;

/**
 * Stores the index gate's answer for one occupation (PLAN M14). Only a listed or published
 * occupation can be indexable; any other is set to not indexable. Returns whether it changed.
 */
final class SetIndexable
{
    public function handle(int $occupationId, bool $indexable): bool
    {
        return DB::transaction(function () use ($occupationId, $indexable): bool {
            $occupation = Occupation::query()->lockForUpdate()->find($occupationId);

            if ($occupation === null) {
                return false;
            }

            $indexable = $indexable && in_array($occupation->publish_state, PublishState::public(), true);

            if ($occupation->indexable === $indexable) {
                return false;
            }

            $occupation->update(['indexable' => $indexable]);

            return true;
        });
    }
}
