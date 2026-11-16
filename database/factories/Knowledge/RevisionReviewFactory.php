<?php

namespace Database\Factories\Knowledge;

use App\Models\User;
use App\Modules\Knowledge\Enums\ReviewDecision;
use App\Modules\Knowledge\Models\Revision;
use App\Modules\Knowledge\Models\RevisionReview;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RevisionReview>
 */
class RevisionReviewFactory extends Factory
{
    protected $model = RevisionReview::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'revision_id' => Revision::factory(),
            'reviewer_id' => User::factory(),
            'decision' => ReviewDecision::Accepted,
            'comment' => null,
        ];
    }

    public function rejected(): static
    {
        return $this->state(['decision' => ReviewDecision::Rejected]);
    }
}
