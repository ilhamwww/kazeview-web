<!DOCTYPE html>
<html lang="id" class="min-h-screen bg-[#050505]">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="referrer" content="no-referrer">
    <title>{{ $title ?? 'KZSTRM' }}</title>

    <!-- Progressive Web App (PWA) Meta & Manifest -->
    <link rel="manifest" href="/manifest.json?v=20261013a">
    <meta name="theme-color" content="#050505">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="KZSTRM">
    <link rel="apple-touch-icon" href="/icons/kaze-apple-touch-180.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/icons/kaze-apple-touch-180.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/icons/kaze-favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/icons/kaze-favicon-16.png">

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

        /* Top Navigation Progress Bar for PWA & Fast Feedback */
        #pwa-top-loader {
            position: fixed;
            top: 0;
            left: 0;
            width: 0%;
            height: 3px;
            background: linear-gradient(90deg, #b91c1c, #E50914, #ff4d4d);
            box-shadow: 0 0 10px rgba(229, 9, 20, 0.9), 0 0 5px rgba(255, 77, 77, 0.8);
            z-index: 9999999;
            pointer-events: none;
            opacity: 0;
            transition: width 0.22s cubic-bezier(0.1, 0.9, 0.2, 1), opacity 0.2s ease;
        }

        #pwa-top-loader.is-loading {
            opacity: 1;
        }

        #pwa-top-loader.is-complete {
            width: 100% !important;
            opacity: 0;
            transition: width 0.15s ease, opacity 0.35s ease 0.1s;
        }

        #pwa-top-loader::after {
            content: '';
            position: absolute;
            right: 0;
            top: -2px;
            width: 25px;
            height: 7px;
            background: rgba(255, 255, 255, 0.6);
            box-shadow: 0 0 12px #ff4d4d, 0 0 6px #E50914;
            border-radius: 50%;
            opacity: 1;
        }
    </style>
    @stack('styles')
</head>

