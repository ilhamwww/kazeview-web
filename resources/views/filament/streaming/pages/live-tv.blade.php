<div class="min-h-screen bg-[#050505] text-white pb-32 md:pb-20 relative overflow-x-hidden"
     x-data="{
        activeCategory: 'all',
        searchQuery: '',
        selectedCountry: '{{ $selectedCountry ?? 'id' }}',
        showCountryModal: false,
        countrySearch: '',
        channels: {{ Js::from($channels) }},
        categories: {{ Js::from($categories) }},
        countries: {{ Js::from($countries) }},
        get popularCountries() {
            return this.countries.filter(c => c.is_popular);
        },
        get filteredCountries() {
            const q = this.countrySearch.toLowerCase().trim();
            if (!q) return this.countries;
            return this.countries.filter(c => c.name.toLowerCase().includes(q) || c.code.toLowerCase().includes(q));
        },
        get filteredChannels() {
            return this.channels.filter(ch => {
                const matchesCat = this.activeCategory === 'all' || (ch.category && ch.category.toLowerCase() === this.activeCategory.toLowerCase());
                const q = this.searchQuery.toLowerCase().trim();
                const matchesSearch = !q || ch.name.toLowerCase().includes(q) || (ch.category && ch.category.toLowerCase().includes(q)) || (ch.description && ch.description.toLowerCase().includes(q));
                return matchesCat && matchesSearch;
            });
        }
     }">

    {{-- Ambient Glow Background --}}
    <div class="pointer-events-none absolute inset-x-0 top-0 h-[500px] overflow-hidden" aria-hidden="true">
        <div class="absolute -top-32 left-1/2 -translate-x-1/2 w-[600px] md:w-[900px] h-[350px] md:h-[450px] bg-red-600/15 rounded-full blur-[140px]"></div>
    </div>

    {{-- Main Container --}}
    <div class="relative w-full max-w-7xl mx-auto px-4 md:px-8 pt-20 md:pt-28 space-y-8">
        {{-- Hero Header --}}
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 pb-2 border-b border-white/5">
            <div class="space-y-3 max-w-2xl">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-red-600/10 border border-red-500/25 text-red-500 text-xs font-black tracking-wider uppercase">
                    <span class="w-2 h-2 rounded-full bg-red-500 animate-ping"></span>
                    <span>Live TV Hybrid 24 Jam Nonstop</span>
                </div>
                <h1 class="text-3xl md:text-5xl font-black tracking-tight text-white leading-tight flex items-center gap-3">
                    <span>Live TV {{ $currentCountry['name'] ?? 'Indonesia' }}</span>
                    <span class="text-2xl md:text-4xl">{{ $currentCountry['flag'] ?? '🇮🇩' }}</span>
                </h1>
                <p class="text-xs md:text-sm text-zinc-400 leading-relaxed">
                    Nonton siaran langsung televisi nasional {{ $currentCountry['name'] ?? 'Indonesia' }} dan ribuan channel TV dunia (TV Garden Hybrid) kualitas HD tanpa buffering langsung di peramban Anda.
                </p>
                <div class="flex flex-wrap items-center gap-2 pt-1 text-[11px] text-zinc-400 font-semibold">
                    <span class="px-2.5 py-1 rounded-lg bg-zinc-900 border border-zinc-800 text-zinc-300">
                        Total <strong class="text-white">{{ count($channels) }}</strong> Channel Aktif
                    </span>
                    <span class="px-2.5 py-1 rounded-lg bg-zinc-900 border border-zinc-800 text-zinc-300">
                        170+ Negara Tersedia
                    </span>
                    <span class="px-2.5 py-1 rounded-lg bg-zinc-900 border border-zinc-800 text-red-400">
                        Hemat Kuota (Direct Stream)
                    </span>
                </div>
            </div>

            {{-- Channel Search Input --}}
            <div class="w-full md:w-80 relative">
                <input type="text"
                       x-model="searchQuery"
                       :placeholder="'Cari channel TV ' + '{{ $currentCountry['name'] ?? '' }}...'"
                       class="w-full px-4 py-3 pl-11 bg-zinc-900/80 border border-zinc-800 rounded-2xl text-xs md:text-sm text-white placeholder-zinc-500 focus:outline-none focus:border-red-500 focus:ring-1 focus:ring-red-500 transition shadow-inner">
                <svg class="w-4 h-4 text-zinc-500 absolute left-4 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.2-5.2m2.2-5.3a7.5 7.5 0 11-15 0 7.5 7.5 0 0115 0z" />
                </svg>
                <button type="button" x-show="searchQuery.length > 0" @click="searchQuery = ''" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-zinc-500 hover:text-white text-xs">
                    ✕
                </button>
            </div>
        </div>

        {{-- Country Selector Bar --}}
        <div class="space-y-2.5">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-zinc-400 flex items-center gap-1.5 uppercase tracking-wider">
                    <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418"/></svg>
                    Pilih Negara / Wilayah:
                </span>
                <button type="button"
                        @click="showCountryModal = true"
                        class="text-xs text-red-400 hover:text-red-300 font-semibold flex items-center gap-1">
                    <span>Semua 170+ Negara</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                </button>
            </div>

            <div class="flex items-center gap-2 overflow-x-auto no-scrollbar py-1">
                <template x-for="c in popularCountries" :key="c.code">
                    <a :href="'/tv?country=' + c.code"
                       :class="selectedCountry === c.code ? 'bg-red-600 text-white font-black shadow-lg shadow-red-900/40 border-red-500 ring-2 ring-red-500/30' : 'bg-zinc-900/80 text-zinc-400 hover:text-white border-zinc-800 hover:border-zinc-700'"
                       class="px-3.5 py-2 rounded-xl text-xs font-bold border transition shrink-0 flex items-center gap-2 whitespace-nowrap">
                        <span class="text-base" x-text="c.flag"></span>
                        <span x-text="c.name"></span>
                        <span class="text-[10px] px-1.5 py-0.5 rounded-md bg-black/30 text-zinc-300" x-text="c.channel_count"></span>
                    </a>
                </template>

                <button type="button"
                        @click="showCountryModal = true"
                        class="px-3.5 py-2 rounded-xl text-xs font-bold border border-zinc-700/80 bg-zinc-800/80 hover:bg-zinc-700 text-zinc-200 hover:text-white transition shrink-0 flex items-center gap-1.5 whitespace-nowrap">
                    <svg class="w-3.5 h-3.5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    <span>Lainnya (170+)...</span>
                </button>
            </div>
        </div>

        {{-- Category Pills Filter --}}
        <div class="flex items-center gap-2 overflow-x-auto no-scrollbar py-1 border-t border-zinc-900 pt-3">
            <button type="button"
                    @click="activeCategory = 'all'"
                    :class="activeCategory === 'all' ? 'bg-red-600 text-white shadow-lg shadow-red-900/40 border-red-500' : 'bg-zinc-900/80 text-zinc-400 hover:text-white border-zinc-800'"
                    class="px-4 py-2 rounded-xl text-xs font-bold border transition shrink-0 flex items-center gap-2">
                <span>Semua Kategori</span>
                <span class="text-[10px] px-1.5 py-0.5 rounded-md bg-black/30 text-zinc-300" x-text="channels.length"></span>
            </button>

            <template x-for="cat in categories" :key="cat">
                <button type="button"
                        x-show="cat !== 'all'"
                        @click="activeCategory = cat"
                        :class="activeCategory === cat ? 'bg-red-600 text-white shadow-lg shadow-red-900/40 border-red-500' : 'bg-zinc-900/80 text-zinc-400 hover:text-white border-zinc-800'"
                        class="px-4 py-2 rounded-xl text-xs font-bold border transition shrink-0 flex items-center gap-2">
                    <span x-text="cat"></span>
                    <span class="text-[10px] px-1.5 py-0.5 rounded-md bg-black/30 text-zinc-300"
                          x-text="channels.filter(c => c.category === cat).length"></span>
                </button>
            </template>
        </div>

        {{-- Channel Grid --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3.5 md:gap-4">
            <template x-for="ch in filteredChannels" :key="ch.id">
                <a :href="'/tv/' + ch.slug + (ch.country ? '?country=' + ch.country : '')"
                   class="group relative flex flex-col p-4 rounded-2xl bg-zinc-900/40 hover:bg-zinc-900/90 border border-zinc-800/80 hover:border-red-500/50 transition duration-300 shadow-lg hover:shadow-red-950/20 text-left overflow-hidden">
                    {{-- Hover Accent Glow --}}
                    <div class="pointer-events-none absolute -right-10 -top-10 w-24 h-24 bg-red-600/10 rounded-full blur-2xl group-hover:bg-red-600/25 transition"></div>

                    {{-- Top Badges --}}
                    <div class="flex items-center justify-between gap-1 mb-3">
                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-red-600/20 border border-red-500/30 text-[10px] font-black text-red-400 tracking-wider">
                            <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span>
                            LIVE
                        </span>

                        <span class="text-[10px] text-zinc-400 font-medium px-2 py-0.5 rounded bg-zinc-800/60 truncate max-w-[90px]" x-text="ch.category || 'TV'"></span>
                    </div>

                    {{-- Logo / Monogram Container --}}
                    <div class="w-full h-24 rounded-xl bg-zinc-950/60 border border-zinc-800/60 flex items-center justify-center p-3 mb-3 group-hover:scale-[1.02] transition duration-300 relative overflow-hidden">
                        <template x-if="ch.logo">
                            <img :src="ch.logo"
                                 :alt="ch.name"
                                 class="max-h-16 max-w-full object-contain filter drop-shadow-md"
                                 loading="lazy">
                        </template>
                        <template x-if="!ch.logo">
                            <div class="flex flex-col items-center justify-center text-center">
                                <span class="text-3xl filter drop-shadow-md" x-text="ch.flag || '📺'"></span>
                                <span class="text-[10px] font-black uppercase tracking-wider text-zinc-400 mt-1" x-text="ch.country ? ch.country.toUpperCase() : 'TV'"></span>
                            </div>
                        </template>
                    </div>

                    {{-- Content Info --}}
                    <div class="space-y-1 mt-auto">
                        <h3 class="text-sm font-bold text-white group-hover:text-red-400 transition truncate" x-text="ch.name"></h3>
                        <p class="text-[11px] text-zinc-500 truncate" x-text="ch.tagline || ch.category"></p>
                    </div>

                    {{-- Play Indicator Footer --}}
                    <div class="pt-3 mt-3 border-t border-zinc-800/50 flex items-center justify-between text-[11px] text-zinc-400 group-hover:text-white transition">
                        <span class="text-[10px] text-zinc-500" x-text="ch.servers ? ch.servers.length + ' Server' : '1 Server'"></span>
                        <span class="inline-flex items-center gap-1 font-semibold text-red-500 group-hover:translate-x-0.5 transition">
                            Tonton
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                        </span>
                    </div>
                </a>
            </template>
        </div>

        {{-- Empty State --}}
        <div x-show="filteredChannels.length === 0"
             class="p-12 text-center rounded-3xl bg-zinc-900/30 border border-zinc-800/60 space-y-4">
            <div class="w-16 h-16 mx-auto rounded-full bg-red-600/10 border border-red-500/20 flex items-center justify-center text-red-500 text-2xl">
                📺
            </div>
            <div class="space-y-1">
                <h3 class="text-base font-bold text-white">Tidak ada saluran ditemukan</h3>
                <p class="text-xs text-zinc-500">Coba ganti kata kunci pencarian atau pilih kategori channel lain.</p>
            </div>
            <button type="button"
                    @click="activeCategory = 'all'; searchQuery = ''"
                    class="px-4 py-2 rounded-xl bg-red-600 text-white text-xs font-bold hover:bg-red-700 transition">
                Reset Filter
            </button>
        </div>

        {{-- Feature Highlights --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-6 border-t border-white/5">
            <div class="p-5 rounded-2xl bg-zinc-900/40 border border-zinc-800/80 flex items-start gap-4">
                <div class="w-10 h-10 rounded-xl bg-red-600/10 border border-red-500/20 flex items-center justify-center text-red-500 shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/></svg>
                </div>
                <div class="space-y-1">
                    <h4 class="text-xs font-bold text-white">Direct Stream Client-Side</h4>
                    <p class="text-[11px] text-zinc-400 leading-relaxed">Video stream HLS dimuat langsung oleh browser pengguna tanpa melalui proxy server, menjamin kecepatan tinggi tanpa lag.</p>
                </div>
            </div>

            <div class="p-5 rounded-2xl bg-zinc-900/40 border border-zinc-800/80 flex items-start gap-4">
                <div class="w-10 h-10 rounded-xl bg-red-600/10 border border-red-500/20 flex items-center justify-center text-red-500 shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418"/></svg>
                </div>
                <div class="space-y-1">
                    <h4 class="text-xs font-bold text-white">Hybrid TV Garden & KZSTRM</h4>
                    <p class="text-[11px] text-zinc-400 leading-relaxed">Kombinasi stasiun TV nasional utama Indonesia dengan ribuan siaran televisi dari 170+ negara dunia dalam satu wadah.</p>
                </div>
            </div>

            <div class="p-5 rounded-2xl bg-zinc-900/40 border border-zinc-800/80 flex items-start gap-4">
                <div class="w-10 h-10 rounded-xl bg-red-600/10 border border-red-500/20 flex items-center justify-center text-red-500 shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3"/></svg>
                </div>
                <div class="space-y-1">
                    <h4 class="text-xs font-bold text-white">Multi-Device & Landscape HP</h4>
                    <p class="text-[11px] text-zinc-400 leading-relaxed">Optimasi penuh untuk pengalaman smartphone dengan kunci rotasi landscape layar penuh dan remote visual switcher.</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal All Countries (170+ Negara) --}}
    <div x-show="showCountryModal"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/85 backdrop-blur-md"
         @keydown.escape.window="showCountryModal = false">
        <div @click.away="showCountryModal = false"
             class="w-full max-w-2xl bg-zinc-900 border border-zinc-800 rounded-3xl p-6 shadow-2xl space-y-4 max-h-[85vh] flex flex-col">
            {{-- Modal Header --}}
            <div class="flex items-center justify-between pb-3 border-b border-zinc-800 shrink-0">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-red-600/10 border border-red-500/20 flex items-center justify-center text-red-500 text-lg">
                        🌐
                    </div>
                    <div>
                        <h3 class="text-base font-black text-white">Pilih Negara Siaran TV</h3>
                        <p class="text-xs text-zinc-400">Tersedia 170+ negara di dunia (TV Garden Hybrid)</p>
                    </div>
                </div>
                <button type="button" @click="showCountryModal = false" class="p-2 rounded-xl bg-zinc-800 text-zinc-400 hover:text-white text-xs font-bold">
                    ✕ Tutup
                </button>
            </div>

            {{-- Country Search --}}
            <div class="shrink-0 relative">
                <input type="text"
                       x-model="countrySearch"
                       placeholder="Cari negara (misal: Jepang, Korea, Arab Saudi, Brazil, Jerman)..."
                       class="w-full px-4 py-3 pl-11 bg-zinc-950 border border-zinc-800 rounded-xl text-xs text-white placeholder-zinc-500 focus:outline-none focus:border-red-500">
                <svg class="w-4 h-4 text-zinc-500 absolute left-4 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.2-5.2m2.2-5.3a7.5 7.5 0 11-15 0 7.5 7.5 0 0115 0z" />
                </svg>
            </div>

            {{-- Country Grid List --}}
            <div class="overflow-y-auto pr-1 grid grid-cols-2 sm:grid-cols-3 gap-2 flex-1">
                <template x-for="c in filteredCountries" :key="c.code">
                    <a :href="'/tv?country=' + c.code"
                       :class="selectedCountry === c.code ? 'border-red-500 bg-red-950/30 text-white font-bold ring-1 ring-red-500/40' : 'border-zinc-800/80 bg-zinc-950/50 text-zinc-300 hover:bg-zinc-800 hover:text-white'"
                       class="p-2.5 rounded-xl border flex items-center justify-between text-xs transition">
                        <div class="flex items-center gap-2 truncate">
                            <span class="text-lg shrink-0" x-text="c.flag"></span>
                            <span class="truncate" x-text="c.name"></span>
                        </div>
                        <span class="text-[10px] text-zinc-500 font-mono ml-2 shrink-0 px-1.5 py-0.5 rounded bg-zinc-900" x-text="c.channel_count"></span>
                    </a>
                </template>
            </div>
        </div>
    </div>
</div>
