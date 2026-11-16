<?php

namespace App\Modules\Community\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Modules\Community\Actions\SuggestEdit;
use App\Modules\Community\Data\EditSuggestion;
use App\Modules\Community\Exceptions\ContributionRejected;
use App\Modules\Knowledge\Data\SourceInput;
use App\Modules\Knowledge\Exceptions\InvalidSource;
use App\Modules\Knowledge\Exceptions\KnowledgeRuleViolation;
use App\Modules\Knowledge\Exceptions\SourceRequired;
use App\Modules\Taxonomy\Queries\OccupationBySlug;
use App\Support\Localization\LocalizedRoutes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Takes a suggested change to one field (PLAN M16) from a signed-in person with a verified email. A
 * conflict-of-interest declaration is required: "none", or what the interest is. The Knowledge rules
 * (a value that fits the field, a source where one is required) come back as form errors.
 */
final class EditSuggestionController extends Controller
{
    public const int MAX_SOURCES = 5;

    public function __invoke(string $slug, string $field, Request $request, OccupationBySlug $slugs, SuggestEdit $suggest): RedirectResponse
    {
        $data = $request->validate([
            'value' => ['present'],
            'sources' => ['array', 'max:'.self::MAX_SOURCES],
            'sources.*.url' => ['required', 'string', 'url:http,https', 'max:2048'],
            'sources.*.accessed_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'sources.*.title' => ['nullable', 'string', 'max:255'],
            'sources.*.publisher' => ['nullable', 'string', 'max:255'],
            'comment' => ['nullable', 'string', 'max:2000'],
            'interest' => ['required', Rule::in(['none', 'declared'])],
            'interest_note' => ['nullable', 'required_if:interest,declared', 'string', 'max:500'],
        ]);

        $address = $slugs->handle($slug);

        if ($address === null || $address->redirects()) {
            abort(404);
        }

        /** @var list<array{url: string, accessed_on: string, title?: string|null, publisher?: string|null}> $sources */
        $sources = array_values($data['sources'] ?? []);
        $locale = App::getLocale();

        try {
            $result = $suggest->handle(new EditSuggestion(
                userId: (int) $request->user()?->getAuthIdentifier(),
                occupationId: $address->occupationId,
                fieldKey: $field,
                value: $data['value'],
                locale: $locale,
                sources: array_map(fn (array $source): SourceInput => new SourceInput(
                    url: $source['url'],
                    accessedOn: $source['accessed_on'],
                    publisher: $source['publisher'] ?? null,
                    title: $source['title'] ?? null,
                ), $sources),
                comment: $data['comment'] ?? null,
                declaredInterest: $data['interest'] === 'declared' ? $data['interest_note'] : null,
            ));
        } catch (ContributionRejected $rejected) {
            if (in_array($rejected->reason, ['occupation', 'field'], true)) {
                abort(404);
            }

            throw ValidationException::withMessages(['value' => __("contribute.errors.{$rejected->reason}")]);
        } catch (KnowledgeRuleViolation $violation) {
            throw ValidationException::withMessages(match (true) {
                $violation instanceof SourceRequired => ['sources' => __('contribute.errors.source_required')],
                $violation instanceof InvalidSource => ['sources' => __('contribute.errors.invalid_source')],
                default => ['value' => __('contribute.errors.invalid_value')],
            });
        }

        return redirect()
            ->to(LocalizedRoutes::path('occupations.show', ['slug' => $slug], $locale))
            ->with('notice', $result->applied ? 'contribute.suggest.applied' : 'contribute.suggest.proposed');
    }
}
