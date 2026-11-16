<?php

use Illuminate\Support\Facades\Config;

// PLAN M22 (A12): self-hosted Umami only, off unless configured, never sending query strings.

it('loads no analytics script unless Umami is configured', function () {
    $this->get('/')->assertOk()->assertDontSee('data-website-id', false);

    Config::set('atlas.analytics.umami_script', 'https://stats.example.org/script.js');

    $this->get('/')->assertDontSee('data-website-id', false);
});

it('loads the self-hosted Umami script without query strings and honouring Do Not Track', function () {
    Config::set('atlas.analytics.umami_script', 'https://stats.example.org/script.js');
    Config::set('atlas.analytics.umami_website_id', '00000000-0000-4000-8000-000000000000');

    $this->get('/')->assertOk()->assertSee(
        '<script defer src="https://stats.example.org/script.js" data-website-id="00000000-0000-4000-8000-000000000000" data-do-not-track="true" data-exclude-search="true" data-exclude-hash="true"></script>',
        false,
    );
});
