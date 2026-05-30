<?php

namespace Tests\Feature\Auth;

use Livewire\Volt\Volt;
use Spatie\Permission\Models\Role;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response
        ->assertOk()
        ->assertSeeVolt('pages.auth.register');
});

test('new users can register', function () {
    Role::create(['name' => 'student', 'guard_name' => 'web']);

    $component = Volt::test('pages.auth.register')
        ->set('name', 'Test User')
        ->set('email_prefix', 'test')
        ->set('password', 'password')
        ->set('password_confirmation', 'password');

    $component->call('register');

    $component->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});
