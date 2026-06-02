<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('password update page can be rendered', function () {
    $user = User::factory()->create();

    $this
        ->actingAs($user)
        ->get(route('profile.password.edit'))
        ->assertOk()
        ->assertSee('Change Password')
        ->assertSee('action="'.route('profile.password.update').'"', false)
        ->assertSee('name="_method" value="PATCH"', false);
});

test('password can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.password.update'), [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

    $response
        ->assertRedirect(route('profile.password.edit'))
        ->assertSessionHas('password_status', 'Password updated successfully.');

    $this->assertTrue(Hash::check('new-password', $user->refresh()->password));
});

test('current password must be provided', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from(route('profile.password.edit'))
        ->patch(route('profile.password.update'), [
            'current_password' => '',
            'password' => '',
            'password_confirmation' => '',
        ]);

    $response
        ->assertRedirect(route('profile.password.edit'))
        ->assertSessionHasErrors(['current_password', 'password']);

    $this->assertTrue(Hash::check('password', $user->refresh()->password));

    $this->followingRedirects()
        ->actingAs($user)
        ->from(route('profile.password.edit'))
        ->patch(route('profile.password.update'), [
            'current_password' => '',
            'password' => '',
            'password_confirmation' => '',
        ])
        ->assertOk()
        ->assertSee('The current password field is required.')
        ->assertSee('The password field is required.');
});

test('current password must be correct', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from(route('profile.password.edit'))
        ->patch(route('profile.password.update'), [
            'current_password' => 'wrong-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

    $response
        ->assertRedirect(route('profile.password.edit'))
        ->assertSessionHasErrors('current_password');

    $this->assertTrue(Hash::check('password', $user->refresh()->password));
});

test('password confirmation must match', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from(route('profile.password.edit'))
        ->patch(route('profile.password.update'), [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'different-password',
        ]);

    $response
        ->assertRedirect(route('profile.password.edit'))
        ->assertSessionHasErrors('password');

    $this->assertTrue(Hash::check('password', $user->refresh()->password));
});
