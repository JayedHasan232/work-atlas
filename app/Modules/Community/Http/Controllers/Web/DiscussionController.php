<?php

namespace App\Modules\Community\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Modules\Community\Actions\ReplyToDiscussion;
use App\Modules\Community\Actions\StartDiscussion;
use App\Modules\Community\Data\NewDiscussion;
use App\Modules\Community\Exceptions\ContributionRejected;
use App\Modules\Taxonomy\Queries\OccupationBySlug;
use App\Support\Localization\LocalizedRoutes;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\ValidationException;

/**
 * Opens a thread and adds a reply on an occupation's talk page (PLAN M19), for signed-in people with
 * a verified email. Limits and blocks come back as form errors.
 */
final class DiscussionController extends Controller
{
    public const int MAX_BODY = 5000;

    public function store(string $slug, Request $request, OccupationBySlug $slugs, StartDiscussion $start): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:'.self::MAX_BODY],
            'field' => ['nullable', 'string', 'max:64'],
        ]);
        $address = $slugs->handle($slug);

        if ($address === null || $address->redirects()) {
            abort(404);
        }

        $locale = App::getLocale();
        $thread = $this->attempt(fn (): int => $start->handle(new NewDiscussion(
            userId: (int) $request->user()?->getAuthIdentifier(),
            occupationId: $address->occupationId,
            fieldKey: $data['field'] ?? null,
            title: $data['title'],
            body: $data['body'],
            locale: $locale,
        )));

        return redirect()
            ->to(LocalizedRoutes::path('talk.show', ['slug' => $slug, 'thread' => $thread], $locale))
            ->with('notice', 'talk.notices.started');
    }

    public function reply(string $slug, int $thread, Request $request, OccupationBySlug $slugs, ReplyToDiscussion $reply): RedirectResponse
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:'.self::MAX_BODY],
        ]);
        $address = $slugs->handle($slug);

        if ($address === null || $address->redirects()) {
            abort(404);
        }

        $locale = App::getLocale();
        $this->attempt(fn (): int => $reply->handle($address->occupationId, $thread, (int) $request->user()?->getAuthIdentifier(), $data['body'], $locale));

        return redirect()
            ->to(LocalizedRoutes::path('talk.show', ['slug' => $slug, 'thread' => $thread], $locale))
            ->with('notice', 'talk.notices.replied');
    }

    /**
     * @param  Closure(): int  $write
     */
    private function attempt(Closure $write): int
    {
        try {
            return $write();
        } catch (ContributionRejected $rejected) {
            if (in_array($rejected->reason, ['occupation', 'thread'], true)) {
                abort(404);
            }

            throw ValidationException::withMessages([
                $rejected->reason === 'field' ? 'field' : 'body' => __("talk.errors.{$rejected->reason}"),
            ]);
        }
    }
}
