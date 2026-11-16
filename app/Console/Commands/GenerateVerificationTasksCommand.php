<?php

namespace App\Console\Commands;

use App\Modules\Community\Actions\GenerateVerificationTasks;
use Illuminate\Console\Command;

/**
 * PLAN M17: make the micro-task questions (hourly in the scheduler). Safe to run again.
 */
final class GenerateVerificationTasksCommand extends Command
{
    protected $signature = 'atlas:tasks:generate';

    protected $description = 'Make micro-tasks for verifiable values and mark tasks whose value changed as stale';

    public function handle(GenerateVerificationTasks $generate): int
    {
        $result = $generate->handle();

        $this->components->info(sprintf('Made %d tasks; %d went stale.', $result['created'], $result['stale']));

        return self::SUCCESS;
    }
}
