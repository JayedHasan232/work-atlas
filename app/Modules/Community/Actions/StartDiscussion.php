<?php

namespace App\Modules\Community\Actions;

use App\Models\User;
use App\Modules\Community\Data\NewDiscussion;
use App\Modules\Community\Exceptions\ContributionRejected;
use App\Modules\Community\Models\DiscussionThread;
use App\Modules\Knowledge\Queries\FieldDefinitionByKey;
use App\Modules\Taxonomy\Queries\PublicOccupations;
use Illuminate\Support\Facades\DB;

/**
 * Opens a thread on an occupation's talk page (PLAN M19), with its first post. Only public
 * occupations have talk pages; a field link must name a known field. The same limits as changes apply
 * (D68), and a blocked person cannot post (D69).
 */
final class StartDiscussion
{
    public function __construct(
        private readonly PublicOccupations $occupations,
        private readonly FieldDefinitionByKey $fields,
        private readonly CheckContributionLimits $limits,
        private readonly WriteDiscussionPost $write,
    ) {}

    public function handle(NewDiscussion $discussion): int
    {
        $user = User::query()->findOrFail($discussion->userId);

        if ($this->occupations->find($discussion->occupationId) === null) {
            throw ContributionRejected::because('occupation', "occupation {$discussion->occupationId} has no public page");
        }

        if ($discussion->fieldKey !== null && $this->fields->latest($discussion->fieldKey) === null) {
            throw ContributionRejected::because('field', "field {$discussion->fieldKey} does not exist");
        }

        $this->limits->handlePost($user);
        $title = trim($discussion->title);

        return DB::transaction(function () use ($discussion, $user, $title): int {
            $thread = DiscussionThread::query()->create([
                'occupation_id' => $discussion->occupationId,
                'field_key' => $discussion->fieldKey,
                'title' => $title,
                'locale' => $discussion->locale,
                'user_id' => $user->id,
            ]);

            $this->write->handle($thread, $user->id, $discussion->body, $discussion->locale, [$title]);

            return $thread->id;
        });
    }
}
