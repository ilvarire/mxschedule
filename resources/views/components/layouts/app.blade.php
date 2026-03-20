<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title ?? 'MXSchedule' }} — Exam Scheduling</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="font-sans antialiased bg-gray-50 text-gray-900">
        <div class="min-h-screen flex">
            <!-- Sidebar -->
            <aside class="hidden lg:flex lg:flex-col w-64 bg-gray-900 min-h-screen fixed inset-y-0 z-30">
                <!-- Logo -->
                <div class="flex items-center gap-3 px-6 py-5 border-b border-white/10">
                    <div class="w-9 h-9 rounded-lg bg-indigo-600 flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-white font-bold text-lg leading-none">MXSchedule</h1>
                        <p class="text-gray-500 text-xs mt-0.5">Exam Management</p>
                    </div>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 px-4 py-6 space-y-1 overflow-y-auto">
                    @role('super_admin|exam_officer|ict_admin')
                    <a href="{{ route('admin.dashboard') }}" class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                        Dashboard
                    </a>
                    @endrole

                    @role('super_admin|ict_admin')
                    <p class="px-3 pt-5 pb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Infrastructure</p>
                    <a href="{{ route('admin.halls.index') }}" class="sidebar-link {{ request()->routeIs('admin.halls.*') ? 'active' : '' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        Halls
                    </a>
                    <a href="{{ route('admin.systems.index') }}" class="sidebar-link {{ request()->routeIs('admin.systems.*') ? 'active' : '' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        Systems
                    </a>
                    @endrole

                    @role('super_admin|exam_officer')
                    <p class="px-3 pt-5 pb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Examinations</p>
                    <a href="{{ route('admin.exams.index') }}" class="sidebar-link {{ request()->routeIs('admin.exams.*') ? 'active' : '' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Exams
                    </a>
                    <a href="{{ route('admin.reports.show', 'attendance') }}" class="sidebar-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        Reports
                    </a>
                    <a href="{{ route('admin.import.index') }}" class="sidebar-link {{ request()->routeIs('admin.import.*') ? 'active' : '' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        Import
                    </a>
                    @endrole

                    @role('super_admin')
                    <p class="px-3 pt-5 pb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Administration</p>
                    <a href="{{ route('admin.users.index') }}" class="sidebar-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        Users
                    </a>
                    @endrole
                </nav>

                <!-- User -->
                <div class="px-4 py-4 border-t border-white/10">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-indigo-600 flex items-center justify-center text-white text-sm font-bold">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-white truncate">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-gray-500 truncate">{{ auth()->user()->roles->first()?->name ?? 'User' }}</p>
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="text-gray-500 hover:text-white transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                            </button>
                        </form>
                    </div>
                </div>
            </aside>

            <!-- Main Content -->
            <div class="flex-1 lg:ml-64">
            <!-- Mobile Header -->
            <header class="lg:hidden bg-white border-b border-gray-200 px-4 py-3 flex items-center justify-between sticky top-0 z-20" x-data="{ mobileMenuOpen: false }">
                <div class="flex items-center gap-3">
                    <button @click="mobileMenuOpen = true" class="p-2 -ml-2 text-gray-500 hover:text-indigo-600 lg:hidden">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                        </div>
                        <span class="font-bold text-gray-900">MXSchedule</span>
                    </div>
                </div>

                {{-- Mobile Side Overlay --}}
                <template x-if="true">
                    <div x-show="mobileMenuOpen" 
                         class="fixed inset-0 z-50 lg:hidden" 
                         @click.away="mobileMenuOpen = false">
                        <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm" x-show="mobileMenuOpen" x-transition:enter="transition-opacity ease-linear duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-linear duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>
                        
                        <div class="fixed inset-y-0 left-0 w-64 bg-gray-900 shadow-2xl flex flex-col" x-show="mobileMenuOpen" x-transition:enter="transition ease-in-out duration-300 transform" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in-out duration-300 transform" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full">
                            <div class="flex items-center justify-between px-6 py-5 border-b border-white/10">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center">
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                    </div>
                                    <span class="font-bold text-white text-lg">MXSchedule</span>
                                </div>
                                <button @click="mobileMenuOpen = false" class="text-gray-400 hover:text-white">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                            
                            {{-- We could extract the nav to a partial but for now we'll just replicate it or use a blade component --}}
                            <div class="flex-1 px-4 py-6 space-y-1 overflow-y-auto">
                                {{-- Sidebar content simplified for mobile --}}
                                @role('super_admin|exam_officer|ict_admin')
                                <a href="{{ route('admin.dashboard') }}" class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">Dashboard</a>
                                @endrole

                                @role('super_admin|ict_admin')
                                <p class="px-3 pt-4 pb-1 text-[10px] font-bold text-gray-600 uppercase">Infrastructure</p>
                                <a href="{{ route('admin.halls.index') }}" class="sidebar-link {{ request()->routeIs('admin.halls.*') ? 'active' : '' }}">Halls</a>
                                <a href="{{ route('admin.systems.index') }}" class="sidebar-link {{ request()->routeIs('admin.systems.*') ? 'active' : '' }}">Systems</a>
                                @endrole

                                @role('super_admin|exam_officer')
                                <p class="px-3 pt-4 pb-1 text-[10px] font-bold text-gray-600 uppercase">Examinations</p>
                                <a href="{{ route('admin.exams.index') }}" class="sidebar-link {{ request()->routeIs('admin.exams.*') ? 'active' : '' }}">Exams</a>
                                <a href="{{ route('admin.reports.show', 'attendance') }}" class="sidebar-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">Reports</a>
                                <a href="{{ route('admin.import.index') }}" class="sidebar-link {{ request()->routeIs('admin.import.*') ? 'active' : '' }}">Import</a>
                                @endrole
                            </div>
                        </div>
                    </div>
                </template>

                <a href="{{ route('profile') }}" class="text-gray-500 hover:text-indigo-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </a>
            </header>

                <!-- Flash Messages -->
                <div class="px-4 sm:px-6 lg:px-8 pt-4">
                    @if(session('success'))
                        <div class="flash-success mb-4" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" x-transition>
                            {{ session('success') }}
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="flash-error mb-4" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" x-transition>
                            {{ session('error') }}
                        </div>
                    @endif
                </div>

                <!-- Page Header -->
                @if (isset($header))
                <div class="px-4 sm:px-6 lg:px-8 pt-6 pb-2">
                    {{ $header }}
                </div>
                @endif

                <!-- Page Content -->
                <main class="px-4 sm:px-6 lg:px-8 py-6">
                    {{ $slot }}
                </main>
            </div>
        </div>
        @livewireScripts
    </body>
</html>
