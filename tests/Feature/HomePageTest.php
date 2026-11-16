<?php

use Inertia\Testing\AssertableInertia as Assert;

test('the home page renders the welcome page', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('welcome'));
});
