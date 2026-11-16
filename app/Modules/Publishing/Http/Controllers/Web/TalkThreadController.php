<?php

namespace App\Modules\Publishing\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Modules\Publishing\PageRobots;
use App\Modules\Publishing\Presenters\TalkPresenter;
use App\Modules\Taxonomy\Queries\OccupationBySlug;
use App\Modules\Taxonomy\Queries\PublicOccupations;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Inertia\Inertia;
use Inertia\Response;

/**
 * `/occupations/{slug}/talk/{thread}` (PLAN M19): one discussion with its posts. Never indexed.
 */
final class TalkThreadController extends Controller
{
    public function __invoke(
        string $slug,
        int $thread,
        Request $request,
        OccupationBySlug $slugs,
        PublicOccupations $occupations,
        TalkPresenter $presenter,
        PageRobots $robots,
    ): Response {
        $address = $slugs->handle($slug);
        $occupation = $address === null || $address->redirects() ? null : $occupations->find($address->occupationId);
        $user = $request->user();
        $props = $occupation === null ? null : $presenter->thread($occupation, $thread, $user, App::getLocale());

        if ($props === null) {
            abort(404);
        }

        $robots->decide($request, false);

        return Inertia::render('talk/thread', ['talk' => $props]);
    }
}
