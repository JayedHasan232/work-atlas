<?php

namespace App\Modules\Community\Http\Controllers\Web\Moderation;

use App\Http\Controllers\Controller;
use App\Modules\Community\Actions\MarkPatrolled;
use App\Modules\Community\Actions\ModerateEdit;
use App\Modules\Community\Exceptions\ModerationNotAllowed;
use App\Modules\Knowledge\Enums\FieldStatus;
use App\Modules\Knowledge\Enums\ReviewDecision;
use App\Modules\Knowledge\Exceptions\KnowledgeRuleViolation;
use App\Support\Localization\LocalizedRoutes;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\Rule;

/**
 * The moderation buttons (PLAN M18, D66): decide a proposal, set a status, mark an edit patrolled,
 * revert an edit. A rule the Knowledge actions enforce (for example a sensitive value needs a
 * reviewer) comes back as a notice, not an error page.
 */
final class ModerationActionController extends Controller
{
    public function decide(Request $request, int $slot, int $revision, ModerateEdit $moderate): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', Rule::enum(ReviewDecision::class)],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);
        $decision = ReviewDecision::from($data['decision']);

        return $this->attempt(
            fn () => $moderate->review($slot, $revision, $request->user() ?? abort(403), $decision, $data['comment'] ?? null),
            $this->path('review.show', ['slot' => $slot]),
            $decision === ReviewDecision::Accepted ? 'moderation.notices.accepted' : 'moderation.notices.rejected',
        );
    }

    public function status(Request $request, int $slot, ModerateEdit $moderate): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::enum(FieldStatus::class)]]);

        return $this->attempt(
            fn () => $moderate->setStatus($slot, FieldStatus::from($data['status']), $request->user() ?? abort(403)),
            $this->path('review.show', ['slot' => $slot]),
            'moderation.notices.status',
        );
    }

    public function patrol(Request $request, int $revision, MarkPatrolled $patrol): RedirectResponse
    {
        return $this->attempt(
            fn () => $patrol->handle($revision, $request->user() ?? abort(403)),
            $this->path('patrol.index', []),
            'moderation.notices.patrolled',
        );
    }

    public function revert(Request $request, int $revision, ModerateEdit $moderate): RedirectResponse
    {
        $data = $request->validate(['comment' => ['nullable', 'string', 'max:2000']]);

        return $this->attempt(
            fn () => $moderate->revert($revision, $request->user() ?? abort(403), $data['comment'] ?? null),
            $this->path('patrol.index', []),
            'moderation.notices.reverted',
        );
    }

    private function attempt(Closure $action, string $next, string $notice): RedirectResponse
    {
        try {
            $action();
        } catch (KnowledgeRuleViolation|ModerationNotAllowed) {
            return back()->with('notice', 'moderation.notices.refused');
        }

        return redirect()->to($next)->with('notice', $notice);
    }

    /**
     * @param  array<string, int>  $parameters
     */
    private function path(string $name, array $parameters): string
    {
        return LocalizedRoutes::path($name, $parameters, App::getLocale());
    }
}
