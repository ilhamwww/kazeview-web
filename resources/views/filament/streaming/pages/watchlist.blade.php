<div class="min-h-screen bg-[#050505] text-white pt-24 md:pt-28 pb-24 px-4 md:px-6">
    <div class="max-w-7xl mx-auto space-y-8">

        <!-- Top Header & Actions -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-zinc-800/80 pb-6">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-red-600/10 border border-red-500/20 text-red-500 text-xs font-bold uppercase tracking-wider mb-2">
                    <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                    <span>Koleksi Favorit</span>
                </div>
                <h1 class="text-2xl md:text-3xl font-black tracking-tight text-white flex items-center gap-3">
                    Daftar Tontonan Saya
                    @auth
                        <span class="text-sm font-mono font-normal text-zinc-400 bg-zinc-900 px-3 py-1 rounded-full border border-zinc-800">
                            {{ $this->counts['all'] }} Tersimpan
                        </span>
                    @endauth
                </h1>
                <p class="text-xs md:text-sm text-zinc-400 mt-1">
                    Semua film, serial TV, drama pendek, dan anime yang kamu tandai untuk ditonton.
                </p>
            </div>

            @auth
                @if($this->counts['all'] > 0)
                    <div class="flex items-center gap-3">
                        <button wire:click="clearAllFavorites"
                                wire:confirm="Yakin ingin menghapus seluruh daftar tontonan yang tersimpan?"
                                class="px-4 py-2 rounded-xl bg-zinc-900 hover:bg-red-950/40 text-zinc-400 hover:text-red-400 border border-zinc-800 hover:border-red-900/50 text-xs font-semibold transition duration-200 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            Bersihkan Semua
                        </button>
                    </div>
                @endif
            @endauth
        </div>

        @guest
            <!-- Guest State Banner -->
            <div class="bg-gradient-to-br from-zinc-900/90 to-zinc-950/90 border border-zinc-800 rounded-3xl p-8 md:p-12 text-center max-w-2xl mx-auto space-y-6 shadow-2xl">
                <div class="w-20 h-20 mx-auto rounded-3xl bg-red-600/10 border border-red-500/20 flex items-center justify-center text-red-500 shadow-inner">
                    <svg class="w-10 h-10 fill-current" viewBox="0 0 24 24">
                        <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                    </svg>
                </div>
                <div class="space-y-2">
                    <h2 class="text-xl md:text-2xl font-bold text-white">Simpan Tontonan Favoritmu</h2>
                    <p class="text-xs md:text-sm text-zinc-400 max-w-md mx-auto">
                        Masuk ke akun KZSTRM kamu untuk menandai film, serial, anime, dan dracin favoritmu agar bisa ditonton kapan saja.
                    </p>
                </div>
                <div class="flex flex-col sm:flex-row items-center justify-center gap-3 pt-2">
                    <a href="/login" class="w-full sm:w-auto px-6 py-3 rounded-xl bg-red-600 hover:bg-red-700 text-white font-bold text-sm shadow-lg shadow-red-600/20 transition">
                        Masuk ke Akun
                    </a>
                    <a href="/movies" class="w-full sm:w-auto px-6 py-3 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-zinc-300 font-semibold text-sm transition">
                        Jelajah Konten
                    </a>
                </div>
            </div>
        @else
            <!-- Filter Tabs -->
            <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none">
                <button wire:click="setFilter('all')"
                        class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 flex-shrink-0 {{ $this->activeFilter === 'all' ? 'bg-red-600 text-white shadow-lg shadow-red-600/20' : 'bg-zinc-900/80 hover:bg-zinc-800 text-zinc-400 hover:text-white border border-zinc-800' }}">
                    <span>Semua</span>
                    <span class="px-1.5 py-0.5 rounded-md text-[10px] {{ $this->activeFilter === 'all' ? 'bg-red-700 text-white' : 'bg-zinc-800 text-zinc-400' }}">
                        {{ $this->counts['all'] }}
                    </span>
                </button>

                <button wire:click="setFilter('movie')"
                        class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 flex-shrink-0 {{ $this->activeFilter === 'movie' ? 'bg-red-600 text-white shadow-lg shadow-red-600/20' : 'bg-zinc-900/80 hover:bg-zinc-800 text-zinc-400 hover:text-white border border-zinc-800' }}">
                    <span>Film (Movies)</span>
                    <span class="px-1.5 py-0.5 rounded-md text-[10px] {{ $this->activeFilter === 'movie' ? 'bg-red-700 text-white' : 'bg-zinc-800 text-zinc-400' }}">
                        {{ $this->counts['movie'] }}
                    </span>
                </button>

                <button wire:click="setFilter('tv_series')"
                        class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 flex-shrink-0 {{ $this->activeFilter === 'tv_series' ? 'bg-red-600 text-white shadow-lg shadow-red-600/20' : 'bg-zinc-900/80 hover:bg-zinc-800 text-zinc-400 hover:text-white border border-zinc-800' }}">
                    <span>TV Series</span>
                    <span class="px-1.5 py-0.5 rounded-md text-[10px] {{ $this->activeFilter === 'tv_series' ? 'bg-red-700 text-white' : 'bg-zinc-800 text-zinc-400' }}">
                        {{ $this->counts['tv_series'] }}
                    </span>
                </button>

                <button wire:click="setFilter('dracin')"
                        class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 flex-shrink-0 {{ $this->activeFilter === 'dracin' ? 'bg-red-600 text-white shadow-lg shadow-red-600/20' : 'bg-zinc-900/80 hover:bg-zinc-800 text-zinc-400 hover:text-white border border-zinc-800' }}">
                    <span>Short Drama / Dracin</span>
                    <span class="px-1.5 py-0.5 rounded-md text-[10px] {{ $this->activeFilter === 'dracin' ? 'bg-red-700 text-white' : 'bg-zinc-800 text-zinc-400' }}">
                        {{ $this->counts['dracin'] }}
                    </span>
                </button>

                <button wire:click="setFilter('anime')"
                        class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 flex-shrink-0 {{ $this->activeFilter === 'anime' ? 'bg-red-600 text-white shadow-lg shadow-red-600/20' : 'bg-zinc-900/80 hover:bg-zinc-800 text-zinc-400 hover:text-white border border-zinc-800' }}">
                    <span>Anime</span>
                    <span class="px-1.5 py-0.5 rounded-md text-[10px] {{ $this->activeFilter === 'anime' ? 'bg-red-700 text-white' : 'bg-zinc-800 text-zinc-400' }}">
                        {{ $this->counts['anime'] }}
                    </span>
                </button>
            </div>

            <!-- Content Grid / Empty State -->
            @if($this->filteredFavorites->isEmpty())
                <div class="bg-zinc-950/60 border border-zinc-800/80 rounded-3xl p-12 text-center space-y-4">
                    <div class="w-16 h-16 mx-auto rounded-2xl bg-zinc-900 border border-zinc-800 flex items-center justify-center text-zinc-600">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0z" /></svg>
                    </div>
                    <div class="space-y-1">
                        <h3 class="text-base font-bold text-white">Tidak ada tontonan di kategori ini</h3>
                        <p class="text-xs text-zinc-500 max-w-sm mx-auto">
                            Kamu belum menyimpan konten apa pun di kategori {{ $this->activeFilter === 'all' ? 'ini' : $this->activeFilter }}.
                        </p>
                    </div>
                    <div class="pt-2 flex items-center justify-center gap-3">
                        <a href="/dracin" class="px-4 py-2 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-xs font-semibold text-zinc-300 transition">
                            Cari Dracin
                        </a>
                        <a href="/movies" class="px-4 py-2 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-xs font-semibold text-zinc-300 transition">
                            Cari Film
                        </a>
                    </div>
                </div>
            @else
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4 md:gap-5">
                    @foreach($this->filteredFavorites as $fav)
                        @php
                            $detailUrl = '#';
                            try {
                                if ($fav->item_type === 'movie') {
                                    $detailUrl = \App\Filament\Streaming\Pages\WatchMovie::getUrl(['slug' => $fav->item_id], panel: 'streaming');
                                } elseif ($fav->item_type === 'tv_series') {
                                    $detailUrl = \App\Filament\Streaming\Pages\ShowSeries::getUrl(['slug' => $fav->item_id], panel: 'streaming');
                                } elseif ($fav->item_type === 'anime') {
                                    $detailUrl = \App\Filament\Streaming\Pages\AnimeDetail::getUrl(['slug' => $fav->item_id], panel: 'streaming');
                                } elseif ($fav->item_type === 'bstation') {
                                    $detailUrl = \App\Filament\Streaming\Pages\BstationDetail::getUrl(['id' => $fav->item_id], panel: 'streaming');
                                } elseif ($fav->item_type === 'dracin') {
                                    $dracinParts = explode(':', $fav->item_id);
                                    $networkSlug = $dracinParts[0] ?? '';
                                    $dramaId = $dracinParts[1] ?? '';
                                    $detailUrl = \App\Filament\Streaming\Pages\DracinDetail::getUrl(['network_slug' => $networkSlug, 'id' => $dramaId], panel: 'streaming');
                                }
                            } catch (\Throwable $e) {
                                $detailUrl = $fav->url ?? '#';
                            }

                            $badgeType = match($fav->item_type) {
                                'movie' => ['label' => 'Movie', 'bg' => 'bg-red-600/80 text-white'],
                                'tv_series' => ['label' => 'Series', 'bg' => 'bg-blue-600/80 text-white'],
                                'dracin' => ['label' => 'Dracin', 'bg' => 'bg-amber-600/80 text-white'],
                                'anime', 'bstation' => ['label' => 'Anime', 'bg' => 'bg-purple-600/80 text-white'],
                                default => ['label' => 'Video', 'bg' => 'bg-zinc-700 text-white'],
                            };

                            $thumbUrl = $fav->thumbnail;
                            if ($thumbUrl && !str_starts_with($thumbUrl, '/') && !str_contains($thumbUrl, '127.0.0.1')) {
                                $thumbUrl = url('/api/proxy-image?url=' . urlencode($thumbUrl));
                            }
                        @endphp

                        <div class="group relative flex flex-col bg-zinc-900/40 border border-zinc-800 hover:border-red-600/50 rounded-2xl overflow-hidden transition-all duration-300 hover:scale-[1.02] shadow-lg">
                            <!-- Thumbnail with Actions Overlay -->
                            <div class="relative w-full aspect-[2/3] bg-zinc-950 overflow-hidden">
                                @if($thumbUrl)
                                    <img src="{{ $thumbUrl }}"
                                         alt="{{ $fav->title }}"
                                         class="w-full h-full object-cover group-hover:scale-105 transition duration-500 ease-out"
                                         loading="lazy"
                                         referrerpolicy="no-referrer">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-zinc-600 text-xs">
                                        No Image
                                    </div>
                                @endif

                                <!-- Category Badge -->
                                <div class="absolute top-2 left-2">
                                    <span class="text-[9px] font-black uppercase px-2 py-0.5 rounded-md backdrop-blur-md {{ $badgeType['bg'] }}">
                                        {{ $badgeType['label'] }}
                                    </span>
                                </div>

                                <!-- Remove Bookmark Button (Top Right) -->
                                <button wire:click="removeFavorite({{ $fav->id }})"
                                        wire:confirm="Hapus {{ $fav->title }} dari daftar tontonan?"
                                        class="absolute top-2 right-2 w-7 h-7 rounded-full bg-black/60 hover:bg-red-600 text-zinc-300 hover:text-white flex items-center justify-center backdrop-blur-md transition duration-200"
                                        title="Hapus dari Bookmark">
                                    <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                                </button>

                                <!-- Play Hover Overlay -->
                                <a href="{{ $detailUrl }}" class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition duration-300 flex items-center justify-center">
                                    <div class="w-12 h-12 rounded-full bg-red-600 text-white flex items-center justify-center shadow-xl transform scale-75 group-hover:scale-100 transition duration-300">
                                        <svg class="w-5 h-5 fill-current translate-x-0.5" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                    </div>
                                </a>
                            </div>

                            <!-- Content Meta -->
                            <div class="p-3 flex flex-col justify-between flex-1 gap-2">
                                <div>
                                    <a href="{{ $detailUrl }}" class="block">
                                        <h3 class="text-xs md:text-sm font-bold text-white group-hover:text-red-400 transition line-clamp-2 leading-snug" title="{{ $fav->title }}">
                                            {{ $fav->title }}
                                        </h3>
                                    </a>
                                </div>

                                <div class="flex items-center justify-between pt-1 border-t border-zinc-800/60 text-[10px] text-zinc-500">
                                    <span>Tersimpan</span>
                                    <span class="font-mono">{{ $fav->created_at ? $fav->created_at->diffForHumans() : 'baru saja' }}</span>
                                </div>

                                <div class="pt-1">
                                    <a href="{{ $detailUrl }}" class="w-full py-2 px-3 rounded-xl bg-zinc-800/80 hover:bg-red-600 active:scale-95 text-zinc-300 hover:text-white text-xs font-bold transition duration-200 flex items-center justify-center gap-1.5 shadow-sm">
                                        <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                        <span>Tonton</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        @endguest

    </div>
</div>
