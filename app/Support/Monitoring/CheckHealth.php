<?php

namespace App\Support\Monitoring;

use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

/**
 * Makes `/up` honest (PLAN M22): the uptime check and the post-deploy smoke get a 500 when the
 * database or Redis (queues, cache, Horizon) cannot be reached, not just when PHP is up.
 */
final class CheckHealth
{
    public function handle(DiagnosingHealth $event): void
    {
        DB::connection()->select('select 1');
        Redis::connection()->ping();
    }
}
