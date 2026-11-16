<?php

namespace App\Modules\Taxonomy\Actions;

use App\Modules\Knowledge\Actions\RecordSource;
use App\Modules\Knowledge\Data\SourceInput;
use App\Modules\Taxonomy\Data\IscoImportResult;
use App\Modules\Taxonomy\Exceptions\InvalidTaxonomyFile;
use App\Modules\Taxonomy\Models\IscoGroup;
use Illuminate\Support\Facades\DB;
use SplFileObject;

/**
 * Imports the ISCO-08 groups from a CSV file with the header `code,title_en`, one group per row.
 * The level and the parent come from the code (a code of length n is level n; its parent is the code
 * without its last digit), so every group except a major group needs its parent in the same file.
 *
 * The whole file is checked before anything is written, then imported in one transaction: new
 * groups are created, groups whose English title changed are updated, everything else is left alone,
 * so a second run changes nothing. Bangla titles and BSCO codes are never touched here (M6b). Groups in
 * the database but not in the file stay: occupations may point to them. The source is recorded once.
 */
final class ImportIscoGroups
{
    /** The group counts of ISCO-08, by level (ILO, ISCO-08 Volume I). */
    public const array ISCO08_GROUP_COUNTS = [1 => 10, 2 => 43, 3 => 130, 4 => 436];

    private const array HEADER = ['code', 'title_en'];

    public function __construct(
        private readonly RecordSource $recordSource,
    ) {}

    public function handle(string $path, SourceInput $source): IscoImportResult
    {
        $groups = $this->read($path);

        return DB::transaction(function () use ($groups, $source): IscoImportResult {
            $sourceId = $this->recordSource->handle($source);
            $existing = IscoGroup::query()->whereIn('code', array_keys($groups))->get()->keyBy('code');
            $created = $updated = $unchanged = 0;

            // Parents first, so the parent foreign key always finds its row.
            uksort($groups, fn (string $a, string $b): int => [strlen($a), $a] <=> [strlen($b), $b]);

            foreach ($groups as $code => $title) {
                $attributes = [
                    'level' => strlen($code),
                    'parent_code' => strlen($code) > 1 ? substr($code, 0, -1) : null,
                    'title_en' => $title,
                ];
                $group = $existing->get($code);

                if ($group === null) {
                    IscoGroup::query()->create(['code' => $code, ...$attributes]);
                    $created++;

                    continue;
                }

                $group->fill($attributes);

                if ($group->isDirty()) {
                    $group->save();
                    $updated++;
                } else {
                    $unchanged++;
                }
            }

            $perLevel = array_fill_keys(array_keys(self::ISCO08_GROUP_COUNTS), 0);

            foreach (array_keys($groups) as $code) {
                $perLevel[strlen($code)]++;
            }

            return new IscoImportResult($created, $updated, $unchanged, $perLevel, $sourceId);
        });
    }

    /**
     * @return array<string, string> English title by code
     */
    private function read(string $path): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw InvalidTaxonomyFile::because($path, 'the file does not exist');
        }

        $file = new SplFileObject($path);
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::READ_AHEAD | SplFileObject::DROP_NEW_LINE);
        $groups = [];
        $line = 0;

        foreach ($file as $row) {
            $line++;

            if (! is_array($row)) {
                continue;
            }

            $row = array_map(fn (mixed $cell): string => trim((string) $cell), $row);

            if ($line === 1) {
                $row[0] = preg_replace('/^\x{FEFF}/u', '', $row[0]) ?? $row[0];

                if ($row !== self::HEADER) {
                    throw InvalidTaxonomyFile::because($path, 'the header must be "'.implode(',', self::HEADER).'"');
                }

                continue;
            }

            if (count($row) !== count(self::HEADER)) {
                throw InvalidTaxonomyFile::because($path, "line {$line} must have ".count(self::HEADER).' columns');
            }

            [$code, $title] = $row;

            if (preg_match('/^\d{1,4}$/', $code) !== 1) {
                throw InvalidTaxonomyFile::because($path, "line {$line}: \"{$code}\" is not an ISCO code of 1 to 4 digits");
            }

            if ($title === '') {
                throw InvalidTaxonomyFile::because($path, "line {$line}: group {$code} has no English title");
            }

            if (array_key_exists($code, $groups)) {
                throw InvalidTaxonomyFile::because($path, "line {$line}: group {$code} is listed twice");
            }

            $groups[$code] = $title;
        }

        if ($line === 0) {
            throw InvalidTaxonomyFile::because($path, 'the file is empty');
        }

        if ($groups === []) {
            throw InvalidTaxonomyFile::because($path, 'the file has no groups');
        }

        foreach (array_keys($groups) as $code) {
            $code = (string) $code;

            if (strlen($code) > 1 && ! array_key_exists(substr($code, 0, -1), $groups)) {
                throw InvalidTaxonomyFile::because($path, "group {$code} has no parent group ".substr($code, 0, -1));
            }
        }

        return $groups;
    }
}
