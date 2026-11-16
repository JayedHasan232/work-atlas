<?php

namespace App\Modules\Knowledge\Models;

use Database\Factories\Knowledge\SourceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * Where a value comes from: URL, publisher, license and the date it was read.
 *
 * @property int $id
 * @property string $url
 * @property string|null $publisher
 * @property string|null $title
 * @property string|null $license
 * @property Carbon $accessed_on
 * @property string|null $archive_url
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['url', 'publisher', 'title', 'license', 'accessed_on', 'archive_url'])]
#[UseFactory(SourceFactory::class)]
class Source extends Model
{
    /** @use HasFactory<SourceFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'accessed_on' => 'date',
        ];
    }

    /**
     * @return BelongsToMany<Revision, $this>
     */
    public function revisions(): BelongsToMany
    {
        return $this->belongsToMany(Revision::class)->withPivot('created_at');
    }
}
