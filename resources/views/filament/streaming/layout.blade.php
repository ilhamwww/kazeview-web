<!DOCTYPE html>
<html lang="id" class="min-h-screen bg-[#050505]">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="referrer" content="no-referrer">
    <title>{{ $title ?? 'KZSTRM' }}</title>
    <!-- Tailwind CSS & Livewire/AlpineJS -->
    <script src="https://cdn.tailwindcss.com"></script>
    @livewireStyles
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: '#E50914',
                    }
                }
            }
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Caveat:wght@600;700&display=swap"
        rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .font-script {
            font-family: 'Caveat', cursive;
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #050505;
        }

        ::-webkit-scrollbar-thumb {
            background: #222;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #333;
        }
    </style>
    @stack('styles')
</head>

<body class="min-h-screen bg-[#050505] text-white antialiased" x-data="{ 
    searchOpen: false, 
    mobileMenuOpen: false,
    searchQuery: '', 
    searchResults: [], 
    searchLoading: false,
    searchDebounce: null,
    onSearchInput() {
        clearTimeout(this.searchDebounce);
        if (!this.searchQuery.trim()) {
            this.searchResults = [];
            return;
        }
        this.searchLoading = true;
        this.searchDebounce = setTimeout(() => {
            fetch('/api/search?q=' + encodeURIComponent(this.searchQuery))
                .then(res => res.json())
                .then(data => {
                    this.searchResults = data;
                    this.searchLoading = false;
                })
                .catch(() => {
                    this.searchLoading = false;
                });
        }, 1000); // 1 second delay to prevent rapid requests / rate limiting
    },
    clearSearch() {
        this.searchQuery = '';
        this.searchResults = [];
    }
}" @keydown.escape.window="searchOpen = false; mobileMenuOpen = false">
    @php
        $currentUrl = request()->url();
        $isBstation = str_contains($currentUrl, '/bstation');
        $isDracin = str_contains($currentUrl, '/dracin') || $isBstation;
        $isAnime = str_contains($currentUrl, '/anime');
        $isSeries = (str_contains($currentUrl, '/series') || str_contains($currentUrl, '/tv-series')) && !$isDracin;
        $isMovie = str_contains($currentUrl, '/movies') || (str_contains($currentUrl, '/movie/') && !$isDracin);
        $isHome = !$isSeries && !$isMovie && !$isDracin && !$isAnime;

        $activeClass = "flex items-center gap-1.5 text-white bg-red-600/80 border border-red-500/25 px-3 py-1 rounded-full text-xs font-bold tracking-wide transition";
        $inactiveClass = "flex items-center gap-1.5 text-gray-300 hover:text-white text-xs font-semibold tracking-wide transition";
    @endphp

    <!-- Centered Suspended Navbar Header -->
    <header class="fixed top-4 inset-x-0 z-50 flex justify-center px-4">
        <div
            class="w-full max-w-7xl h-14 bg-black/60 backdrop-blur-xl border border-white/10 rounded-full flex items-center justify-between px-6 shadow-[0_8px_32px_0_rgba(0,0,0,0.8)]">
            <!-- Logo -->
            <a href="{{ \App\Filament\Streaming\Pages\StreamingHome::getUrl() }}" class="flex items-center gap-2">
                <span class="text-red-600 font-extrabold text-2xl tracking-wider">KZSTRM</span>
            </a>

            <!-- Nav Links -->
            <nav class="hidden lg:flex items-center gap-6">
                <a href="{{ \App\Filament\Streaming\Pages\StreamingHome::getUrl() }}"
                    class="{{ $isHome ? $activeClass : $inactiveClass }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    Home
                </a>
                <a href="{{ \App\Filament\Streaming\Pages\Movies::getUrl() }}"
                    class="{{ $isMovie ? $activeClass : $inactiveClass }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 4h16a2 2 0 012 2v12a2 2 0 01-2 2H4a2 2 0 01-2-2V6a2 2 0 012-2z" />
                    </svg>
                    Movies
                </a>
                <a href="{{ \App\Filament\Streaming\Pages\TVSeries::getUrl() }}" class="{{ $isSeries ? $activeClass : $inactiveClass }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    TV Series
                </a>
                <a href="{{ \App\Filament\Streaming\Pages\Dracin::getUrl(panel: 'streaming') }}"
                    class="{{ $isDracin ? $activeClass : $inactiveClass }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 4h16a2 2 0 012 2v12a2 2 0 01-2 2H4a2 2 0 01-2-2V6a2 2 0 012-2z" />
                    </svg>
                    Dracin
                </a>
                <a href="{{ \App\Filament\Streaming\Pages\ExploreAnime::getUrl(panel: 'streaming') }}"
                    class="{{ $isAnime ? $activeClass : $inactiveClass }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" />
                    </svg>
                    Anime
                </a>
            </nav>

            <!-- Right Side -->
            <div class="flex items-center gap-3 lg:gap-4">
                <button @click="searchOpen = true; $nextTick(() => $refs.searchInput.focus())"
                    class="text-gray-300 hover:text-white transition focus:outline-none cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </button>
                <div class="relative hidden lg:block" x-data="{ open: false }" @click.away="open = false">
                    <!-- Trigger Button -->
                    <button @click="open = !open"
                        class="flex items-center gap-1.5 px-2.5 py-1.5 bg-white/10 hover:bg-white/20 transition rounded-full text-xs font-bold text-white border border-white/5 cursor-pointer focus:outline-none whitespace-nowrap">
                        <svg class="w-4 h-4 opacity-80 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        @if (auth()->check())
                            <span class="truncate max-w-[80px] inline-block">{{ auth()->user()->name }}</span>
                        @else
                            <span>Account</span>
                        @endif
                    </button>

                    <!-- Dropdown Box (Styled to match screenshot) -->
                    <div x-show="open" x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                        class="absolute right-0 mt-3 w-80 bg-[#111111] border border-zinc-800 rounded-2xl p-4 shadow-[0_10px_40px_rgba(0,0,0,0.9)] z-50 space-y-4"
                        style="display: none;">

                        @auth
                            <!-- Logged In User Info -->
                            <a href="{{ \App\Filament\Streaming\Pages\Profile::getUrl() }}">
                                <div class="flex items-center gap-3 bg-[#18181b] p-3 rounded-xl border border-white/5">
                                    <div
                                        class="w-10 h-10 rounded-full bg-zinc-800 border border-zinc-700 flex items-center justify-center text-white font-extrabold uppercase text-sm">
                                        {{ substr(auth()->user()->name, 0, 2) }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-bold text-white truncate">{{ auth()->user()->name }}</p>
                                        <p class="text-xs text-gray-400 truncate">{{ auth()->user()->email }}</p>
                                    </div>
                                </div>
                            </a>

                            <!-- Logged In Actions -->
                            <div class="space-y-2">
                                <!-- button clear cache -->
                                <form action="{{ route('clear.cache') }}" method="GET" class="block w-full">
                                    @csrf
                                    <button type="submit"
                                        class="w-full flex items-center gap-3 px-3.5 py-2.5 bg-[#18181b] border border-zinc-800 hover:bg-zinc-800 text-sm font-bold text-red-500 rounded-xl transition duration-200 text-left">
                                        <div
                                            class="w-8 h-8 rounded-lg bg-zinc-900/80 flex items-center justify-center flex-shrink-0">
                                            <svg class="w-5 text-red-500 h-5 text-white flex-shrink-0" fill="none"
                                                stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </div>
                                        <span class="text-red-500">Clear Cache</span>
                                    </button>
                                </form>


                                <form action="{{ route('filament.streaming.auth.logout') }}" method="POST"
                                    class="block w-full">
                                    @csrf
                                    <button type="submit"
                                        class="w-full flex items-center gap-3 px-3.5 py-2.5 bg-[#18181b] border border-zinc-800 hover:bg-zinc-800 text-sm font-bold text-red-500 rounded-xl transition duration-200 text-left">
                                        <div
                                            class="w-8 h-8 rounded-lg bg-zinc-900/80 flex items-center justify-center flex-shrink-0">
                                            <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="none"
                                                stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                            </svg>
                                        </div>
                                        <span class="text-red-500">Sign out</span>
                                    </button>
                                </form>
                            </div>
                        @else
                            <!-- Guest User Box -->
                            <div class="flex items-center gap-3 bg-[#18181b] p-3 rounded-xl border border-white/5">
                                <div
                                    class="w-10 h-10 rounded-full bg-zinc-800 border border-zinc-700 flex items-center justify-center text-zinc-400">
                                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0 text-left">
                                    <p class="text-sm font-bold text-white leading-tight">Account</p>
                                    <p class="text-xs text-gray-400 mt-0.5 leading-tight">Sign in to save your list.</p>
                                </div>
                            </div>

                            <!-- Guest Actions -->
                            <div class="space-y-2">
                                <a href="/streaming/login"
                                    class="flex items-center gap-3 px-3.5 py-2.5 bg-[#18181b] border border-zinc-800 hover:bg-zinc-800 text-sm font-bold text-white rounded-xl transition duration-200">
                                    <div
                                        class="w-8 h-8 rounded-lg bg-zinc-900/80 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-5 h-5 text-white flex-shrink-0 rotate-180 opacity-80" fill="none"
                                            stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                        </svg>
                                    </div>
                                    <span class="text-gray-200">Sign in</span>
                                </a>
                                <a href="/admin/register"
                                    class="flex items-center gap-3 px-3.5 py-2.5 bg-[#18181b] border border-zinc-800 hover:bg-zinc-800 text-sm font-bold text-red-500 rounded-xl transition duration-200">
                                    <div
                                        class="w-8 h-8 rounded-lg bg-zinc-900/80 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-5 h-5 text-red-500 flex-shrink-0 opacity-85" fill="none"
                                            stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                    </div>
                                    <span class="text-red-500">Sign up</span>
                                </a>
                            </div>
                        @endauth
                    </div>
                </div>
                <!-- Mobile Hamburger Button -->
                <button @click="mobileMenuOpen = !mobileMenuOpen" class="lg:hidden text-gray-300 hover:text-white transition focus:outline-none cursor-pointer p-1">
                    <svg x-show="!mobileMenuOpen" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    <svg x-show="mobileMenuOpen" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display:none;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </header>

    <!-- Mobile Menu Drawer -->
    <div x-show="mobileMenuOpen" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 z-40 bg-black/70 backdrop-blur-sm lg:hidden" @click="mobileMenuOpen = false" style="display:none;"></div>
    <div x-show="mobileMenuOpen" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full" class="fixed top-0 right-0 z-40 w-72 h-full bg-[#0a0a0a] border-l border-zinc-800 pt-24 pb-8 px-6 lg:hidden overflow-y-auto" style="display:none;">
        <nav class="flex flex-col gap-2">
            <a href="{{ \App\Filament\Streaming\Pages\StreamingHome::getUrl() }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all duration-200 {{ $isHome ? 'bg-red-600/15 text-red-500 border border-red-500/20' : 'text-zinc-300 hover:bg-zinc-900 hover:text-white' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
                Home
            </a>
            <a href="{{ \App\Filament\Streaming\Pages\Movies::getUrl() }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all duration-200 {{ $isMovie ? 'bg-red-600/15 text-red-500 border border-red-500/20' : 'text-zinc-300 hover:bg-zinc-900 hover:text-white' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 4h16a2 2 0 012 2v12a2 2 0 01-2 2H4a2 2 0 01-2-2V6a2 2 0 012-2z" /></svg>
                Movies
            </a>
            <a href="{{ \App\Filament\Streaming\Pages\TVSeries::getUrl() }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all duration-200 {{ $isSeries ? 'bg-red-600/15 text-red-500 border border-red-500/20' : 'text-zinc-300 hover:bg-zinc-900 hover:text-white' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                TV Series
            </a>
            <a href="{{ \App\Filament\Streaming\Pages\Dracin::getUrl(panel: 'streaming') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all duration-200 {{ $isDracin ? 'bg-red-600/15 text-red-500 border border-red-500/20' : 'text-zinc-300 hover:bg-zinc-900 hover:text-white' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 4h16a2 2 0 012 2v12a2 2 0 01-2 2H4a2 2 0 01-2-2V6a2 2 0 012-2z" /></svg>
                Dracin
            </a>
            <a href="{{ \App\Filament\Streaming\Pages\ExploreAnime::getUrl(panel: 'streaming') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all duration-200 {{ $isAnime ? 'bg-red-600/15 text-red-500 border border-red-500/20' : 'text-zinc-300 hover:bg-zinc-900 hover:text-white' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" /></svg>
                Anime
            </a>
        </nav>

        <!-- Mobile Menu Divider -->
        <hr class="border-zinc-800 my-5">

        <!-- Mobile Account Section -->
        <div class="space-y-2">
            @auth
                <a href="{{ \App\Filament\Streaming\Pages\Profile::getUrl() }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold text-zinc-300 hover:bg-zinc-900 hover:text-white transition-all duration-200">
                    <div class="w-8 h-8 rounded-full bg-zinc-800 border border-zinc-700 flex items-center justify-center text-white font-extrabold uppercase text-xs">{{ substr(auth()->user()->name, 0, 2) }}</div>
                    <span>{{ auth()->user()->name }}</span>
                </a>
                <form action="{{ route('clear.cache') }}" method="GET">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold text-red-500 hover:bg-zinc-900 hover:text-red-400 transition-all duration-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                        Clear Cache
                    </button>
                </form>
                <form action="{{ route('filament.streaming.auth.logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold text-red-500 hover:bg-zinc-900 hover:text-red-400 transition-all duration-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                        Sign out
                    </button>
                </form>
            @else
                <a href="/streaming/login" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold text-zinc-300 hover:bg-zinc-900 hover:text-white transition-all duration-200">
                    <svg class="w-5 h-5 rotate-180 opacity-80" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                    <span>Sign in</span>
                </a>
                <a href="/admin/register" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold text-red-500 hover:bg-zinc-900 hover:text-red-400 transition-all duration-200">
                    <svg class="w-5 h-5 opacity-85" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                    Sign up
                </a>
            @endauth
        </div>
    </div>

    @auth
        @if(auth()->user()->isSubscriptionExpired())
            <!-- Subscription Expired Modal -->
            <div x-data="{ showExpModal: true }" x-show="showExpModal" class="fixed inset-0 z-[200] flex items-center justify-center p-4 bg-black/80 backdrop-blur-md" style="display:none;" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" x-init="showExpModal = true">
                <div class="w-full max-w-md bg-[#111] border border-zinc-800 rounded-3xl p-8 shadow-2xl text-center space-y-6" x-transition:enter="transition ease-out duration-300 delay-100" x-transition:enter-start="opacity-0 scale-90" x-transition:enter-end="opacity-100 scale-100">
                    <!-- Icon -->
                    <div class="mx-auto w-16 h-16 bg-red-600/15 border border-red-500/25 rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                    </div>

                    <!-- Title -->
                    <div>
                        <h2 class="text-xl font-extrabold text-white tracking-tight">Langganan Berakhir</h2>
                        <p class="text-sm text-zinc-400 mt-2 leading-relaxed">
                            Langganan Anda telah berakhir pada
                            <span class="text-red-500 font-semibold">{{ auth()->user()->subscription_expires_at->translatedFormat('d F Y, H:i') }}</span>.
                            Silakan hubungi admin untuk memperpanjang akses Anda.
                        </p>
                    </div>

                    <!-- Info Box -->
                    <div class="bg-zinc-900/80 border border-zinc-800 rounded-xl p-4 text-left space-y-2">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-zinc-500">Status</span>
                            <span class="text-red-500 font-bold">Expired</span>
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-zinc-500">Berakhir</span>
                            <span class="text-zinc-300 font-semibold">{{ auth()->user()->subscription_expires_at->translatedFormat('d M Y') }}</span>
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-zinc-500">Akun</span>
                            <span class="text-zinc-300 font-semibold">{{ auth()->user()->email }}</span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="pt-2">
                        <a href="/admin" class="w-full py-2.5 bg-zinc-800 hover:bg-zinc-700 text-white rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5 border border-zinc-700">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 15l-3-3m0 0l3-3m-3 3h8M3 12a9 9 0 1118 0 9 9 0 01-18 0z" />
                            </svg>
                            Kembali ke Admin Panel
                        </a>
                    </div>
                </div>
            </div>
        @endif
    @endauth

    {{ $slot }}

    <!-- Search Modal Overlay -->
    <div x-show="searchOpen"
        class="fixed inset-0 z-[100] flex items-start justify-center pt-24 px-4 bg-black/75 backdrop-blur-sm"
        style="display: none;" x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0">

        <div @click.away="searchOpen = false"
            class="w-full max-w-xl bg-zinc-950/95 backdrop-blur-xl rounded-2xl border border-white/10 shadow-2xl shadow-black/40 overflow-hidden"
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">

            <!-- Header Search Input -->
            <div class="flex items-center gap-3 px-5 py-4 border-b border-white/10">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    color="currentColor" class="w-5 h-5 text-zinc-400 flex-shrink-0" stroke-width="1.5"
                    stroke="currentColor">
                    <path d="M17 17L21 21" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                        stroke-width="1.5"></path>
                    <path
                        d="M19 11C19 6.58172 15.4183 3 11 3C6.58172 3 3 6.58172 3 11C3 15.4183 6.58172 19 11 19C15.4183 19 19 15.4183 19 11Z"
                        stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"></path>
                </svg>
                <input x-model="searchQuery" @input="onSearchInput" x-ref="searchInput"
                    placeholder="Search movies or tv series..."
                    class="flex-1 bg-transparent text-base text-white placeholder:text-zinc-500 focus:outline-none"
                    autocomplete="off" spellcheck="false" type="text">

                <!-- Clear Button -->
                <button @click="clearSearch()" x-show="searchQuery"
                    class="p-1 text-zinc-400 hover:text-white transition rounded-full hover:bg-white/5">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                        color="currentColor" class="w-4 h-4" stroke-width="1.5" stroke="currentColor">
                        <path d="M18 6L6.00081 17.9992M17.9992 18L6 6.00085" stroke="currentColor"
                            stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"></path>
                    </svg>
                </button>

                <div class="hidden sm:flex items-center">
                    <kbd
                        class="text-xs text-zinc-400 bg-zinc-900 px-1.5 py-0.5 rounded border border-white/5 font-mono">ESC</kbd>
                </div>
            </div>

            <!-- Results Scroll Area -->
            <div class="max-h-[60vh] overflow-y-auto">
                <!-- Search Loading State -->
                <div x-show="searchLoading" class="py-12 flex items-center justify-center gap-2 text-zinc-400">
                    <svg class="animate-spin h-5 w-5 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                        </circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>
                    <span>Searching...</span>
                </div>

                <!-- Empty Results State -->
                <div x-show="searchResults.length === 0 && searchQuery.length > 0 && !searchLoading"
                    class="py-12 text-center text-zinc-500">
                    No results found for "<span class="text-zinc-300 font-semibold" x-text="searchQuery"></span>"
                </div>

                <!-- Default State (when no search query has been typed) -->
                <div x-show="searchQuery.length === 0" class="py-12 text-center text-zinc-500">
                    Type something to search...
                </div>

                <!-- Results list -->
                <div class="py-2" x-show="searchResults.length > 0 && !searchLoading">
                    <template x-for="item in searchResults" :key="item.id">
                        <a :href="item.url"
                            class="w-full flex items-center gap-3 px-5 py-3 text-left transition hover:bg-white/5">
                            <div
                                class="w-10 h-14 rounded-lg overflow-hidden bg-zinc-900 flex-shrink-0 border border-white/5">
                                <template x-if="item.thumbnail">
                                    <img :alt="item.title" class="w-full h-full object-cover" :src="item.thumbnail">
                                </template>
                                <template x-if="!item.thumbnail">
                                    <div
                                        class="w-full h-full flex items-center justify-center text-[10px] text-zinc-600 bg-zinc-800">
                                        No Image</div>
                                </template>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-medium text-white truncate"
                                        x-text="item.title + (item.year ? ' (' + item.year + ')' : '')"></span>
                                </div>
                                <div class="flex items-center gap-2 mt-0.5">
                                    <span class="text-xs text-zinc-400 bg-zinc-900 px-1.5 py-0.5 rounded capitalize"
                                        x-text="item.type ? item.type.replace('_', ' ') : 'Movie'"></span>
                                    <template x-if="item.rating && item.rating !== '0.0'">
                                        <span class="flex items-center gap-0.5 text-xs text-yellow-400">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                viewBox="0 0 24 24" fill="currentColor" color="currentColor"
                                                class="w-3 h-3" stroke-width="1.5" stroke="currentColor">
                                                <path
                                                    d="M13.7276 3.44418L15.4874 6.99288C15.7274 7.48687 16.3673 7.9607 16.9073 8.05143L20.0969 8.58575C22.1367 8.92853 22.6167 10.4206 21.1468 11.8925L18.6671 14.3927C18.2471 14.8161 18.0172 15.6327 18.1471 16.2175L18.8571 19.3125C19.417 21.7623 18.1271 22.71 15.9774 21.4296L12.9877 19.6452C12.4478 19.3226 11.5579 19.3226 11.0079 19.6452L8.01827 21.4296C5.8785 22.71 4.57865 21.7522 5.13859 19.3125L5.84851 16.2175C5.97849 15.6327 5.74852 14.8161 5.32856 14.3927L2.84884 11.8925C1.389 10.4206 1.85895 8.92853 3.89872 8.58575L7.08837 8.05143C7.61831 7.9607 8.25824 7.48687 8.49821 6.99288L10.258 3.44418C11.2179 1.51861 12.7777 1.51861 13.7276 3.44418Z"
                                                    stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                                    stroke-width="1.5"></path>
                                            </svg>
                                            <span x-text="item.rating"></span>
                                        </span>
                                    </template>
                                </div>
                            </div>
                        </a>
                    </template>
                </div>
            </div>

            <!-- Footer view all results -->
            <div class="border-t border-white/10 px-5 py-3" x-show="searchResults.length > 0 && !searchLoading">
                <button class="text-sm text-red-500 hover:text-red-400 font-bold hover:underline"
                    @click="searchOpen = false">Close Results</button>
            </div>
        </div>
    </div>

    @livewireScripts
    @stack('scripts')
</body>

</html>