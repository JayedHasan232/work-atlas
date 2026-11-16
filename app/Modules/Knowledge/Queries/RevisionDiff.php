<?php

namespace App\Modules\Knowledge\Queries;

use App\Modules\Knowledge\Models\Revision;

/**
 * A line diff between a revision's old and new value, for the admin's revision view. Both values are
 * printed as indented JSON; each line is kept (' '), removed ('-') or added ('+').
 */
final class RevisionDiff
{
    /**
     * @return list<array{op: ' '|'-'|'+', line: string}>
     */
    public function handle(int $revisionId): array
    {
        $revision = Revision::query()->findOrFail($revisionId, ['id', 'old_value', 'new_value']);

        return self::lines(self::print($revision->old_value), self::print($revision->new_value));
    }

    /**
     * @param  list<string>  $old
     * @param  list<string>  $new
     * @return list<array{op: ' '|'-'|'+', line: string}>
     */
    public static function lines(array $old, array $new): array
    {
        // Longest common subsequence, filled from the end. Values are small, so O(n·m) is fine.
        $n = count($old);
        $m = count($new);
        $lcs = array_fill(0, $n + 1, array_fill(0, $m + 1, 0));

        for ($i = $n - 1; $i >= 0; $i--) {
            for ($j = $m - 1; $j >= 0; $j--) {
                $lcs[$i][$j] = $old[$i] === $new[$j]
                    ? $lcs[$i + 1][$j + 1] + 1
                    : max($lcs[$i + 1][$j], $lcs[$i][$j + 1]);
            }
        }

        $diff = [];
        $i = $j = 0;

        while ($i < $n || $j < $m) {
            if ($i < $n && $j < $m && $old[$i] === $new[$j]) {
                $diff[] = ['op' => ' ', 'line' => $old[$i++]];
                $j++;
            } elseif ($i < $n && ($j === $m || $lcs[$i + 1][$j] >= $lcs[$i][$j + 1])) {
                // Removed lines come before the lines that replace them.
                $diff[] = ['op' => '-', 'line' => $old[$i++]];
            } else {
                $diff[] = ['op' => '+', 'line' => $new[$j++]];
            }
        }

        return $diff;
    }

    /**
     * @return list<string>
     */
    private static function print(mixed $value): array
    {
        return explode("\n", (string) json_encode(
            $value,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION,
        ));
    }
}
