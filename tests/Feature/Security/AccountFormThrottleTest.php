<?php

use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;

/*
 * PLAN M23 security review (D81): signups and password-reset mails are limited per IP address, so
 * nobody can create accounts in bulk or send reset mail to many addresses.
 */

beforeEach(function () {
    Config::set('atlas.security.account_forms_per_hour', 2);
    Notification::fake();
});

it('limits signups per IP address', function () {
    foreach (range(1, 2) as $i) {
        $this->post('/register', [
            'name' => "Person {$i}",
            'email' => "person{$i}@example.org",
            'password' => 'a-long-test-password-1',
            'password_confirmation' => 'a-long-test-password-1',
            'contribution_terms' => 'on',
        ])->assertStatus(302);
        auth()->logout();
    }

    $this->post('/register', ['name' => 'Third', 'email' => 'third@example.org'])->assertTooManyRequests();
});

it('limits password-reset mails per IP address', function () {
    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email])->assertStatus(302);
    $this->post('/forgot-password', ['email' => 'someone@example.org'])->assertStatus(302);
    $this->post('/forgot-password', ['email' => 'another@example.org'])->assertTooManyRequests();
});

it('does not count signing in against the signup limit', function () {
    $user = User::factory()->create();

    foreach (range(1, 3) as $i) {
        $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])->assertStatus(302);
    }

    $this->post('/forgot-password', ['email' => $user->email])->assertStatus(302);
});
