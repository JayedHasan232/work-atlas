<?php

namespace App\Modules\Knowledge\Data;

final readonly class FieldSyncResult
{
    /**
     * @param  list<string>  $created  "key vN"
     * @param  list<string>  $updated  "key vN"
     * @param  list<string>  $notInFiles  definitions in the database that no file lists (kept)
     */
    public function __construct(
        public array $created,
        public array $updated,
        public int $unchanged,
        public array $notInFiles,
    ) {}
}
