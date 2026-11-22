<?php

namespace App\Modules\Taxonomy\Data;

use App\Modules\Taxonomy\Enums\PublishState;

final readonly class OccupationAddress
{
    /**
     * @param  string  $slug  the slug that was asked for
     * @param  string  $canonicalSlug  where the page lives: the same slug, or the one it was merged into
     */
    public function __construct(
        public int $occupationId,
        public string $slug,
        public string $canonicalSlug,
        public PublishState $state,
    ) {}

    /**
     * A merged occupation's address redirects only to a public page. A target that is still a
     * candidate (or was rejected) stays hidden: a redirect would give away its slug.
     */
    public function redirects(): bool
    {
        return $this->canonicalSlug !== $this->slug && $this->state->isPublic();
    }
}
