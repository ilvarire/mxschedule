<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'SmartExam Platform') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=outfit:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased relative bg-gray-900 min-h-screen selection:bg-brand-500 selection:text-white">
        <!-- Background Image & Overlay -->
        <div class="fixed inset-0 z-0">
            <img src="{{ asset('images/auth-bg.png') }}" alt="Background" class="w-full h-full object-cover opacity-60 mix-blend-screen" />
            <div class="absolute inset-0 bg-gradient-to-br from-brand-950/80 via-brand-900/60 to-gray-900/90"></div>
        </div>

        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 relative z-10 px-4">
            <div class="animate-fade-in-up">
                <a href="/" wire:navigate class="flex items-center gap-3 transition-transform hover:scale-105 duration-300">
                    <div class="w-14 h-14 rounded-2xl bg-white/10 flex items-center justify-center backdrop-blur-md border border-white/20 shadow-xl relative overflow-hidden">
                        <div class="absolute inset-0 bg-gradient-to-br from-white/20 to-transparent opacity-50"></div>
                        <svg class="w-8 h-8 text-white relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                    </div>
                    <span class="text-3xl font-bold text-white tracking-tight drop-shadow-sm">Smart<span class="text-brand-300">Exam</span></span>
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-8 px-8 py-10 bg-white/10 backdrop-blur-xl shadow-2xl overflow-hidden sm:rounded-3xl border border-white/20 animate-fade-in-up" style="animation-fill-mode: both; animation-delay: 0.15s;">
                {{ $slot }}
            </div>
            
            <div class="mt-8 text-center text-sm text-gray-400 animate-fade-in-up" style="animation-fill-mode: both; animation-delay: 0.3s;">
                &copy; {{ date('Y') }} MXSchedule Platform. All rights reserved.
            </div>
        </div>
    </body>
</html>
