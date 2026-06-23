<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rules;
use App\Notifications\AccountCreationOtpNotification;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $name = '';
    public string $email_prefix = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $otp = '';
    public bool $otp_sent = false;
    public string $pending_email = '';

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        $validated = $this->validateRegistrationData();
        $email = $validated['email'];

        if (! $this->otp_sent || $this->pending_email !== $email) {
            $this->sendOtp($validated);

            return;
        }

        $this->validate([
            'otp' => ['required', 'digits:6'],
        ]);

        $cachedHash = Cache::get($this->otpCacheKey($email));

        if (! $cachedHash) {
            $this->addError('otp', 'This verification code has expired. Please request a new code.');
            $this->otp_sent = false;

            return;
        }

        if (! Hash::check($this->otp, $cachedHash)) {
            $this->addError('otp', 'The verification code is invalid.');

            return;
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $email,
            'password' => Hash::make($validated['password']),
            'email_verified_at' => now(),
        ]);
        $user->assignRole('student');

        Cache::forget($this->otpCacheKey($email));

        Auth::login($user);

        $this->redirect(route('dashboard', absolute: false));
    }

    public function resendOtp(): void
    {
        $this->sendOtp($this->validateRegistrationData());
    }

    protected function validateRegistrationData(): array
    {
        $domain = config('app.allowed_email_domain', '@mxschedule.test');
        $fullEmail = $this->email_prefix . $domain;

        $validator = \Illuminate\Support\Facades\Validator::make(
            [
                'name' => $this->name,
                'email_prefix' => $this->email_prefix,
                'email' => $fullEmail,
                'password' => $this->password,
                'password_confirmation' => $this->password_confirmation,
            ],
            [
                'name' => ['required', 'string', 'max:255'],
                'email_prefix' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9._-]+$/'],
                'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
                'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
            ],
            [
                'email_prefix.regex' => 'The email prefix can only contain letters, numbers, dots, dashes, and underscores.',
                'email.unique' => 'An account with this email address already exists.',
            ]
        );

        $validator->validate();

        return $validator->validated();
    }

    protected function sendOtp(array $validated): void
    {
        $code = (string) random_int(100000, 999999);
        $email = $validated['email'];

        Cache::put($this->otpCacheKey($email), Hash::make($code), now()->addMinutes(10));

        Notification::route('mail', $email)
            ->notify(new AccountCreationOtpNotification($code, 10));

        $this->pending_email = $email;
        $this->otp_sent = true;
        $this->otp = '';
    }

    protected function otpCacheKey(string $email): string
    {
        return 'registration_otp:'.sha1(strtolower($email));
    }
}; ?>

<div>
    <div class="mb-8 text-center">
        <h2 class="text-2xl font-bold text-white mb-2">Create Account</h2>
        <p class="text-white/70 text-sm">Join the platform to manage your exams</p>
    </div>

    <form wire:submit="register" class="space-y-6">
        @if($otp_sent)
            <div class="rounded-xl border border-emerald-400/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-100">
                We sent a 6-digit verification code to <strong>{{ $pending_email }}</strong>. Enter it below to create your account.
            </div>
        @endif

        <!-- Name -->
        <div>
            <label for="name" class="block text-sm font-medium text-white/90 mb-1">Full Name</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-white/40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                </div>
                <input wire:model="name" id="name" type="text" class="block w-full pl-10 pr-3 py-2.5 border border-white/10 rounded-xl leading-5 bg-white/5 text-white placeholder-white/30 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 sm:text-sm transition-all duration-200" placeholder="John Doe" required autofocus autocomplete="name" />
            </div>
            <x-input-error :messages="$errors->get('name')" class="mt-2 text-red-400" />
        </div>

        <!-- Email Address Prefix -->
        <div>
            <label for="email_prefix" class="block text-sm font-medium text-white/90 mb-1">Email Address</label>
            <div class="relative flex items-stretch">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none z-10">
                    <svg class="h-5 w-5 text-white/40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                </div>
                <input wire:model="email_prefix" id="email_prefix" type="text" class="block w-full pl-10 pr-3 py-2.5 border border-r-0 border-white/10 rounded-l-xl leading-5 bg-white/5 text-white placeholder-white/30 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 sm:text-sm transition-all duration-200" placeholder="student123" required autocomplete="username" />
                <span class="inline-flex items-center px-4 py-2.5 rounded-r-xl border border-l-0 border-white/10 bg-white/10 text-white/70 sm:text-sm whitespace-nowrap">
                    {{ config('app.allowed_email_domain', '@mxschedule.test') }}
                </span>
            </div>
            <x-input-error :messages="$errors->get('email_prefix')" class="mt-2 text-red-400" />
            <x-input-error :messages="$errors->get('email')" class="mt-2 text-red-400" />
        </div>

        <!-- Password -->
        <div>
            <label for="password" class="block text-sm font-medium text-white/90 mb-1">Password</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-white/40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                </div>
                <input wire:model="password" id="password" type="password" class="block w-full pl-10 pr-3 py-2.5 border border-white/10 rounded-xl leading-5 bg-white/5 text-white placeholder-white/30 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 sm:text-sm transition-all duration-200" placeholder="••••••••" required autocomplete="new-password" />
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2 text-red-400" />
        </div>

        <!-- Confirm Password -->
        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-white/90 mb-1">Confirm Password</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-white/40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                </div>
                <input wire:model="password_confirmation" id="password_confirmation" type="password" class="block w-full pl-10 pr-3 py-2.5 border border-white/10 rounded-xl leading-5 bg-white/5 text-white placeholder-white/30 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 sm:text-sm transition-all duration-200" placeholder="••••••••" required autocomplete="new-password" />
            </div>
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2 text-red-400" />
        </div>

        @if($otp_sent)
            <div>
                <label for="otp" class="block text-sm font-medium text-white/90 mb-1">Email OTP</label>
                <input wire:model="otp" id="otp" type="text" inputmode="numeric" maxlength="6" class="block w-full px-3 py-2.5 border border-white/10 rounded-xl leading-5 bg-white/5 text-white placeholder-white/30 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 sm:text-sm transition-all duration-200" placeholder="123456" autocomplete="one-time-code" />
                <x-input-error :messages="$errors->get('otp')" class="mt-2 text-red-400" />
                <button type="button" wire:click="resendOtp" class="mt-2 text-sm font-medium text-brand-300 hover:text-brand-200">
                    Resend code
                </button>
            </div>
        @endif

        <div class="flex items-center justify-between mt-6">
            <a href="{{ route('login') }}" wire:navigate class="text-sm font-medium text-brand-300 hover:text-brand-200 transition-colors">
                Already registered?
            </a>

            <button type="submit" class="inline-flex justify-center py-2.5 px-6 border border-transparent rounded-xl shadow-[0_0_15px_rgba(59,130,246,0.3)] text-sm font-medium text-white bg-gradient-to-r from-brand-600 to-brand-500 hover:from-brand-500 hover:to-brand-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-900 focus:ring-brand-500 transition-all duration-300 transform hover:-translate-y-0.5">
                <span wire:loading.remove wire:target="register">{{ $otp_sent ? 'Verify & Create Account' : 'Send Email OTP' }}</span>
                <span wire:loading wire:target="register" class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    Processing...
                </span>
            </button>
        </div>
    </form>
</div>
