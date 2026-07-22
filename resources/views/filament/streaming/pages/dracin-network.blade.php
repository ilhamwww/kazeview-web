<div class="min-h-screen max-w-[1800px] pt-28 pb-16 px-4 md:px-8 max-w-7xl mx-auto">
        <div class="flex flex-col md:flex-row items-center gap-8">
            <!-- Logo / Image -->
            <div class="w-48 h-48 rounded-2xl bg-black border border-zinc-800 flex items-center justify-center overflow-hidden flex-shrink-0 shadow-2xl">
                @if(!empty($network['logo']))
                    <img src="{{ url('/api/proxy-image?url=' . urlencode($network['logo'])) }}" alt="{{ $network['name'] }}" class="w-full h-full object-contain p-4" referrerpolicy="no-referrer">
                @else
                    <div class="text-zinc-600 flex flex-col items-center gap-2">
                        <svg class="w-16 h-16 opacity-40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 100-6 3 3 0 000 6z"/>
                        </svg>
                        <span class="text-xs uppercase tracking-widest font-semibold">No Logo</span>
                    </div>
                @endif
            </div>

            <!-- Detail Info -->
            <div class="flex-1 text-center md:text-left space-y-4">
                <div class="inline-flex items-center gap-2 px-3 py-1 bg-red-600/10 border border-red-500/25 rounded-full text-red-500 text-xs font-semibold uppercase tracking-wider">
                    Network Detail
                </div>
                <h1 class="text-4xl md:text-5xl font-extrabold tracking-tight">{{ $network['name'] }}</h1>
                <p class="text-zinc-400 text-sm max-w-2xl">
                    Halaman ini menampilkan drama-drama yang tersedia dari jaringan penyiaran <span class="text-white font-semibold">{{ $network['name'] }}</span>.
                </p>

                <div class="flex flex-wrap items-center justify-center md:justify-start gap-4 pt-2">
                    <div class="flex items-center gap-2 bg-zinc-950 border border-zinc-800 px-4 py-2 rounded-xl text-xs text-zinc-400">
                        <span class="text-zinc-500 font-semibold uppercase">ID NETWORK:</span>
                        <span>{{ $network['id'] }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Divider -->
        <hr class="border-zinc-800 my-10">

        <!-- Selector Bahasa (Khusus GoodShort) & Search bar -->
        <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <!-- Search bar -->
            <div class="relative w-full max-w-md">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input 
                    type="text" 
                    wire:model.live.debounce.500ms="search" 
                    placeholder="Cari drama..." 
                    class="block w-full pl-10 pr-10 py-2.5 bg-zinc-950/60 border border-zinc-800 rounded-xl text-zinc-200 placeholder-zinc-500 focus:outline-none focus:border-red-600/50 focus:ring-1 focus:ring-red-600/50 transition-colors text-sm"
                >
                @if(!empty($search))
                    <button 
                        wire:click="$set('search', '')" 
                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-zinc-500 hover:text-zinc-300 transition-colors"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                @endif
            </div>

            <!-- Selector Bahasa (GoodShort) -->
            @if($slugVal === 'goodshort')
                <div class="flex items-center gap-2">
                    <span class="text-xs text-zinc-400 font-medium">Bahasa:</span>
                    <div class="inline-flex rounded-lg p-0.5 bg-zinc-950 border border-zinc-800">
                        @foreach(['id' => 'Indonesia', 'en' => 'English', 'es' => 'Español', 'ko' => 'Korean'] as $langKey => $langLabel)
                            <button 
                                wire:click="changeLang('{{ $langKey }}')"
                                class="px-3 py-1 text-xs font-bold rounded-md transition-all duration-200
                                    {{ $lang === $langKey 
                                        ? 'bg-zinc-800 text-white' 
                                        : 'text-zinc-400 hover:text-white' }}"
                            >
                                {{ $langLabel }}
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Tab bar (khusus shortmax: popular / new / hot / vip) -->
        @if($slugVal === 'shortmax')
            <div class="mb-8 flex flex-wrap items-center gap-2">
                @foreach(['popular' => 'Popular', 'new' => 'New', 'hot' => 'Hot', 'vip' => 'VIP'] as $tabKey => $tabLabel)
                    <button
                        wire:click="setTab('{{ $tabKey }}')"
                        wire:loading.attr="disabled"
                        wire:target="setTab"
                        class="px-5 py-2 rounded-full text-sm font-bold border transition-all duration-200
                            {{ $tab === $tabKey
                                ? 'bg-red-600 text-white border-red-600 shadow-md shadow-red-600/20'
                                : 'bg-zinc-900/60 text-zinc-300 border-zinc-800 hover:border-zinc-600 hover:text-white' }}">
                        {{ $tabLabel }}
                    </button>
                @endforeach
            </div>
        @endif

        <!-- Grid placeholder for dramas list -->
        <div class="space-y-6" wire:loading.class="opacity-50" wire:target="setTab">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-bold tracking-wide">
                    @if(!empty($search))
                        Hasil Pencarian
                    @else
                        Daftar Drama
                    @endif
                </h2>
                <span class="text-xs text-zinc-500 font-mono">{{ count($dramas) }} drama ditemukan</span>
            </div>
            
            @if(empty($dramas))
                <div class="py-16 text-center border border-dashed border-zinc-800 rounded-2xl flex flex-col items-center justify-center gap-3">
                    <div class="p-4 bg-zinc-950 border border-zinc-800 rounded-full text-zinc-600">
                        <svg class="w-8 h-8 opacity-60 animate-pulse" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold text-zinc-300">
                        @if(!empty($search))
                            Drama Tidak Ditemukan
                        @else
                            Belum Ada Drama
                        @endif
                    </h3>
                    <p class="text-xs text-zinc-500 max-w-sm">
                        @if(!empty($search))
                            Tidak ada drama yang cocok dengan kata kunci "{{ $search }}". Silakan coba kata kunci lain.
                        @else
                            Drama untuk network ini sedang dalam proses penyiapan dan akan segera tersedia.
                        @endif
                    </p>
                </div>
            @else
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3 md:gap-6">
                    @foreach($dramas as $drama)
                        <a href="{{ \App\Filament\Streaming\Pages\DracinDetail::getUrl(['network_slug' => $slugVal, 'id' => $drama['id']]) }}" 
                           class="group bg-zinc-900/40 border border-zinc-800 hover:border-red-600/50 hover:bg-zinc-900/80 rounded-2xl p-3 flex flex-col justify-between gap-3 transition-all duration-300 hover:scale-105 shadow-lg">
                            
                            <!-- Cover Container -->
                            <div class="w-full aspect-[3/4] rounded-xl bg-black border border-zinc-800 flex items-center justify-center overflow-hidden relative group-hover:scale-95 transition-transform duration-300">
                                @if(!empty($drama['cover']))
                                    <img src="{{ url('/api/proxy-image?url=' . urlencode($drama['cover'])) }}" alt="{{ $drama['name'] }}" class="w-full h-full object-cover" referrerpolicy="no-referrer">
                                @else
                                    <div class="text-zinc-700 flex flex-col items-center gap-1.5">
                                        <svg class="w-10 h-10 opacity-40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 100-6 3 3 0 000 6z"/>
                                        </svg>
                                        <span class="text-[9px] uppercase tracking-widest font-bold">No Cover</span>
                                    </div>
                                @endif
                                
                                @if(!empty($drama['episodes']))
                                    <span class="absolute bottom-2 right-2 px-2 py-0.5 bg-black/80 border border-zinc-800 text-[10px] font-bold text-red-500 rounded-md tracking-wider">
                                        {{ $drama['episodes'] }} Eps
                                    </span>
                                @endif
                                
                                <div class="absolute top-2 left-2 flex flex-col gap-1 z-10">
                                    @if(!empty($drama['is_hot']) || !empty($drama['label_hot']))
                                        <span class="px-2 py-0.5 bg-red-600/90 border border-red-500/30 text-[9px] font-extrabold text-white rounded-md uppercase tracking-wider shadow-sm backdrop-blur-sm">
                                            HOT
                                        </span>
                                    @endif
                                    @if(!empty($drama['is_new']) || !empty($drama['label_new']))
                                        <span class="px-2 py-0.5 bg-emerald-600/90 border border-emerald-500/30 text-[9px] font-extrabold text-white rounded-md uppercase tracking-wider shadow-sm backdrop-blur-sm">
                                            NEW
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <!-- Details -->
                            <div class="text-left w-full space-y-1">
                                <h3 class="text-sm font-bold text-zinc-200 group-hover:text-white line-clamp-2 transition-colors duration-200" title="{{ $drama['name'] }}">
                                    {{ $drama['name'] }}
                                </h3>
                                @if(!empty($drama['intro']))
                                    <p class="text-[11px] text-zinc-500 line-clamp-2 font-light">
                                        {{ $drama['intro'] }}
                                    </p>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>

                @if($hasMore)
                    <div class="mt-12 text-center">
                        <button wire:click="loadDramas" wire:loading.attr="disabled" class="px-8 py-3 bg-red-600 hover:bg-red-700 active:scale-95 text-white font-bold rounded-xl transition duration-150 inline-flex items-center gap-2 text-sm">
                            <span wire:loading.remove wire:target="loadDramas">Load More</span>
                            <span wire:loading wire:target="loadDramas" class="inline-block animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></span>
                            <span wire:loading wire:target="loadDramas">Loading...</span>
                        </button>
                    </div>
                @endif
            @endif
        </div>
</div>
