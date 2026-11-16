<?php

use App\Models\User;
use App\Modules\Community\Enums\Role;

it('gives a person a role by email, whatever the case', function () {
    $user = User::factory()->create(['email' => 'reviewer@example.test']);

    $this->artisan('atlas:user:promote', ['email' => 'Reviewer@Example.test', 'role' => 'reviewer'])
        ->expectsOutputToContain('is now a reviewer (was contributor)')
        ->assertSuccessful();

    expect($user->fresh()->role)->toBe(Role::Reviewer);
});

it('lowers a role too', function () {
    $user = User::factory()->role(Role::Maintainer)->create();

    $this->artisan('atlas:user:promote', ['email' => $user->email, 'role' => 'trusted'])->assertSuccessful();

    expect($user->fresh()->role)->toBe(Role::Trusted);
});

it('fails for an unknown role or email', function (string $email, string $role, string $message) {
    User::factory()->create(['email' => 'known@example.test']);

    $this->artisan('atlas:user:promote', ['email' => $email, 'role' => $role])
        ->expectsOutputToContain($message)
        ->assertFailed();

    expect(User::query()->sole()->role)->toBe(Role::Contributor);
})->with([
    'unknown role' => ['known@example.test', 'admin', 'Unknown role'],
    'unknown email' => ['nobody@example.test', 'reviewer', 'No account has the email'],
]);

it('refuses a role the database does not know', function () {
    $user = User::factory()->create();

    expect(databaseError(fn () => DB::table('users')->where('id', $user->id)->update(['role' => 'admin'])))
        ->toContain('users_role_check');
});
