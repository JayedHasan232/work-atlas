<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Redis;

// PLAN M22: the uptime check and the post-deploy smoke use `/up`.

it('reports healthy when the database and Redis answer', function () {
    $this->get('/up')->assertOk();
});

it('fails when Redis cannot be reached', function () {
    Config::set('database.redis.default.port', 1);
    Redis::purge('default');

    $this->get('/up')->assertServerError();
});

it('fails when the database cannot be reached', function () {
    Config::set('database.connections.unreachable', ['port' => 1] + Config::array('database.connections.pgsql'));
    Config::set('database.default', 'unreachable');

    try {
        $this->get('/up')->assertServerError();
    } finally {
        Config::set('database.default', 'pgsql');
    }
});

it('sets no session cookie', function () {
    $response = $this->get('/up');

    expect($response->headers->getCookies())->toBe([]);
});
