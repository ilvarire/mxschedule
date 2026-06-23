<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\AccountCreationOtpNotification;
use Illuminate\Support\Facades\Notification;
use Livewire\Volt\Volt;
use Spatie\Permission\Models\Role;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response
        ->assertOk()
        ->assertSeeVolt('pages.auth.register');
});

test('registration sends an email otp before creating an account', function () {
    Role::create(['name' => 'student', 'guard_name' => 'web']);
    Notification::fake();

    $component = Volt::test('pages.auth.register')
        ->set('name', 'Test User')
        ->set('email_prefix', 'test')
        ->set('password', 'password')
        ->set('password_confirmation', 'password');

    $component->call('register');

    $component
        ->assertNoRedirect()
        ->assertSet('otp_sent', true)
        ->assertSet('pending_email', 'test@mxschedule.test')
        ->assertSee('We sent a 6-digit verification code');

    expect(User::where('email', 'test@mxschedule.test')->exists())->toBeFalse();

    Notification::assertSentOnDemand(AccountCreationOtpNotification::class, function ($notification, $channels, $notifiable) {
        return in_array('mail', $channels, true)
            && ($notifiable->routes['mail'] ?? null) === 'test@mxschedule.test'
            && strlen($notification->code) === 6;
    });
});

test('new users can register after email otp verification', function () {
    Role::create(['name' => 'student', 'guard_name' => 'web']);
    Notification::fake();

    $component = Volt::test('pages.auth.register')
        ->set('name', 'Test User')
        ->set('email_prefix', 'test')
        ->set('password', 'password')
        ->set('password_confirmation', 'password');

    $component->call('register');

    $otp = null;
    Notification::assertSentOnDemand(AccountCreationOtpNotification::class, function ($notification) use (&$otp) {
        $otp = $notification->code;

        return true;
    });

    $component
        ->set('otp', $otp)
        ->call('register');

    $component->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();

    $user = User::where('email', 'test@mxschedule.test')->first();

    expect($user)->not->toBeNull()
        ->and($user->email_verified_at)->not->toBeNull()
        ->and($user->hasRole('student'))->toBeTrue();
});

test('invalid registration otp is rejected', function () {
    Role::create(['name' => 'student', 'guard_name' => 'web']);
    Notification::fake();

    $component = Volt::test('pages.auth.register')
        ->set('name', 'Test User')
        ->set('email_prefix', 'test')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register')
        ->set('otp', '000000')
        ->call('register');

    $component
        ->assertHasErrors('otp')
        ->assertNoRedirect();

    expect(User::where('email', 'test@mxschedule.test')->exists())->toBeFalse();
});
