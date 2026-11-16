<?php

namespace App\Modules\Community\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Modules\Community\Actions\SubmitErrorReport;
use App\Modules\Community\Data\ErrorReportInput;
use App\Modules\Community\Exceptions\ContributionRejected;
use App\Modules\Taxonomy\Queries\OccupationBySlug;
use App\Support\Localization\LocalizedRoutes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\ValidationException;

/**
 * Takes an error report (PLAN M16). Anyone may send one; the route is rate-limited per IP address.
 * The hidden `website` field is a honeypot: a person never fills it, so a filled one is a bot. The bot
 * sees the same thank-you as a person, and nothing is stored.
 */
final class ErrorReportController extends Controller
{
    public const string HONEYPOT = 'website';

    public function __invoke(string $slug, Request $request, OccupationBySlug $slugs, SubmitErrorReport $submit): RedirectResponse
    {
        $locale = App::getLocale();
        $page = LocalizedRoutes::path('occupations.show', ['slug' => $slug], $locale);

        if (filled($request->input(self::HONEYPOT))) {
            return redirect()->to($page)->with('notice', 'contribute.report.thanks');
        }

        $data = $request->validate([
            'message' => ['required', 'string', 'max:'.SubmitErrorReport::MAX_LENGTH],
            'field' => ['nullable', 'string', 'max:64'],
        ]);

        $address = $slugs->handle($slug);

        if ($address === null || $address->redirects()) {
            abort(404);
        }

        try {
            $submit->handle(new ErrorReportInput(
                occupationId: $address->occupationId,
                fieldKey: $data['field'] ?? null,
                message: $data['message'],
                locale: $locale,
                userId: $request->user()?->id,
            ));
        } catch (ContributionRejected $rejected) {
            if ($rejected->reason === 'occupation') {
                abort(404);
            }

            throw ValidationException::withMessages([
                $rejected->reason === 'field' ? 'field' : 'message' => __("contribute.errors.{$rejected->reason}"),
            ]);
        }

        return redirect()->to($page)->with('notice', 'contribute.report.thanks');
    }
}