<body class="min-h-screen bg-[#050505] text-white antialiased" x-data="{ 
    searchOpen: false, 
    mobileMenuOpen: false,
    searchQuery: '', 
    searchResults: [], 
    searchCategory: 'all',
    searchLoading: false,
    searchDebounce: null,
    setSearchCategory(cat) {
        this.searchCategory = cat;
        if (this.searchQuery.trim()) {
            this.executeSearch();
        }
    },
    onSearchInput() {
        clearTimeout(this.searchDebounce);
        if (!this.searchQuery.trim()) {
            this.searchResults = [];
            this.searchLoading = false;
            return;
        }
        this.searchLoading = true;
        this.searchDebounce = setTimeout(() => {
            this.executeSearch();
        }, 400);
    },
    executeSearch() {
        if (!this.searchQuery.trim()) {
            this.searchResults = [];
            this.searchLoading = false;
            return;
        }
        this.searchLoading = true;
        fetch('/api/search?q=' + encodeURIComponent(this.searchQuery) + '&category=' + encodeURIComponent(this.searchCategory))
            .then(res => res.json())
            .then(data => {
                this.searchResults = Array.isArray(data) ? data : [];
                this.searchLoading = false;
            })
            .catch(() => {
                this.searchLoading = false;
            });
    },
    clearSearch() {
        this.searchQuery = '';
        this.searchResults = [];
        this.searchLoading = false;
    }
}" @keydown.escape.window="searchOpen = false; mobileMenuOpen = false">
    @php
        $currentUrl = request()->url();
        $isBstation = str_contains($currentUrl, '/bstation');
        $isDracin = str_contains($currentUrl, '/dracin') || $isBstation;
        $isAnime = str_contains($currentUrl, '/anime');
        $isCollection = str_contains($currentUrl, '/collection');
        $isNetwork = str_contains($currentUrl, '/network');
        $isIndonesia = str_contains($currentUrl, '/indonesia');
        $isDrakor = str_contains($currentUrl, '/korean-drama') || str_contains($currentUrl, '/drakor');
        $isSeries = (str_contains($currentUrl, '/series') || str_contains($currentUrl, '/tv-series')) && !$isDracin && !$isDrakor;
        $isMovie = (str_contains($currentUrl, '/movies') || str_contains($currentUrl, '/movie/')) && !$isDracin && !$isCollection;
        $isTv = (request()->is('tv') || request()->is('tv/*') || request()->is('live-tv*')) && !str_contains($currentUrl, '/tv-series');
        $isHome = !$isSeries && !$isMovie && !$isDracin && !$isAnime && !$isCollection && !$isNetwork && !$isIndonesia && !$isDrakor && !$isTv && !str_contains($currentUrl, '/dmca') && !str_contains($currentUrl, '/traffic');

        $activeClass = "flex items-center gap-1.5 text-white bg-red-600/80 border border-red-500/25 px-3 py-1 rounded-full text-xs font-bold tracking-wide transition";
        $inactiveClass = "flex items-center gap-1.5 text-gray-300 hover:text-white text-xs font-semibold tracking-wide transition";
    @endphp

    <!-- PWA Top Progress Bar Element -->
    <div id="pwa-top-loader" aria-hidden="true"></div>

    <!-- Centered Suspended Navbar Header (Hidden on mobile watch pages to avoid covering video) -->
    {{-- ================= MOBILE TOPBAR (< lg) =================
         Full-width, transparan di atas hero lalu solid saat discroll.
         Sembunyi saat scroll ke bawah, muncul lagi saat scroll ke atas. --}}
    @php
        $hideMobileTop = str_contains($currentUrl, '/movie/') || str_contains($currentUrl, '/season/');
    @endphp
    <header x-data="{
                hide: false,
                solid: false,
                lastY: 0,
                onScroll() {
                    const y = window.scrollY;
                    this.solid = y > 24;
                    if (this.mobileMenuOpen || this.searchOpen) {
                        this.hide = false;
                    } else if (y > 140 && y > this.lastY + 6) {
                        this.hide = true;
                    } else if (y < this.lastY - 6 || y < 140) {
                        this.hide = false;
                    }
                    this.lastY = y;
                }
            }"
            x-init="onScroll()"
            @scroll.window.throttle.60ms="onScroll()"
            :class="[
                hide ? '-translate-y-full' : 'translate-y-0',
                solid ? 'bg-[#050505]/95 backdrop-blur-2xl border-white/5 shadow-lg shadow-black/40' : 'bg-gradient-to-b from-black/75 via-black/30 to-transparent border-transparent'
            ]"
            class="{{ $hideMobileTop ? 'hidden' : 'flex' }} lg:hidden fixed top-0 inset-x-0 z-50 flex-col border-b transition-[transform,background-color] duration-300 pt-[env(safe-area-inset-top,0px)]">
        <div class="h-14 px-4 flex items-center gap-2">
            <a href="{{ \App\Filament\Streaming\Pages\StreamingHome::getUrl(panel: 'streaming') }}"
               class="flex items-center gap-2 mr-auto" aria-label="KZSTRM Home">
                <img src="/icons/kaze-app-192.png" alt="Logo" class="w-7 h-7 rounded-lg object-contain shadow-sm">
                <span class="text-red-600 font-black text-[20px] tracking-wider leading-none">KZSTRM</span>
            </a>

            <button type="button" aria-label="Cari"
                    @click="searchOpen = true; $nextTick(() => document.querySelector('[x-ref=searchInput]')?.focus())"
                    class="w-10 h-10 inline-flex items-center justify-center rounded-full text-zinc-200 active:bg-white/10 transition">
                <svg class="w-[22px] h-[22px]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.2-5.2m2.2-5.3a7.5 7.5 0 11-15 0 7.5 7.5 0 0115 0z"/></svg>
            </button>

            <button type="button" aria-label="Buka menu" :aria-expanded="mobileMenuOpen.toString()"
                    @click="mobileMenuOpen = true"
                    class="w-10 h-10 inline-flex items-center justify-center rounded-full active:bg-white/10 transition">
                @auth
                    <span class="w-8 h-8 rounded-full bg-gradient-to-br from-red-500 to-red-800 ring-2 ring-white/10 flex items-center justify-center text-[11px] font-black uppercase">{{ mb_substr(auth()->user()->name, 0, 2) }}</span>
                @else
                    <svg class="w-6 h-6 text-zinc-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
                @endauth
            </button>
        </div>
    </header>

    {{-- ================= DESKTOP TOPBAR (lg+) ================= --}}
    <header class="hidden lg:flex fixed top-4 inset-x-0 z-50 justify-center px-4">
        <div
            class="w-full max-w-7xl h-14 bg-black/60 backdrop-blur-xl border border-white/10 rounded-full flex items-center justify-between px-6 shadow-[0_8px_32px_0_rgba(0,0,0,0.8)]">
            <!-- Logo -->
            <a href="{{ \App\Filament\Streaming\Pages\StreamingHome::getUrl(panel: 'streaming') }}" class="flex items-center gap-2.5">
                <img src="/icons/kaze-app-192.png" alt="Logo" class="w-8 h-8 rounded-lg object-contain shadow-sm">
                <span class="text-red-600 font-extrabold text-2xl tracking-wider">KZSTRM</span>
            </a>

            <!-- Nav Links -->
            <nav class="hidden lg:flex items-center gap-6">
                <a href="{{ \App\Filament\Streaming\Pages\StreamingHome::getUrl(panel: 'streaming') }}"
                    class="{{ $isHome ? $activeClass : $inactiveClass }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    Home
                </a>
                <a href="{{ \App\Filament\Streaming\Pages\Movies::getUrl(panel: 'streaming') }}"
                    class="{{ $isMovie ? $activeClass : $inactiveClass }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 4h16a2 2 0 012 2v12a2 2 0 01-2 2H4a2 2 0 01-2-2V6a2 2 0 012-2z" />
                    </svg>
                    Movies
                </a>
                <a href="{{ \App\Filament\Streaming\Pages\TVSeries::getUrl(panel: 'streaming') }}" class="{{ $isSeries ? $activeClass : $inactiveClass }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    TV Series
                </a>
                <a href="{{ \App\Filament\Streaming\Pages\Indonesia::getUrl(panel: 'streaming') }}" class="{{ $isIndonesia ? $activeClass : $inactiveClass }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3" />
                    </svg>
                    Indonesia
                </a>
                <a href="{{ \App\Filament\Streaming\Pages\KoreanDrama::getUrl(panel: 'streaming') }}" class="{{ $isDrakor ? $activeClass : $inactiveClass }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    Drakor
                </a>
                <a href="{{ \App\Filament\Streaming\Pages\Collections::getUrl(panel: 'streaming') }}"
                    class="{{ $isCollection ? $activeClass : $inactiveClass }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    Collections
                </a>
                <a href="{{ \App\Filament\Streaming\Pages\Network::getUrl(panel: 'streaming') }}"
                    class="{{ $isNetwork ? $activeClass : $inactiveClass }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    Network
                </a>
                <a href="{{ \App\Filament\Streaming\Pages\Dracin::getUrl(panel: 'streaming') }}"
                    class="{{ $isDracin ? $activeClass : $inactiveClass }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 4h16a2 2 0 012 2v12a2 2 0 01-2 2H4a2 2 0 01-2-2V6a2 2 0 012-2z" />
                    </svg>
                    Dracin
                </a>
                <a href="{{ \App\Filament\Streaming\Pages\AnimeSources::getUrl(panel: 'streaming') }}"
                    class="{{ $isAnime ? $activeClass : $inactiveClass }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" />
                    </svg>
                    Anime
                </a>

                <a href="/tv" class="{{ $isTv ? $activeClass : $inactiveClass }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M6 20.25h12m-7.5-3v3m3-3v3m-10.125-3h17.25c.621 0 1.125-.504 1.125-1.125V4.875c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125z" />
                    </svg>
                    Live TV
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
                            <a href="{{ \App\Filament\Streaming\Pages\Profile::getUrl(panel: 'streaming') }}">
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
                                <a href="/watchlist"
                                    class="flex items-center gap-3 px-3.5 py-2.5 bg-[#18181b] border border-zinc-800 hover:bg-zinc-800 text-sm font-bold text-white rounded-xl transition duration-200">
                                    <div
                                        class="w-8 h-8 rounded-lg bg-red-950/80 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-4 h-4 text-red-500 fill-current" viewBox="0 0 24 24">
                                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                                        </svg>
                                    </div>
                                    <span>Daftar Tontonan 📑</span>
                                </a>

                                <a href="/traffic"
                                    class="flex items-center gap-3 px-3.5 py-2.5 bg-[#18181b] border border-zinc-800 hover:bg-zinc-800 text-sm font-bold text-emerald-400 rounded-xl transition duration-200">
                                    <div
                                        class="w-8 h-8 rounded-lg bg-emerald-950/80 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                                        </svg>
                                    </div>
                                    <span>Traffic Stats 📊</span>
                                </a>

                                <a href="/dmca"
                                    class="flex items-center gap-3 px-3.5 py-2.5 bg-[#18181b] border border-zinc-800 hover:bg-zinc-800 text-sm font-bold text-zinc-300 rounded-xl transition duration-200">
                                    <div
                                        class="w-8 h-8 rounded-lg bg-zinc-900 border border-zinc-800 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/>
                                        </svg>
                                    </div>
                                    <span>DMCA & Disclaimer ⚖️</span>
                                </a>

                                <!-- PWA Install Trigger -->
                                <button type="button"
                                    @click="window.dispatchEvent(new CustomEvent('trigger-pwa-install'))"
                                    class="w-full flex items-center gap-3 px-3.5 py-2.5 bg-[#18181b] border border-zinc-800 hover:bg-zinc-800 text-sm font-bold text-red-500 rounded-xl transition duration-200 text-left">
                                    <div class="w-8 h-8 rounded-lg bg-red-950/80 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
                                        </svg>
                                    </div>
                                    <span>Install App 📲</span>
                                </button>

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
                                <a href="/watchlist"
                                    class="flex items-center gap-3 px-3.5 py-2.5 bg-[#18181b] border border-zinc-800 hover:bg-zinc-800 text-sm font-bold text-white rounded-xl transition duration-200">
                                    <div
                                        class="w-8 h-8 rounded-lg bg-red-950/80 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-4 h-4 text-red-500 fill-current" viewBox="0 0 24 24">
                                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                                        </svg>
                                    </div>
                                    <span>Daftar Tontonan 📑</span>
                                </a>

                                <a href="/traffic"
                                    class="flex items-center gap-3 px-3.5 py-2.5 bg-[#18181b] border border-zinc-800 hover:bg-zinc-800 text-sm font-bold text-emerald-400 rounded-xl transition duration-200">
                                    <div
                                        class="w-8 h-8 rounded-lg bg-emerald-950/80 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                                        </svg>
                                    </div>
                                    <span>Traffic Stats 📊</span>
                                </a>

                                <a href="/dmca"
                                    class="flex items-center gap-3 px-3.5 py-2.5 bg-[#18181b] border border-zinc-800 hover:bg-zinc-800 text-sm font-bold text-zinc-300 rounded-xl transition duration-200">
                                    <div
                                        class="w-8 h-8 rounded-lg bg-zinc-900 border border-zinc-800 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/>
                                        </svg>
                                    </div>
                                    <span>DMCA & Disclaimer ⚖️</span>
                                </a>

                                <!-- PWA Install Trigger -->
                                <button type="button"
                                    @click="window.dispatchEvent(new CustomEvent('trigger-pwa-install'))"
                                    class="w-full flex items-center gap-3 px-3.5 py-2.5 bg-[#18181b] border border-zinc-800 hover:bg-zinc-800 text-sm font-bold text-red-500 rounded-xl transition duration-200 text-left">
                                    <div class="w-8 h-8 rounded-lg bg-red-950/80 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
                                        </svg>
                                    </div>
                                    <span>Install App 📲</span>
                                </button>

                                <a href="/login"
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

    {{-- ================= MOBILE MENU SHEET (< lg) ================= --}}
    <div x-show="mobileMenuOpen"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[60] bg-black/70 backdrop-blur-sm lg:hidden" @click="mobileMenuOpen = false" style="display:none;"></div>

    <aside x-show="mobileMenuOpen"
           x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
           x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full"
           x-data="{ sy: 0, dy: 0 }"
           @touchstart.passive="sy = $event.touches[0].clientY; dy = 0"
           @touchmove.passive="dy = Math.max(0, $event.touches[0].clientY - sy)"
           @touchend="if (dy > 90 && $el.scrollTop <= 0) mobileMenuOpen = false; dy = 0"
           :style="dy ? `transform: translateY(${dy}px)` : ''"
           role="dialog" aria-modal="true" aria-label="Menu"
           class="fixed inset-x-0 bottom-0 z-[61] lg:hidden max-h-[88dvh] overflow-y-auto overscroll-contain bg-[#0c0c0d] border-t border-white/10 rounded-t-3xl shadow-[0_-20px_60px_rgba(0,0,0,0.8)] pb-[calc(1.25rem+env(safe-area-inset-bottom,0px))]"
           style="display:none;">

        <div class="sticky top-0 z-10 bg-[#0c0c0d] pt-3 pb-2 px-5">
            <div class="mx-auto w-10 h-1.5 rounded-full bg-zinc-700" aria-hidden="true"></div>
            <div class="mt-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <img src="/icons/kaze-app-192.png" alt="Logo" class="w-6 h-6 rounded-md object-contain shadow-sm">
                    <span class="text-red-600 font-black text-xl tracking-wider">KZSTRM</span>
                </div>
                <button type="button" @click="mobileMenuOpen = false" aria-label="Tutup menu"
                        class="w-9 h-9 inline-flex items-center justify-center rounded-full bg-white/5 text-zinc-300 active:bg-white/10">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>

        <div class="px-5 space-y-5">
            {{-- Akun --}}
            @auth
                <a href="{{ \App\Filament\Streaming\Pages\Profile::getUrl(panel: 'streaming') }}"
                   class="flex items-center gap-3 p-3 rounded-2xl bg-gradient-to-br from-red-600/20 to-transparent ring-1 ring-red-500/20 active:scale-[0.99] transition">
                    <span class="w-11 h-11 rounded-full bg-gradient-to-br from-red-500 to-red-800 flex items-center justify-center text-sm font-black uppercase">{{ mb_substr(auth()->user()->name, 0, 2) }}</span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-bold truncate">{{ auth()->user()->name }}</span>
                        <span class="block text-[11px] text-zinc-400">Lihat profil & langganan</span>
                    </span>
                    <svg class="w-4 h-4 text-zinc-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                </a>
            @else
                <div class="grid grid-cols-2 gap-2">
                    <a href="/login" class="py-3 rounded-xl bg-white text-black text-sm font-bold text-center active:scale-[0.98] transition">Masuk</a>
                    <a href="/register" class="py-3 rounded-xl bg-white/5 ring-1 ring-white/10 text-sm font-bold text-center active:bg-white/10">Daftar</a>
                </div>
            @endauth

            {{-- Jelajah --}}
            <div>
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500 mb-2.5">Jelajah</p>
                @php
                    $sheetLinks = [
                        ['Home', \App\Filament\Streaming\Pages\StreamingHome::getUrl(panel: 'streaming'), $isHome, 'M2.25 12l8.954-8.955a1.126 1.126 0 011.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25'],
                        ['Movies', \App\Filament\Streaming\Pages\Movies::getUrl(panel: 'streaming'), $isMovie, 'M3.375 19.5h17.25m-17.25 0a1.125 1.125 0 01-1.125-1.125M3.375 19.5h1.5C5.496 19.5 6 18.996 6 18.375m-3.75 0V5.625m0 12.75v-1.5c0-.621.504-1.125 1.125-1.125m18.375 2.625V5.625m0 12.75c0 .621-.504 1.125-1.125 1.125m1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125m0 3.75h-1.5A1.125 1.125 0 0118 18.375M20.625 4.5H3.375m17.25 0c.621 0 1.125.504 1.125 1.125M20.625 4.5h-1.5C18.504 4.5 18 5.004 18 5.625m3.75 0v1.5c0 .621-.504 1.125-1.125 1.125M3.375 4.5c-.621 0-1.125.504-1.125 1.125M3.375 4.5h1.5C5.496 4.5 6 5.004 6 5.625m-3.75 0v1.5c0 .621.504 1.125 1.125 1.125m0 0h1.5m-1.5 0c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125m1.5-3.75C5.496 8.25 6 7.746 6 7.125v-1.5M4.875 8.25C5.496 8.25 6 8.754 6 9.375v1.5m0-5.25v5.25m0-5.25C6 5.004 6.504 4.5 7.125 4.5h9.75c.621 0 1.125.504 1.125 1.125m1.125 2.625h1.5m-1.5 0A1.125 1.125 0 0118 7.125v-1.5m1.125 2.625c-.621 0-1.125.504-1.125 1.125v1.5m2.625-2.625c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125M18 5.625v5.25M7.125 12h9.75m-9.75 0A1.125 1.125 0 016 10.875M7.125 12C6.504 12 6 12.504 6 13.125m0-2.25C6 11.496 5.496 12 4.875 12M18 10.875c0 .621-.504 1.125-1.125 1.125M18 10.875c0 .621.504 1.125 1.125 1.125m-2.25 0c.621 0 1.125.504 1.125 1.125m-12 5.25v-5.25m0 5.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125m-12 0v-1.5c0-.621-.504-1.125-1.125-1.125M18 18.375v-5.25m0 5.25v-1.5c0-.621.504-1.125 1.125-1.125M18 13.125v1.5c0 .621.504 1.125 1.125 1.125M18 13.125c0-.621.504-1.125 1.125-1.125M6 13.125v1.5c0 .621-.504 1.125-1.125 1.125M6 13.125C6 12.504 5.496 12 4.875 12m-1.5 0h1.5m-1.5 0c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125m-17.25 0h1.5m14.25 0h1.5'],
                        ['TV Series', \App\Filament\Streaming\Pages\TVSeries::getUrl(panel: 'streaming'), $isSeries, 'M6 20.25h12m-7.5-3v3m3-3v3m-10.125-3h17.25c.621 0 1.125-.504 1.125-1.125V4.875c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125z'],
                        ['Indonesia', \App\Filament\Streaming\Pages\Indonesia::getUrl(panel: 'streaming'), $isIndonesia, 'M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3'],
                        ['Drakor', \App\Filament\Streaming\Pages\KoreanDrama::getUrl(panel: 'streaming'), $isDrakor, 'M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z'],
                        ['Live TV', '/tv', $isTv, 'M6 20.25h12m-7.5-3v3m3-3v3m-10.125-3h17.25c.621 0 1.125-.504 1.125-1.125V4.875c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125z'],
                        ['Dracin', \App\Filament\Streaming\Pages\Dracin::getUrl(panel: 'streaming'), $isDracin, 'M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3'],
                        ['Anime', \App\Filament\Streaming\Pages\AnimeSources::getUrl(panel: 'streaming'), $isAnime, 'M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456z'],
                        ['Collections', \App\Filament\Streaming\Pages\Collections::getUrl(panel: 'streaming'), $isCollection, 'M6 6.878V6a2.25 2.25 0 012.25-2.25h7.5A2.25 2.25 0 0118 6v.878m-12 0c.235-.083.487-.128.75-.128h10.5c.263 0 .515.045.75.128m-12 0A2.25 2.25 0 004.5 9v.878m13.5-3A2.25 2.25 0 0119.5 9v.878m0 0a2.246 2.246 0 00-.75-.128H5.25c-.263 0-.515.045-.75.128m15 0A2.25 2.25 0 0121 12v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6c0-.98.626-1.813 1.5-2.122'],
                        ['Network', \App\Filament\Streaming\Pages\Network::getUrl(panel: 'streaming'), $isNetwork, 'M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418'],
                        ['Watchlist', '/watchlist', str_contains($currentUrl, '/watchlist'), 'M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0z'],
                    ];
                @endphp
                <div class="grid grid-cols-4 gap-2">
                    @foreach($sheetLinks as [$label, $href, $active, $path])
                        <a href="{{ $href }}" @click="mobileMenuOpen = false"
                           class="flex flex-col items-center justify-center gap-1.5 py-3 rounded-2xl transition active:scale-95 {{ $active ? 'bg-red-600 text-white shadow-lg shadow-red-900/40' : 'bg-white/[0.04] ring-1 ring-white/5 text-zinc-300' }}">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $path }}"/></svg>
                            <span class="text-[11px] font-bold leading-none">{{ $label }}</span>
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- Lainnya --}}
            <div>
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500 mb-2">Lainnya</p>
                <div class="rounded-2xl bg-white/[0.03] ring-1 ring-white/5 divide-y divide-white/5 overflow-hidden">
                    <button type="button" @click="mobileMenuOpen = false; window.dispatchEvent(new CustomEvent('trigger-pwa-install'))"
                            class="w-full flex items-center gap-3 px-4 py-3.5 text-sm font-semibold text-left active:bg-white/5">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                        <span class="flex-1">Install Aplikasi</span>
                    </button>
                    <a href="/traffic" class="flex items-center gap-3 px-4 py-3.5 text-sm font-semibold active:bg-white/5">
                        <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/></svg>
                        <span class="flex-1">Live Traffic Stats</span>
                    </a>
                    <a href="/dmca" class="flex items-center gap-3 px-4 py-3.5 text-sm font-semibold active:bg-white/5">
                        <svg class="w-5 h-5 text-zinc-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/></svg>
                        <span class="flex-1">DMCA & Copyright</span>
                    </a>
                    @auth
                        <form action="{{ route('clear.cache') }}" method="GET">
                            @csrf
                            <button type="submit" class="w-full flex items-center gap-3 px-4 py-3.5 text-sm font-semibold text-left active:bg-white/5">
                                <svg class="w-5 h-5 text-zinc-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                                <span class="flex-1">Clear Cache</span>
                            </button>
                        </form>
                        <form action="{{ route('filament.streaming.auth.logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full flex items-center gap-3 px-4 py-3.5 text-sm font-semibold text-red-400 text-left active:bg-white/5">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/></svg>
                                <span class="flex-1">Keluar</span>
                            </button>
                        </form>
                    @endauth
                </div>
            </div>
        </div>
    </aside>

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

    {{-- Global Site Footer (Hidden on watch video player to keep full immersion) --}}
    @if(!str_contains(request()->url(), '/movie/') && !str_contains(request()->url(), '/episode/') && !str_contains(request()->url(), '/drama/'))
        <footer class="w-full border-t border-white/5 bg-black/40 text-center py-8 px-4 text-xs text-zinc-500 space-y-2 mt-auto">
            <div class="flex items-center justify-center gap-3 sm:gap-4 flex-wrap text-zinc-400 font-medium">
                <a href="/" class="hover:text-white transition">Home</a>
                <span>•</span>
                <a href="/movies" class="hover:text-white transition">Movies</a>
                <span>•</span>
                <a href="/tv-series" class="hover:text-white transition">TV Series</a>
                <span>•</span>
                <a href="/tv" class="hover:text-white transition">Live TV</a>
                <span>•</span>
                <a href="/dracin" class="hover:text-white transition">Dracin</a>
                <span>•</span>
                <a href="/anime" class="hover:text-white transition">Anime</a>
                <span>•</span>
                <a href="/traffic" class="hover:text-white transition">Live Traffic</a>
                <span>•</span>
                <a href="/dmca" class="text-red-500 hover:text-red-400 font-bold transition">DMCA & Disclaimer</a>
            </div>
            <p class="text-[11px] text-zinc-600">Disclaimer: KZSTRM does not store any files on its servers. All contents are provided by non-affiliated third parties.</p>
            <p class="text-[10px] text-zinc-600">© {{ date('Y') }} KZSTRM. All Rights Reserved.</p>
        </footer>
    @endif

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
                    placeholder="Cari film, serial, anime, drama china..."
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

            <!-- Category Filter Tabs -->
            <div class="flex items-center gap-1.5 px-4 py-2.5 border-b border-white/5 overflow-x-auto no-scrollbar text-xs bg-black/40">
                <button type="button" @click="setSearchCategory('all')"
                    :class="searchCategory === 'all' ? 'bg-red-600 text-white font-bold shadow-md shadow-red-600/30' : 'bg-white/5 text-zinc-400 hover:text-white hover:bg-white/10'"
                    class="px-3 py-1 rounded-full whitespace-nowrap transition text-xs">
                    Semua
                </button>
                <button type="button" @click="setSearchCategory('movie')"
                    :class="searchCategory === 'movie' ? 'bg-red-600 text-white font-bold shadow-md shadow-red-600/30' : 'bg-white/5 text-zinc-400 hover:text-white hover:bg-white/10'"
                    class="px-3 py-1 rounded-full whitespace-nowrap transition text-xs">
                    🎬 Film
                </button>
                <button type="button" @click="setSearchCategory('series')"
                    :class="searchCategory === 'series' ? 'bg-red-600 text-white font-bold shadow-md shadow-red-600/30' : 'bg-white/5 text-zinc-400 hover:text-white hover:bg-white/10'"
                    class="px-3 py-1 rounded-full whitespace-nowrap transition text-xs">
                    📺 Series
                </button>
                <button type="button" @click="setSearchCategory('anime')"
                    :class="searchCategory === 'anime' ? 'bg-purple-600 text-white font-bold shadow-md shadow-purple-600/30' : 'bg-white/5 text-zinc-400 hover:text-white hover:bg-white/10'"
                    class="px-3 py-1 rounded-full whitespace-nowrap transition text-xs">
                    ⛩️ Anime
                </button>
                <button type="button" @click="setSearchCategory('dracin')"
                    :class="searchCategory === 'dracin' ? 'bg-amber-600 text-white font-bold shadow-md shadow-amber-600/30' : 'bg-white/5 text-zinc-400 hover:text-white hover:bg-white/10'"
                    class="px-3 py-1 rounded-full whitespace-nowrap transition text-xs">
                    🎭 Dracin
                </button>
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
                    <span>Mencari konten...</span>
                </div>

                <!-- Empty Results State -->
                <div x-show="searchResults.length === 0 && searchQuery.length > 0 && !searchLoading"
                    class="py-12 text-center text-zinc-500">
                    Tidak ada hasil untuk "<span class="text-zinc-300 font-semibold" x-text="searchQuery"></span>"
                </div>

                <!-- Default State (when no search query has been typed) -->
                <div x-show="searchQuery.length === 0" class="py-12 text-center text-zinc-500">
                    Ketik judul film, series, anime, atau drama china...
                </div>

                <!-- Results list -->
                <div class="py-2" x-show="searchResults.length > 0 && !searchLoading">
                    <template x-for="item in searchResults" :key="item.id">
                        <a :href="item.url"
                            class="w-full flex items-center gap-3 px-5 py-3 text-left transition hover:bg-white/5">
                            <div
                                class="w-10 h-14 rounded-lg overflow-hidden bg-zinc-900 flex-shrink-0 border border-white/5">
                                <template x-if="item.thumbnail">
                                    <img :alt="item.title" class="w-full h-full object-cover" :src="item.thumbnail" loading="lazy">
                                </template>
                                <template x-if="!item.thumbnail">
                                    <div
                                        class="w-full h-full flex items-center justify-center text-[10px] text-zinc-600 bg-zinc-800">
                                        No Image</div>
                                </template>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-medium text-white line-clamp-2 leading-snug"
                                        x-text="item.title + (item.year && item.category !== 'anime' && item.category !== 'dracin' ? ' (' + item.year + ')' : '')"></span>
                                </div>
                                <div class="flex items-center gap-2 mt-1">
                                    <!-- Badge Kategori -->
                                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded uppercase tracking-wider"
                                        :class="{
                                            'bg-red-500/20 text-red-400 border border-red-500/30': item.category === 'movie',
                                            'bg-blue-500/20 text-blue-400 border border-blue-500/30': item.category === 'series',
                                            'bg-purple-500/20 text-purple-400 border border-purple-500/30': item.category === 'anime',
                                            'bg-amber-500/20 text-amber-400 border border-amber-500/30': item.category === 'dracin',
                                            'bg-zinc-800 text-zinc-300 border border-zinc-700': !['movie','series','anime','dracin'].includes(item.category)
                                        }"
                                        x-text="item.type || item.badge || 'Film'">
                                    </span>

                                    <!-- Badge Sumber -->
                                    <template x-if="item.source_name">
                                        <span class="text-[10px] text-zinc-400 bg-white/5 px-1.5 py-0.5 rounded border border-white/5"
                                            x-text="item.source_name"></span>
                                    </template>

                                    <!-- Rating -->
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

    {{-- ================= MOBILE BOTTOM NAV (< lg) =================
         Dock mengambang: ikon jelas, label pendek, indikator aktif berbentuk pill. --}}
    @php
        $dockLinks = [
            ['Home', \App\Filament\Streaming\Pages\StreamingHome::getUrl(panel: 'streaming'), $isHome, 'M2.25 12l8.954-8.955a1.126 1.126 0 011.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25'],
            ['Movies', \App\Filament\Streaming\Pages\Movies::getUrl(panel: 'streaming'), $isMovie, 'M3.75 4.5h16.5a.75.75 0 01.75.75v13.5a.75.75 0 01-.75.75H3.75a.75.75 0 01-.75-.75V5.25a.75.75 0 01.75-.75zM7.5 4.5v15m9-15v15M3 9h4.5M3 15h4.5m9-6H21m-4.5 6H21'],
            ['Series', \App\Filament\Streaming\Pages\TVSeries::getUrl(panel: 'streaming'), $isSeries, 'M6 20.25h12m-7.5-3v3m3-3v3m-10.125-3h17.25c.621 0 1.125-.504 1.125-1.125V4.875c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125z'],
            ['Dracin', \App\Filament\Streaming\Pages\Dracin::getUrl(panel: 'streaming'), $isDracin, 'M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3'],
            ['Anime', \App\Filament\Streaming\Pages\AnimeSources::getUrl(panel: 'streaming'), $isAnime, 'M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456z'],
        ];
    @endphp
    <nav aria-label="Navigasi utama"
         class="lg:hidden fixed inset-x-0 bottom-0 z-50 px-3 pb-[calc(0.6rem+env(safe-area-inset-bottom,0px))] pointer-events-none">
        <div class="pointer-events-auto mx-auto max-w-md grid grid-cols-5 gap-1 p-1.5 rounded-[22px] bg-[#111113]/95 backdrop-blur-2xl ring-1 ring-white/10 shadow-[0_12px_40px_rgba(0,0,0,0.75)]">
            @foreach($dockLinks as [$label, $href, $active, $path])
                <a href="{{ $href }}" @if($active) aria-current="page" @endif
                   class="relative flex flex-col items-center justify-center gap-1 h-[52px] rounded-2xl transition active:scale-90 {{ $active ? 'text-white' : 'text-zinc-400' }}">
                    @if($active)
                        <span class="absolute inset-0 rounded-2xl bg-gradient-to-b from-red-600/30 to-red-600/5 ring-1 ring-red-500/30" aria-hidden="true"></span>
                    @endif
                    <svg class="relative w-[22px] h-[22px] {{ $active ? 'text-red-500' : '' }}" fill="none" stroke="currentColor" stroke-width="{{ $active ? '2' : '1.7' }}" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $path }}"/>
                    </svg>
                    <span class="relative text-[10px] leading-none {{ $active ? 'font-bold' : 'font-semibold' }}">{{ $label }}</span>
                </a>
            @endforeach
        </div>
    </nav>

    <!-- PWA Install Prompt Banner & iOS Guide (Alpine.js) -->
    @php
        $isWatchPageUrl = str_contains(request()->url(), '/movie/') || str_contains(request()->url(), '/series/') || (str_contains(request()->url(), '/anime/') && str_contains(request()->url(), '/episode/')) || str_contains(request()->url(), '/drama/');
    @endphp
    <div x-data="pwaInstaller()"
         x-init="init()"
         x-cloak>
        @if(!$isWatchPageUrl)
        <!-- Floating Docked Banner (Desktop & Mobile) -->
        <div x-show="showBanner && !isStandalone"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-8 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
             x-transition:leave-end="opacity-0 translate-y-8 scale-95"
             class="fixed bottom-20 md:bottom-6 right-4 left-4 md:left-auto md:w-[420px] z-[90] bg-[#101014]/95 border border-red-600/40 backdrop-blur-2xl rounded-2xl p-3.5 sm:p-4 shadow-[0_16px_40px_rgba(0,0,0,0.8)] flex items-center justify-between gap-3 text-white"
             style="display: none;">
            
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-12 h-12 rounded-xl bg-black border border-white/10 flex-shrink-0 overflow-hidden shadow-md shadow-red-600/20">
                    <img src="/icons/kaze-app-192.png" alt="KZSTRM" class="w-full h-full object-cover">
                </div>
                <div class="min-w-0">
                    <div class="flex items-center gap-1.5">
                        <span class="text-xs sm:text-sm font-black text-white tracking-wide">Install KZSTRM App</span>
                        <span class="px-1.5 py-0.2 rounded bg-red-600/20 border border-red-500/30 text-[9px] font-bold text-red-400 uppercase">PWA</span>
                    </div>
                    <p class="text-[11px] text-zinc-400 truncate mt-0.5 leading-snug">
                        Nonton fullscreen tanpa address bar & hemat kuota!
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2 flex-shrink-0">
                <button type="button"
                        @click="installApp()"
                        class="px-3.5 py-2 bg-red-600 hover:bg-red-700 active:scale-95 text-white font-bold text-xs rounded-xl shadow-lg shadow-red-600/30 transition">
                    Install
                </button>
                <button type="button"
                        @click="dismissBanner()"
                        class="w-7 h-7 rounded-full bg-zinc-800/80 hover:bg-zinc-700 text-zinc-400 hover:text-white flex items-center justify-center transition"
                        title="Tutup">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
        </div>
        @endif

        <!-- iOS Safari Install Tutorial Modal -->
        <div x-show="showIosModal"
             x-transition.opacity.duration.200ms
             @click.self="showIosModal = false"
             class="fixed inset-0 z-[120] bg-black/80 backdrop-blur-md flex items-end sm:items-center justify-center p-4"
             style="display: none;">
            <div x-show="showIosModal"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-10 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                 x-transition:leave-end="opacity-0 translate-y-10 scale-95"
                 class="w-full max-w-sm bg-[#121215] border border-zinc-800 rounded-3xl p-6 text-center space-y-5 shadow-2xl">
                
                <div class="w-16 h-16 mx-auto rounded-2xl bg-black border border-white/10 overflow-hidden shadow-xl shadow-red-600/20">
                    <img src="/icons/kaze-app-192.png" alt="KZSTRM" class="w-full h-full object-cover">
                </div>

                <div class="space-y-1.5">
                    <h3 class="text-lg font-bold text-white">Pasang KZSTRM di iPhone</h3>
                    <p class="text-xs text-zinc-400 leading-relaxed">
                        Nikmati pengalaman aplikasi native layar penuh tanpa address bar Safari:
                    </p>
                </div>

                <div class="space-y-3 text-left text-xs bg-zinc-950/70 p-4 rounded-2xl border border-zinc-800/80">
                    <div class="flex items-start gap-3">
                        <div class="w-6 h-6 rounded-full bg-red-600/20 text-red-400 font-bold flex items-center justify-center flex-shrink-0 text-xs">1</div>
                        <div>Ketuk tombol <strong>Bagikan (Share)</strong> <svg class="inline w-4 h-4 text-blue-400 align-text-bottom" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg> di bilah bawah Safari.</div>
                    </div>
                    <div class="flex items-start gap-3">
                        <div class="w-6 h-6 rounded-full bg-red-600/20 text-red-400 font-bold flex items-center justify-center flex-shrink-0 text-xs">2</div>
                        <div>Gulir ke bawah lalu pilih opsi <strong>"Tambah ke Layar Utama" (Add to Home Screen)</strong> ➕.</div>
                    </div>
                    <div class="flex items-start gap-3">
                        <div class="w-6 h-6 rounded-full bg-red-600/20 text-red-400 font-bold flex items-center justify-center flex-shrink-0 text-xs">3</div>
                        <div>Ketuk <strong>Tambah (Add)</strong> di pojok kanan atas. Buka KZSTRM dari ikon home screen!</div>
                    </div>
                </div>

                <button type="button"
                        @click="showIosModal = false"
                        class="w-full py-3 rounded-xl bg-red-600 hover:bg-red-700 text-white font-bold text-xs shadow-lg shadow-red-600/30 transition">
                    Saya Mengerti
                </button>
            </div>
        </div>
    </div>

    <!-- PWA Alpine Controller & Service Worker Registration Script -->
    <script>
        function pwaInstaller() {
            return {
                showBanner: false,
                showIosModal: false,
                isStandalone: false,
                isIos: false,
                deferredPrompt: null,

                init() {
                    // Check standalone display mode (already installed)
                    this.isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
                    if (this.isStandalone) {
                        return;
                    }

                    // Check if device is iOS Safari
                    this.isIos = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;

                    // Listen for Chrome / Android beforeinstallprompt
                    window.addEventListener('beforeinstallprompt', (e) => {
                        e.preventDefault();
                        this.deferredPrompt = e;
                        const dismissed = localStorage.getItem('kzstrm_pwa_dismissed');
                        if (!dismissed) {
                            setTimeout(() => { this.showBanner = true; }, 2000);
                        }
                    });

                    // Listen for global custom trigger (from menus)
                    window.addEventListener('trigger-pwa-install', () => {
                        this.installApp();
                    });

                    // If iOS and not dismissed, show banner
                    if (this.isIos && !localStorage.getItem('kzstrm_pwa_dismissed')) {
                        setTimeout(() => { this.showBanner = true; }, 3000);
                    }
                },

                installApp() {
                    if (this.deferredPrompt) {
                        this.deferredPrompt.prompt();
                        this.deferredPrompt.userChoice.then((choiceResult) => {
                            if (choiceResult.outcome === 'accepted') {
                                this.showBanner = false;
                            }
                            this.deferredPrompt = null;
                        });
                    } else if (this.isIos) {
                        this.showIosModal = true;
                    } else {
                        // Desktop Chrome or generic fallback
                        alert("Buka menu browser Anda (titik tiga ⋮ di pojok atas) lalu pilih 'Install KZSTRM' atau 'Tambahkan ke Layar Utama'.");
                    }
                },

                dismissBanner() {
                    this.showBanner = false;
                    localStorage.setItem('kzstrm_pwa_dismissed', Date.now());
                }
            }
        }

        // Service Worker Registration
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js', { scope: '/' })
                    .then((reg) => {
                        console.log('KZSTRM PWA ServiceWorker active with scope:', reg.scope);
                    })
                    .catch((err) => {
                        console.warn('KZSTRM PWA ServiceWorker registration error:', err);
                    });
            });
        }

        // PWA Top Loading Progress Bar (YouTube/GitHub Style)
        const PwaTopLoader = (function () {
            let bar = null;
            let progress = 0;
            let trickleTimer = null;
            let safetyTimeout = null;

            function getBar() {
                if (!bar) {
                    bar = document.getElementById('pwa-top-loader');
                    if (!bar) {
                        bar = document.createElement('div');
                        bar.id = 'pwa-top-loader';
                        bar.setAttribute('aria-hidden', 'true');
                        document.body.prepend(bar);
                    }
                }
                return bar;
            }

            function setWidth(p) {
                progress = p;
                const el = getBar();
                el.style.width = p + '%';
            }

            function start() {
                const el = getBar();
                if (trickleTimer) clearInterval(trickleTimer);
                if (safetyTimeout) clearTimeout(safetyTimeout);

                el.classList.remove('is-complete');
                el.classList.add('is-loading');
                setWidth(18);

                trickleTimer = setInterval(() => {
                    if (progress < 45) {
                        setWidth(progress + (Math.random() * 10 + 6));
                    } else if (progress < 75) {
                        setWidth(progress + (Math.random() * 5 + 3));
                    } else if (progress < 90) {
                        setWidth(progress + (Math.random() * 2 + 0.6));
                    }
                }, 180);

                // Auto reset safety after 10s if navigation or request aborts
                safetyTimeout = setTimeout(() => {
                    finish();
                }, 10000);
            }

            function finish() {
                if (trickleTimer) {
                    clearInterval(trickleTimer);
                    trickleTimer = null;
                }
                if (safetyTimeout) {
                    clearTimeout(safetyTimeout);
                    safetyTimeout = null;
                }
                const el = getBar();
                el.classList.add('is-complete');
                setTimeout(() => {
                    el.classList.remove('is-loading', 'is-complete');
                    setWidth(0);
                }, 400);
            }

            // Intercept standard navigation link clicks
            document.addEventListener('click', (e) => {
                const anchor = e.target.closest('a');
                if (!anchor) return;

                // Ignore modifier keys
                if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
                // Ignore non-left click
                if (e.button !== 0) return;
                // Ignore default prevented
                if (e.defaultPrevented) return;

                const href = anchor.getAttribute('href');
                if (!href) return;

                // Ignore hash, javascript, mailto, tel
                if (href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('mailto:') || href.startsWith('tel:')) {
                    return;
                }

                // Ignore external target, download, or opt-out attribute
                if (anchor.target === '_blank' || anchor.hasAttribute('download') || anchor.hasAttribute('data-no-loader')) {
                    return;
                }

                try {
                    const targetUrl = new URL(anchor.href, window.location.href);
                    // Check same origin
                    if (targetUrl.origin !== window.location.origin) return;

                    // Same page anchor jump
                    if (targetUrl.pathname === window.location.pathname && targetUrl.search === window.location.search && targetUrl.hash) {
                        return;
                    }

                    // Start progress bar immediately
                    start();
                } catch (_) {}
            }, true);

            // Intercept form submissions
            document.addEventListener('submit', (e) => {
                if (e.defaultPrevented) return;
                const form = e.target;
                if (form.target === '_blank' || form.hasAttribute('data-no-loader')) return;
                start();
            }, true);

            // Trigger on beforeunload
            window.addEventListener('beforeunload', () => {
                start();
            });

            // Reset on BFCache restore (back/forward button)
            window.addEventListener('pageshow', (e) => {
                if (e.persisted) finish();
            });

            // Livewire hooks
            document.addEventListener('livewire:navigating', () => start());
            document.addEventListener('livewire:navigated', () => finish());
            document.addEventListener('livewire:init', () => {
                if (window.Livewire && typeof window.Livewire.hook === 'function') {
                    window.Livewire.hook('request', ({ fail, succeed }) => {
                        start();
                        succeed(() => finish());
                        fail(() => finish());
                    });
                }
            });

            return { start, finish };
        })();
    </script>

    @livewireScripts
    @stack('scripts')
</body>

</html>