<?php

namespace App\Console\Commands;

use App\Modules\Publishing\Actions\QueueIndexGateCheck;
use App\Modules\Taxonomy\Queries\ListedOccupationIds;
use Illuminate\Console\Command;

/**
 * PLAN M14: recompute `indexable` for every listed and published occupation, after the index gate
 * is turned on or its thresholds change. Queues nothing while the gate is off. Safe to run again.
 */
final class RecomputeIndexGateCommand extends Command
{
    protected $signature = 'atlas:index-gate:recompute';

    protected $description = 'Queue a recompute of the index gate for every listed and published occupation';

    public function handle(ListedOccupationIds $occupations, QueueIndexGateCheck $queue): int
    {
        $queued = 0;

        foreach ($occupations->handle() as $id) {
            $queued += $queue->handle($id) ? 1 : 0;
        }

        $this->components->info(sprintf('Queued %d occupations.', $queued));

        return self::SUCCESS;
    }
}
