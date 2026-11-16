<?php

namespace App\Modules\Publishing\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Modules\Taxonomy\Queries\OccupationBySlug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/**
 * `/occupations/{slug}` (A10). A merged occupation's old slug redirects permanently to the occupation
 * it was merged into, in the same language (D5). The page itself is built in M13; until then every
 * other slug is not found.
 */
final class OccupationPageController extends Controller
{
    public function __invoke(Request $request, string $slug, OccupationBySlug $occupations): RedirectResponse
    {
        $address = $occupations->handle($slug);

        if ($address === null || ! $address->redirects()) {
            abort(404);
        }

        $name = (string) Route::currentRouteName();

        return redirect()->to(route($name, ['slug' => $address->canonicalSlug]), 301);
    }
}
