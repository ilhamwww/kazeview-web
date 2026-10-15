<div class="min-h-screen bg-[#050505] text-white pb-32 md:pb-20 relative overflow-x-hidden"
     x-data="{
        activeCategory: 'all',
        searchQuery: '',
        channels: {{ Js::from($channels) }},
        categories: {{ Js::from($categories) }},
        get filteredChannels() {
            return this.channels.filter(ch => {
                const matchesCat = this.activeCategory === 'all' || ch.category.toLowerCase() === this.activeCategory.toLowerCase();
                const q = this.searchQuery.toLowerCase().trim();
                const matchesSearch = !q || ch.name.toLowerCase().includes(q) || ch.category.toLowerCase().includes(q) || (ch.description && ch.description.toLowerCase().includes(q));
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
                    <span>Siaran Langsung 24 Jam</span>
                </div>
                <h1 class="text-3xl md:text-5xl font-black tracking-tight text-white leading-tight">
                    TV Indonesia Live
                </h1>
                <p class="text-xs md:text-sm text-zinc-400 leading-relaxed">
                    Tonton stasiun televisi nasional, kanal berita independen, hiburan keluarga, dan siaran olahraga favorit tanpa buffering langsung dari perangkat Anda.
                </p>
            </div>

            {{-- Channel Search Input --}}
            <div class="w-full md:w-80 relative">
                <input type="text"
                       x-model="searchQuery"
                       placeholder="Cari stasiun TV..."
                       class="w-full px-4 py-3 pl-11 bg-zinc-900/80 border border-zinc-800 rounded-2xl text-xs md:text-sm text-white placeholder-zinc-500 focus:outline-none focus:border-red-500 focus:ring-1 focus:ring-red-500 transition shadow-inner">
                <svg class="w-4 h-4 text-zinc-500 absolute left-4 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.2-5.2m2.2-5.3a7.5 7.5 0 11-15 0 7.5 7.5 0 0115 0z" />
                </svg>
                <button type="button" x-show="searchQuery.length > 0" @click="searchQuery = ''" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-zinc-500 hover:text-white text-xs">
                    ✕
                </button>
            </div>
        </div>

        {{-- Category Pills Filter --}}
        <div class="flex items-center gap-2 overflow-x-auto no-scrollbar py-1">
            <button type="button"
                    @click="activeCategory = 'all'"
                    :class="activeCategory === 'all' ? 'bg-red-600 text-white shadow-lg shadow-red-900/40 border-red-500' : 'bg-zinc-900/80 text-zinc-400 hover:text-white border-zinc-800'"
                    class="px-4 py-2 rounded-xl text-xs font-bold border transition shrink-0 flex items-center gap-2">
                <span>Semua Channel</span>
                <span class="text-[10px] px-1.5 py-0.5 rounded-md bg-black/30 text-zinc-300" x-text="channels.length"></span>
            </button>

            <template x-for="cat in categories" :key="cat">
                <button type="button"
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
                <a :href="'/tv/' + ch.slug"
                   class="group relative flex flex-col p-4 rounded-2xl bg-zinc-900/40 hover:bg-zinc-900/90 border border-zinc-800/80 hover:border-red-500/50 transition duration-300 shadow-lg hover:shadow-red-950/20 text-left overflow-hidden">
                    {{-- Hover Accent Glow --}}
                    <div class="pointer-events-none absolute -right-10 -top-10 w-24 h-24 bg-red-600/10 rounded-full blur-2xl group-hover:bg-red-600/25 transition"></div>

                    {{-- Top Badges --}}
                    <div class="flex items-center justify-between gap-1 mb-3">
                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-red-600/20 border border-red-500/30 text-[10px] font-black text-red-400 tracking-wider">
                            <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span>
                            LIVE
                        </span>

                        <span class="text-[10px] text-zinc-500 font-medium px-2 py-0.5 rounded bg-zinc-800/60" x-text="ch.category"></span>
                    </div>

                    {{-- Logo Container --}}
                    <div class="w-full h-24 rounded-xl bg-zinc-950/60 border border-zinc-800/60 flex items-center justify-center p-3 mb-3 group-hover:scale-[1.02] transition duration-300">
                        <img :src="ch.logo"
                             :alt="ch.name"
                             class="max-h-16 max-w-full object-contain filter drop-shadow-md"
                             loading="lazy">
                    </div>

                    {{-- Content Info --}}
                    <div class="space-y-1 mt-auto">
                        <h3 class="text-sm font-bold text-white group-hover:text-red-400 transition truncate" x-text="ch.name"></h3>
                        <p class="text-[11px] text-zinc-500 truncate" x-text="ch.tagline || ch.category"></p>
                    </div>

                    {{-- Play Indicator Footer --}}
                    <div class="pt-3 mt-3 border-t border-zinc-800/50 flex items-center justify-between text-[11px] text-zinc-400 group-hover:text-white transition">
                        <span class="text-[10px] text-zinc-500" x-text="ch.servers.length + ' Server'"></span>
                        <span class="inline-flex items-center gap-1 font-semibold text-red-500 group-hover:translate-x-0.5 transition">
                            Tonton
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                        </span>
                    </div>
                </a>
            </template>
        </div>

        {{-- Empty State --}}
        <div x-show="filteredChannels.length === 0" class="text-center py-16 space-y-3" style="display: none;">
            <div class="w-12 h-12 rounded-2xl bg-zinc-900 border border-zinc-800 flex items-center justify-center mx-auto text-zinc-500">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </div>
            <p class="text-sm font-bold text-zinc-300">Tidak ada stasiun TV yang cocok</p>
            <p class="text-xs text-zinc-500">Coba ganti kata kunci pencarian atau pilih kategori lain.</p>
        </div>

        {{-- Feature Highlights --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-6">
            <div class="p-5 rounded-2xl bg-zinc-900/30 border border-zinc-800/60 flex items-start gap-4">
                <div class="w-10 h-10 rounded-xl bg-red-950/60 border border-red-500/20 flex items-center justify-center text-red-500 shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                    </svg>
                </div>
                <div class="space-y-1">
                    <h4 class="text-xs md:text-sm font-bold text-white">Direct CDN Streaming</h4>
                    <p class="text-[11px] md:text-xs text-zinc-400 leading-relaxed">Video diputar langsung dari jaringan resmi stasiun TV tanpa jeda dan tanpa kompresi tambahan.</p>
                </div>
            </div>

            <div class="p-5 rounded-2xl bg-zinc-900/30 border border-zinc-800/60 flex items-start gap-4">
                <div class="w-10 h-10 rounded-xl bg-blue-950/60 border border-blue-500/20 flex items-center justify-center text-blue-400 shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
                    </svg>
                </div>
                <div class="space-y-1">
                    <h4 class="text-xs md:text-sm font-bold text-white">Auto Landscape di HP</h4>
                    <p class="text-[11px] md:text-xs text-zinc-400 leading-relaxed">Tekan tombol fullscreen untuk sensasi menonton TV layar penuh dengan orientasi landscape otomatis.</p>
                </div>
            </div>

            <div class="p-5 rounded-2xl bg-zinc-900/30 border border-zinc-800/60 flex items-start gap-4">
                <div class="w-10 h-10 rounded-xl bg-emerald-950/60 border border-emerald-500/20 flex items-center justify-center text-emerald-400 shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                    </svg>
                </div>
                <div class="space-y-1">
                    <h4 class="text-xs md:text-sm font-bold text-white">Remote TV Cepat</h4>
                    <p class="text-[11px] md:text-xs text-zinc-400 leading-relaxed">Pindah stasiun TV seketika dari sidebar pemutar tanpa perlu kembali ke halaman katalog.</p>
                </div>
            </div>
        </div>
    </div>
</div>
