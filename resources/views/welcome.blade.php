<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="MXSchedule — The smart university exam scheduling platform. Automate CBT hall allocation, session scheduling, and attendance validation with secure QR technology.">
        <title>MXSchedule - Smart University Exam Platform</title>
        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=outfit:300,400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Styles -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-900 text-white min-h-screen relative overflow-x-hidden selection:bg-brand-500 selection:text-white">
        
        <!-- Background Layer -->
        <div class="fixed inset-0 z-0">
            <img src="{{ asset('images/auth-bg.png') }}" alt="Background" class="w-full h-full object-cover opacity-60 mix-blend-screen" />
            <div class="absolute inset-0 bg-gradient-to-br from-gray-900/95 via-brand-950/80 to-gray-900/90"></div>
        </div>

        <!-- Navigation -->
        <nav class="relative z-50 py-6 px-6 lg:px-12 flex justify-between items-center animate-fade-in-up">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-white/10 flex items-center justify-center backdrop-blur-md border border-white/20 shadow-lg">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                </div>
                <span class="text-2xl font-bold tracking-tight">MX<span class="text-brand-400">Schedule</span></span>
            </div>

            <div class="flex items-center gap-4">
                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('/dashboard') }}" class="font-medium text-white hover:text-brand-300 transition px-4 py-2 rounded-lg bg-white/5 hover:bg-white/10 border border-transparent hover:border-white/10">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="font-medium text-white hover:text-brand-300 transition px-4 py-2">Sign in</a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="font-medium text-white bg-brand-600 hover:bg-brand-500 px-5 py-2.5 rounded-xl shadow-lg shadow-brand-500/30 transition-all hover:-translate-y-0.5">Register</a>
                        @endif
                    @endauth
                @endif
            </div>
        </nav>

        <!-- Main Content -->
        <main class="relative z-10 flex flex-col justify-center min-h-[80vh] px-6 lg:px-12">
            <div class="max-w-4xl mx-auto text-center space-y-8 animate-fade-in-up" style="animation-delay: 0.1s;">
                
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/5 border border-white/10 backdrop-blur-md mb-4 text-brand-300 text-sm font-medium">
                    <span class="flex h-2 w-2 relative">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-brand-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-brand-500"></span>
                    </span>
                    Smart University Scheduling Engine 2.0
                </div>

                <h1 class="text-5xl md:text-7xl font-extrabold tracking-tight leading-tight">
                    Flawless Exam Execution,<br />
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-brand-300 to-purple-400">Zero Complications.</span>
                </h1>
                
                <p class="text-lg md:text-xl text-gray-300 max-w-2xl mx-auto leading-relaxed">
                    The next-generation platform for universities to seamlessly allocate CBT halls, schedule exam sessions, and validate student attendance using secure QR technology.
                </p>

                <div class="flex flex-col sm:flex-row items-center justify-center gap-4 pt-4">
                    <a href="{{ route('login') }}" class="w-full sm:w-auto flex items-center justify-center gap-2 px-8 py-4 rounded-xl bg-gradient-to-r from-brand-600 to-brand-500 text-white font-semibold shadow-[0_0_20px_rgba(59,130,246,0.4)] transition-all hover:shadow-[0_0_30px_rgba(59,130,246,0.6)] hover:-translate-y-1">
                        Access Portal
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg>
                    </a>
                </div>
            </div>

            <!-- Floating Abstract UI Elements (Decorative) -->
            <div class="absolute top-1/4 left-10 w-32 h-32 bg-brand-500/20 rounded-full blur-3xl animate-blob"></div>
            <div class="absolute top-1/3 right-20 w-48 h-48 bg-purple-500/20 rounded-full blur-3xl animate-blob" style="animation-delay: 2s;"></div>
            <div class="absolute bottom-20 left-1/4 w-40 h-40 bg-blue-600/20 rounded-full blur-3xl animate-blob" style="animation-delay: 4s;"></div>

            <!-- Stats/Features Bar -->
            <div class="max-w-5xl mx-auto mt-24 grid grid-cols-1 md:grid-cols-3 gap-6 animate-fade-in-up" style="animation-delay: 0.3s;">
                <div class="bg-white/5 backdrop-blur-lg border border-white/10 rounded-2xl p-6 hover:bg-white/10 transition">
                    <div class="w-12 h-12 bg-brand-500/20 rounded-xl flex items-center justify-center mb-4 text-brand-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Automated Allocation</h3>
                    <p class="text-gray-400 text-sm">Intelligent seating and session generation based on system capacity.</p>
                </div>
                <div class="bg-white/5 backdrop-blur-lg border border-white/10 rounded-2xl p-6 hover:bg-white/10 transition">
                    <div class="w-12 h-12 bg-purple-500/20 rounded-xl flex items-center justify-center mb-4 text-purple-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">QR Validation</h3>
                    <p class="text-gray-400 text-sm">Secure, offline-capable QR code scanning for instantaneous attendance.</p>
                </div>
                <div class="bg-white/5 backdrop-blur-lg border border-white/10 rounded-2xl p-6 hover:bg-white/10 transition">
                    <div class="w-12 h-12 bg-blue-500/20 rounded-xl flex items-center justify-center mb-4 text-blue-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Live Analytics</h3>
                    <p class="text-gray-400 text-sm">Real-time dashboards for monitoring exam progress and center health.</p>
                </div>
            </div>
        </main>

    </body>
</html>
