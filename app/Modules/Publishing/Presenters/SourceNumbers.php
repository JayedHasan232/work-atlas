<?php

namespace App\Modules\Publishing\Presenters;

use App\Modules\Knowledge\Data\CitedSource;

/**
 * Numbers the sources of a page in the order they are first cited, each source once.
 */
final class SourceNumbers
{
    /** @var array<int, array<string, mixed>> by source id */
    private array $sources = [];

    public function number(CitedSource $source): int
    {
        if (! isset($this->sources[$source->id])) {
            $this->sources[$source->id] = [
                'number' => count($this->sources) + 1,
                'url' => $source->url,
                'title' => $source->title,
                'publisher' => $source->publisher,
                'license' => $source->license,
                'accessedOn' => $source->accessedOn,
                'archiveUrl' => $source->archiveUrl,
            ];
        }

        return $this->sources[$source->id]['number'];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function all(): array
    {
        return array_values($this->sources);
    }
}
