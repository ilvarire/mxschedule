<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Volt\Volt;

test('password can be updated', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $component = Volt::test('profile.update-password-form')
        ->set('current_password', 'password')
        ->set('password', 'new-password')
        ->set('password_confirmation', 'new-password')
        ->call('updatePassword');

    $component
        ->assertHasNoErrors()
        ->assertNoRedirect()
        ->assertSet('password_updated', true)
        ->assertSee('Password updated successfully.');

    $this->assertTrue(Hash::check('new-password', $user->refresh()->password));
});

test('correct password must be provided to update password', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $component = Volt::test('profile.update-password-form')
        ->set('current_password', 'wrong-password')
        ->set('password', 'new-password')
        ->set('password_confirmation', 'new-password')
        ->call('updatePassword');

    $component
        ->assertHasErrors(['current_password'])
        ->assertNoRedirect();
});

test('password can be updated without javascript', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.password.update'), [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

    $response
        ->assertRedirect()
        ->assertSessionHas('password_status', 'Password updated successfully.');

    $this->assertTrue(Hash::check('new-password', $user->refresh()->password));
});

test('current password is validated without javascript', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from(route('profile'))
        ->patch(route('profile.password.update'), [
            'current_password' => 'wrong-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

    $response
        ->assertRedirect(route('profile'))
        ->assertSessionHasErrors('current_password', null, 'updatePassword');

    $this->assertTrue(Hash::check('password', $user->refresh()->password));
});

test('password validation errors do not open the delete account modal', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from(route('profile'))
        ->patch(route('profile.password.update'), [
            'current_password' => 'wrong-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

    $response->assertRedirect(route('profile'));

    $this
        ->followingRedirects()
        ->actingAs($user)
        ->get(route('profile'))
        ->assertOk()
        ->assertSee('style="display: none;"', false);
});
