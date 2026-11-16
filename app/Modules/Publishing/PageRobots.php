<?php

namespace App\Modules\Publishing;

use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Whether search engines may index the page being served (rule 12, PLAN M14, D56). A page is
 * indexable only when the index gate is on and the page itself qualifies; otherwise it gets
 * `<meta name="robots" content="noindex">` (the `robots` prop) and `X-Robots-Tag: noindex`.
 */
final class PageRobots
{
    public const string NOINDEX = 'noindex';

    private const string ATTRIBUTE = 'atlas.indexable';

    public function __construct(private readonly IndexGate $gate) {}

    /** Records the page's own answer; a later call (the page's controller) replaces an earlier one. */
    public function decide(Request $request, bool $pageQualifies): void
    {
        $indexable = $this->gate->enabled() && $pageQualifies;

        $request->attributes->set(self::ATTRIBUTE, $indexable);
        Inertia::share('robots', $indexable ? null : self::NOINDEX);
    }

    public function indexable(Request $request): bool
    {
        return $request->attributes->get(self::ATTRIBUTE) === true;
    }
}
