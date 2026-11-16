<?php

namespace App\Console\Commands;

use App\Modules\Community\Actions\PromoteTrustedContributors;
use Illuminate\Console\Command;

/**
 * PLAN M17, D65: promote contributors who qualify for trusted (daily in the scheduler).
 */
final class PromoteTrustedCommand extends Command
{
    protected $signature = 'atlas:trust:promote';

    protected $description = 'Make contributors trusted who have enough accepted contributions and days since signup';

    public function handle(PromoteTrustedContributors $promote): int
    {
        $this->components->info(sprintf('Promoted %d contributors to trusted.', $promote->handle()));

        return self::SUCCESS;
    }
}
