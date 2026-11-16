<?php

namespace App\Modules\Knowledge\Data;

/**
 * Where something comes from: the URL that was read, on which day (`Y-m-d`), who publishes it and under
 * which license. Other modules record sources through Knowledge\Actions\RecordSource with this.
 */
final readonly class SourceInput
{
    public function __construct(
        public string $url,
        public string $accessedOn,
        public ?string $publisher = null,
        public ?string $title = null,
        public ?string $license = null,
        public ?string $archiveUrl = null,
    ) {}
}
