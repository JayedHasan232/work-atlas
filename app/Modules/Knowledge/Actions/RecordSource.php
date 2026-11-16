<?php

namespace App\Modules\Knowledge\Actions;

use App\Modules\Knowledge\Data\SourceInput;
use App\Modules\Knowledge\Exceptions\InvalidSource;
use App\Modules\Knowledge\Models\Source;
use Carbon\CarbonImmutable;
use Throwable;

/**
 * Records a source and returns its id. The same source read on the same day (every field equal) is
 * recorded once, so a repeated import doesn't add rows. A URL must be http(s); the access date must
 * be a real day, not in the future (stored in UTC, the day as read in Asia/Dhaka).
 */
final class RecordSource
{
    public function handle(SourceInput $input): int
    {
        foreach (['url' => $input->url, 'archive URL' => $input->archiveUrl] as $name => $url) {
            if ($url !== null && preg_match('#^https?://\S+$#', $url) !== 1) {
                throw InvalidSource::because("the {$name} must start with http:// or https://");
            }
        }

        $accessedOn = $this->accessDate($input->accessedOn);

        return Source::query()->firstOrCreate([
            'url' => $input->url,
            'accessed_on' => $accessedOn,
            'publisher' => $input->publisher,
            'title' => $input->title,
            'license' => $input->license,
            'archive_url' => $input->archiveUrl,
        ])->id;
    }

    private function accessDate(string $value): string
    {
        try {
            $date = CarbonImmutable::createFromFormat('!Y-m-d', $value, 'Asia/Dhaka');
        } catch (Throwable) {
            $date = null;
        }

        if ($date === null || $date->format('Y-m-d') !== $value) {
            throw InvalidSource::because('the access date must be a day written as YYYY-MM-DD');
        }

        if ($date->isAfter(CarbonImmutable::now('Asia/Dhaka')->endOfDay())) {
            throw InvalidSource::because('the access date is in the future');
        }

        return $value;
    }
}
