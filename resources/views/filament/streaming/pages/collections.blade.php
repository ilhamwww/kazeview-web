<div class="min-h-screen bg-[#050505] text-white pt-24 md:pt-28 pb-20 px-4 md:px-6"
     x-data="{
        searchQuery: '',
        activeTab: 'all',
        matches(col) {
            const matchesQuery = !this.searchQuery.trim() || 
                col.title.toLowerCase().includes(this.searchQuery.toLowerCase().trim()) ||
                (col.description && col.description.toLowerCase().includes(this.searchQuery.toLowerCase().trim()));
            
            if (!matchesQuery) return false;
            if (this.activeTab === 'all') return true;
            return col.kind === this.activeTab;
        }
     }">

    <div class="max-w-7xl mx-auto space-y-8">
        <!-- Page Header -->
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 border-b border-zinc-800/80 pb-6">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-red-600/10 border border-red-500/20 text-red-500 text-xs font-bold tracking-wider uppercase mb-3">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    Katalog Waralaba
                </div>
                <h1 class="text-3xl md:text-4xl font-extrabold text-white tracking-tight flex items-center gap-3">
                    Collections
                </h1>
                <p class="text-sm md:text-base text-zinc-400 mt-2 max-w-2xl leading-relaxed">
                    Jelajahi film dan serial yang terangkum dalam semesta sinematik, waralaba, dan saga terkurasi berdasarkan kronologi rilis.
                </p>
            </div>

            <!-- Search Bar -->
            <div class="w-full md:w-80">
                <div class="relative">
                    <svg class="w-4 h-4 text-zinc-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="text"
                           x-model="searchQuery"
                           placeholder="Search collection names..."
                           class="w-full pl-10 pr-4 py-2.5 bg-zinc-900/90 border border-zinc-800 rounded-xl text-sm text-white placeholder-zinc-500 focus:outline-none focus:border-red-500 focus:ring-1 focus:ring-red-500 transition duration-200">
                    <button x-show="searchQuery" @click="searchQuery = ''" class="absolute right-3 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-white p-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Filter Tabs -->
        <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none">
            <button @click="activeTab = 'all'"
                    :class="activeTab === 'all' ? 'bg-red-600 text-white font-bold shadow-lg shadow-red-900/30' : 'bg-zinc-900/70 text-zinc-400 hover:text-white hover:bg-zinc-800 border border-zinc-800/80'"
                    class="px-4 py-2 rounded-xl text-xs font-semibold tracking-wide transition duration-200 whitespace-nowrap">
                Semua Koleksi ({{ count($collections) }})
            </button>
            <button @click="activeTab = 'franchise'"
                    :class="activeTab === 'franchise' ? 'bg-red-600 text-white font-bold shadow-lg shadow-red-900/30' : 'bg-zinc-900/70 text-zinc-400 hover:text-white hover:bg-zinc-800 border border-zinc-800/80'"
                    class="px-4 py-2 rounded-xl text-xs font-semibold tracking-wide transition duration-200 whitespace-nowrap">
                Franchises
            </button>
            <button @click="activeTab = 'universe'"
                    :class="activeTab === 'universe' ? 'bg-red-600 text-white font-bold shadow-lg shadow-red-900/30' : 'bg-zinc-900/70 text-zinc-400 hover:text-white hover:bg-zinc-800 border border-zinc-800/80'"
                    class="px-4 py-2 rounded-xl text-xs font-semibold tracking-wide transition duration-200 whitespace-nowrap">
                Cinematic Universes
            </button>
            <button @click="activeTab = 'creator_era'"
                    :class="activeTab === 'creator_era' ? 'bg-red-600 text-white font-bold shadow-lg shadow-red-900/30' : 'bg-zinc-900/70 text-zinc-400 hover:text-white hover:bg-zinc-800 border border-zinc-800/80'"
                    class="px-4 py-2 rounded-xl text-xs font-semibold tracking-wide transition duration-200 whitespace-nowrap">
                Creator & Era
            </button>
        </div>

        <!-- Collections Grid -->
        @if(empty($collections))
            <div class="py-24 text-center text-zinc-500">
                <svg class="w-16 h-16 mx-auto mb-4 stroke-zinc-700" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                <p class="text-base font-medium">Belum ada koleksi yang tersedia saat ini.</p>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($collections as $col)
                    @php
                        $detailUrl = \App\Filament\Streaming\Pages\CollectionDetail::getUrl(['slug' => $col['slug']], panel: 'streaming');
                        $imageSrc = $col['banner'] ?? $col['poster'] ?? 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?auto=format&fit=crop&w=1280&q=80';
                        $colDataJson = htmlspecialchars(json_encode([
                            'id' => $col['id'],
                            'title' => $col['title'],
                            'slug' => $col['slug'],
                            'description' => $col['description'],
                            'kind' => $col['kind'],
                        ]), ENT_QUOTES, 'UTF-8');
                    @endphp
                    <div x-show="matches({{ $colDataJson }})"
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         class="group relative flex flex-col">
                        
                        <!-- Card Banner Link -->
                        <a href="{{ $detailUrl }}" class="relative w-full aspect-[16/9] rounded-2xl overflow-hidden bg-zinc-900 border border-zinc-800/80 group-hover:border-zinc-700 group-hover:shadow-[0_10px_30px_rgba(0,0,0,0.8)] transition duration-300">
                            <!-- Image -->
                            <img src="{{ $imageSrc }}"
                                 alt="{{ $col['title'] }}"
                                 loading="lazy"
                                 class="w-full h-full object-cover group-hover:scale-105 transition duration-500 ease-out">

                            <!-- Dark Overlay -->
                            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent"></div>

                            <!-- Badges (Top Left & Right) -->
                            <div class="absolute top-3 inset-x-3 flex items-center justify-between">
                                <span class="px-2.5 py-1 text-[10px] font-extrabold text-white bg-black/60 backdrop-blur-md rounded-lg border border-white/10 uppercase tracking-wider">
                                    {{ str_replace('_', ' ', $col['kind']) }}
                                </span>

                                @if(!empty($col['childCount']) && $col['childCount'] > 0)
                                    <span class="px-2.5 py-1 text-[10px] font-bold text-amber-300 bg-amber-950/60 backdrop-blur-md rounded-lg border border-amber-500/20">
                                        {{ $col['childCount'] }} Sections
                                    </span>
                                @endif
                            </div>

                            <!-- Play Icon Hover Pill -->
                            <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition duration-300">
                                <div class="w-12 h-12 rounded-full bg-red-600/90 text-white flex items-center justify-center shadow-xl shadow-red-950 transform group-hover:scale-110 transition duration-200">
                                    <svg class="w-5 h-5 ml-0.5 fill-current" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                </div>
                            </div>
                        </a>

                        <!-- Card Info Below -->
                        <div class="mt-3.5 space-y-1">
                            <h2 class="text-base font-bold text-white group-hover:text-red-500 transition duration-200 truncate leading-snug">
                                <a href="{{ $detailUrl }}">
                                    {{ $col['title'] }}
                                </a>
                            </h2>

                            <!-- Metadata Line -->
                            <div class="flex items-center gap-2 text-xs text-zinc-400">
                                <span class="font-semibold text-zinc-300">{{ $col['itemCount'] }} titles</span>
                                @if(!empty($col['yearRange']))
                                    <span>•</span>
                                    <span>{{ $col['yearRange'] }}</span>
                                @endif
                                @if(!empty($col['childCount']) && $col['childCount'] > 0)
                                    <span>•</span>
                                    <span>{{ $col['childCount'] }} sections</span>
                                @endif
                            </div>

                            @if(!empty($col['description']))
                                <p class="text-xs text-zinc-500 line-clamp-2 leading-relaxed pt-0.5">
                                    {{ $col['description'] }}
                                </p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
