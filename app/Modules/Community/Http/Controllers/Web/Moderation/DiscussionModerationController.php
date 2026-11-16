<?php

namespace App\Modules\Community\Http\Controllers\Web\Moderation;

use App\Http\Controllers\Controller;
use App\Modules\Community\Actions\ModerateDiscussion;
use App\Modules\Community\Exceptions\ModerationNotAllowed;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Hide and show buttons on talk pages (PLAN M19, D70), for moderators. The person goes back to the
 * page they came from.
 */
final class DiscussionModerationController extends Controller
{
    public function hidePost(Request $request, int $post, ModerateDiscussion $moderate): RedirectResponse
    {
        return $this->attempt($request, fn (?string $reason) => $moderate->hidePost($post, $request->user() ?? abort(403), $reason), 'talk.notices.hidden');
    }

    public function showPost(Request $request, int $post, ModerateDiscussion $moderate): RedirectResponse
    {
        return $this->attempt($request, fn (?string $reason) => $moderate->showPost($post, $request->user() ?? abort(403), $reason), 'talk.notices.shown');
    }

    public function hideThread(Request $request, int $thread, ModerateDiscussion $moderate): RedirectResponse
    {
        return $this->attempt($request, fn (?string $reason) => $moderate->hideThread($thread, $request->user() ?? abort(403), $reason), 'talk.notices.hidden');
    }

    public function showThread(Request $request, int $thread, ModerateDiscussion $moderate): RedirectResponse
    {
        return $this->attempt($request, fn (?string $reason) => $moderate->showThread($thread, $request->user() ?? abort(403), $reason), 'talk.notices.shown');
    }

    /**
     * @param  Closure(string|null): void  $action
     */
    private function attempt(Request $request, Closure $action, string $notice): RedirectResponse
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:500']]);

        try {
            $action($data['reason'] ?? null);
        } catch (ModerationNotAllowed) {
            abort(403);
        }

        return back()->with('notice', $notice);
    }
}
