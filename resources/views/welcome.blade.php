<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('National University College Directory') }}</title>

    <link rel="icon" href="/favicon.ico" sizes="any">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">


    <!-- যদি আপনি Vite ব্যবহার করেন, তবে নিচের লাইনটি আনকমেন্ট করুন এবং CDN মুছে দিন -->
    <!-- @vite(['resources/css/app.css', 'resources/js/app.js']) -->

    <!-- Tailwind CSS (Development CDN) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                }
            }
        }
    </script>

    <!-- Dark Mode Init Script (Prevents FOUC) -->
    <script>
        if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>

    <style>
        /* Custom scrollbar for an elegant look */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        .dark ::-webkit-scrollbar-thumb { background: #3f3f46; }
        .dark ::-webkit-scrollbar-thumb:hover { background: #52525b; }
    </style>
</head>

<body class="font-sans antialiased bg-zinc-50 dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 min-h-screen flex flex-col selection:bg-indigo-500/30 transition-colors duration-300">

<!-- Ambient Background Glow -->
<div class="fixed inset-0 -z-10 overflow-hidden pointer-events-none">
    <div class="absolute -top-[200px] left-1/2 -translate-x-1/2 w-full max-w-5xl h-[500px] bg-gradient-to-b from-indigo-500/15 via-purple-500/5 to-transparent dark:from-indigo-500/10 dark:via-purple-500/5 dark:to-transparent blur-3xl rounded-full"></div>
</div>

<!-- Professional Flux Header -->
<flux:header container class="border-b border-zinc-200/80 dark:border-zinc-800/80 bg-white/70 dark:bg-zinc-900/70 backdrop-blur-xl sticky top-0 z-50 transition-colors">
    <flux:brand href="/" class="gap-3">
        <!-- Brand Logo -->
        <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-600 to-violet-600 text-white font-bold shadow-lg shadow-indigo-500/30">
            NU
        </div>
        <!-- Brand Name -->
        <div class="hidden sm:flex flex-col">
                <span class="font-extrabold text-lg leading-tight tracking-tight text-zinc-900 dark:text-white">
                    College<span class="text-indigo-600 dark:text-indigo-400">Directory</span>
                </span>
            <span class="text-[10px] font-semibold text-zinc-500 uppercase tracking-widest leading-none mt-0.5">
                    National University
                </span>
        </div>
    </flux:brand>

    <flux:spacer />

    <flux:navbar class="gap-2 sm:gap-4">
        <!-- Theme Toggle Button -->
        <button id="theme-toggle" type="button" class="flex items-center justify-center w-9 h-9 rounded-lg text-zinc-500 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-all focus:outline-none focus:ring-2 focus:ring-indigo-500/50">
            <svg id="theme-toggle-dark-icon" class="hidden w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
            <svg id="theme-toggle-light-icon" class="hidden w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
        </button>

        <!-- Divider -->
        <div class="h-5 w-px bg-zinc-200 dark:bg-zinc-800 hidden sm:block"></div>

        <!-- Authentication Links -->
        @if (Route::has('login'))
            @auth
                <flux:button href="{{ route('dashboard') }}" variant="ghost" class="hidden sm:flex text-zinc-600 dark:text-zinc-300">
                    Dashboard
                </flux:button>
            @else
                <flux:button href="{{ route('login') }}" variant="ghost" class="hidden sm:flex text-zinc-600 dark:text-zinc-300">
                    Log in
                </flux:button>
                @if (Route::has('register'))
                    <flux:button href="{{ route('register') }}" variant="primary" class="shadow-sm shadow-indigo-500/20">
                        Register
                    </flux:button>
                @endif
            @endauth
        @endif
    </flux:navbar>
</flux:header>

<!-- Main Content Area -->
<flux:main container class="flex-1 py-12 lg:py-20 relative z-10">

    <!-- Hero Section -->
    <div class="flex flex-col items-center text-center max-w-3xl mx-auto mb-14 sm:mb-20">
        <!-- Badge -->
        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-indigo-50 dark:bg-indigo-500/10 border border-indigo-200/50 dark:border-indigo-500/20 text-indigo-700 dark:text-indigo-300 text-sm font-semibold mb-6 shadow-sm">
                <span class="relative flex h-2 w-2">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-2 w-2 bg-indigo-500"></span>
                </span>
            Updated Directory {{ date('Y') }}
        </div>

        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-zinc-900 dark:text-white tracking-tight leading-[1.15]">
            Explore Affiliated <br class="hidden sm:block">
            <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-violet-500">Colleges & Institutions</span>
        </h1>

        <p class="mt-6 text-lg sm:text-xl text-zinc-600 dark:text-zinc-400 leading-relaxed max-w-2xl">
            Comprehensive directory of National University affiliated colleges. Search by code or name to view detailed contact information, available programs, and seat capacities.
        </p>
    </div>

    <!-- Livewire Component Container -->
    <div class="relative w-full max-w-6xl mx-auto">
        <!-- Background Glow for Card -->
        <div class="absolute -inset-1 bg-gradient-to-r from-indigo-500 to-violet-500 rounded-[2rem] blur-xl opacity-10 dark:opacity-20 pointer-events-none"></div>

        <!-- Glassmorphic Card Wrapper -->
        <div class="relative bg-white dark:bg-[#121214] rounded-3xl shadow-xl shadow-zinc-200/50 dark:shadow-black/50 border border-zinc-200/80 dark:border-zinc-800/80 overflow-hidden p-4 sm:p-6 lg:p-8 transition-colors">
            <livewire:college-list />
        </div>
    </div>

</flux:main>

<!-- Minimalist Footer -->
<flux:footer container class="border-t border-zinc-200 dark:border-zinc-800/80 bg-white/50 dark:bg-zinc-950/50 backdrop-blur-md mt-auto py-8">
    <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
        <div class="flex items-center gap-2">
            <div class="w-6 h-6 rounded-md bg-indigo-600 flex items-center justify-center text-white text-[10px] font-bold">
                NU
            </div>
            <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                &copy; {{ date('Y') }} National University Directory. All rights reserved.
            </p>
        </div>

        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5">
            Crafted with
            <span class="flex items-center gap-1 text-zinc-800 dark:text-zinc-200 font-semibold">
                    <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 24 24"><path d="M11.953 2C6.465 2 2 6.486 2 12s4.486 10 10 10 10-4.486 10-10S17.493 2 11.953 2zM12 20c-4.411 0-8-3.589-8-8s3.589-8 8-8 8 3.589 8 8-3.589 8-8 8zm3.626-11.758c-1.396 0-2.61.947-3.056 2.274h-1.14c-.446-1.327-1.66-2.274-3.056-2.274-1.782 0-3.23 1.455-3.23 3.25 0 2.502 2.656 4.965 6.136 7.644l.72.553.72-.552c3.48-2.679 6.136-5.142 6.136-7.645 0-1.795-1.448-3.25-3.23-3.25z"></path></svg>
                    Laravel & Flux
                </span>
        </p>
    </div>
</flux:footer>

@livewireScripts
@fluxScripts

<!-- Dark Mode Toggle Script -->
<script>
    const darkIcon = document.getElementById('theme-toggle-dark-icon');
    const lightIcon = document.getElementById('theme-toggle-light-icon');
    const themeBtn = document.getElementById('theme-toggle');

    // Initial icon state setup
    if (document.documentElement.classList.contains('dark')) {
        lightIcon.classList.remove('hidden');
    } else {
        darkIcon.classList.remove('hidden');
    }

    themeBtn.addEventListener('click', function() {
        darkIcon.classList.toggle('hidden');
        lightIcon.classList.toggle('hidden');

        if (document.documentElement.classList.contains('dark')) {
            document.documentElement.classList.remove('dark');
            localStorage.setItem('color-theme', 'light');
        } else {
            document.documentElement.classList.add('dark');
            localStorage.setItem('color-theme', 'dark');
        }

        // Trigger an event for Livewire/Flux to react if needed
        window.dispatchEvent(new Event('theme-changed'));
    });
</script>
</body>
</html>
