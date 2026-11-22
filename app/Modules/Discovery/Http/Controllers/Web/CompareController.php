<?php

namespace App\Modules\Discovery\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Modules\Publishing\PageRobots;
use App\Modules\Publishing\Presenters\ComparePresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Inertia\Inertia;
use Inertia\Response;

/**
 * `/compare?o[]=…` (PLAN M20): up to three occupations side by side, chosen in the URL.
 */
final class CompareController extends Controller
{
    private const int MAX_CHOSEN = 10;

    public function __invoke(Request $request, ComparePresenter $presenter, PageRobots $robots): Response
    {
        $robots->decide($request, false);
        $chosen = $request->query('o');
        $query = $request->query('q');

        return Inertia::render('discovery/compare', [
            'compare' => $presenter->present(
                $this->slugs(is_array($chosen) ? $chosen : [$chosen]),
                is_string($query) && trim($query) !== '' ? mb_substr(trim($query), 0, 100) : null,
                App::getLocale(),
            ),
        ]);
    }

    /**
     * The chosen slugs, cleaned before anything is looked up: only well-formed slugs, each once, and
     * no more than a short list (the page shows a few; a long query string must not cost queries).
     *
     * @param  array<array-key, mixed>  $chosen
     * @return list<string>
     */
    private function slugs(array $chosen): array
    {
        $slugs = [];

        foreach (array_slice($chosen, 0, self::MAX_CHOSEN * 2) as $slug) {
            if (is_string($slug) && preg_match('/\A[a-z0-9]+(-[a-z0-9]+)*\z/', $slug) === 1 && strlen($slug) <= 200) {
                $slugs[$slug] = true;
            }
        }

        return array_slice(array_keys($slugs), 0, self::MAX_CHOSEN);
    }
}
