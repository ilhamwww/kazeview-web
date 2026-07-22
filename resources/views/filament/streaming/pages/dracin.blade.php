<div class="min-h-screen bg-[#050505] text-white pt-28 pb-16 px-6 max-w-[1800px] mx-auto">
    <div class="flex items-center justify-between mb-8 border-b border-zinc-800 pb-4">
        <h1 class="text-3xl font-extrabold text-white tracking-wide border-l-4 border-red-600 pl-4">Drama China (Dracin) - Networks</h1>
    </div>

    @if(empty($networks))
        <!-- Empty State -->
        <div class="flex flex-col items-center justify-center py-32 text-center bg-zinc-900/40 rounded-2xl border border-white/5">
            <svg class="w-16 h-16 text-zinc-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
            </svg>
            <h3 class="text-xl font-bold text-gray-300 mb-2">Belum Ada Network</h3>
            <p class="text-zinc-500 max-w-sm mb-6">Jaringan penyiaran drama belum ditemukan di API status.</p>
            <a href="/admin/scraper-setting-page" class="px-5 py-2.5 bg-red-600 hover:bg-red-700 transition rounded-xl text-xs font-bold uppercase tracking-wider">
                Buka Scraper Settings
            </a>
        </div>
    @else
        <!-- Networks Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-6">
            @foreach($networks as $network)
                @if($network['is_online'])
                    <a href="{{ $network['id'] === 'bstation' ? '/streaming/bstation' : \App\Filament\Streaming\Pages\DracinNetwork::getUrl(['slug' => $network['id']]) }}" 
                       class="group bg-zinc-900/40 border border-zinc-800 hover:border-red-600/50 hover:bg-zinc-900/80 rounded-2xl p-4 flex flex-col items-center justify-between gap-4 transition-all duration-300 hover:scale-105 shadow-lg">
                @else
                    <div class="relative group bg-zinc-950/40 border border-zinc-900/60 rounded-2xl p-4 flex flex-col items-center justify-between gap-4 opacity-50 cursor-not-allowed shadow-inner select-none filter grayscale transition-all duration-300">
                        <!-- Offline Badge -->
                        <span class="absolute top-2 right-2 px-2 py-0.5 bg-zinc-800/80 border border-zinc-700/50 text-[9px] font-bold text-zinc-400 rounded-md tracking-wider uppercase">Offline</span>
                @endif
                    
                    <!-- Logo Container -->
                    <div class="w-full aspect-square rounded-xl bg-black/60 border border-zinc-800/85 flex items-center justify-center overflow-hidden p-4 group-hover:scale-95 transition-transform duration-300">
                        @if(!empty($network['logo']))
                            <img src="{{ url('/api/proxy-image?url=' . urlencode($network['logo'])) }}" alt="{{ $network['name'] }}" class="w-full h-full object-contain" referrerpolicy="no-referrer">
                        @else
                            <div class="text-zinc-700 flex flex-col items-center gap-1.5">
                                <svg class="w-10 h-10 opacity-40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 100-6 3 3 0 000 6z"/>
                                </svg>
                                <span class="text-[9px] uppercase tracking-widest font-bold">No Image</span>
                            </div>
                        @endif
                    </div>

                    <!-- Details -->
                    <div class="text-center w-full">
                        <h3 class="text-sm font-bold text-zinc-200 group-hover:text-white truncate transition-colors duration-200">{{ $network['name'] }}</h3>
                        @if($network['is_online'])
                            <span class="text-[10px] text-zinc-500 font-medium font-mono uppercase tracking-wider group-hover:text-red-500 transition-colors duration-200">Lihat Drama</span>
                        @else
                            <span class="text-[10px] text-zinc-500 font-medium font-mono uppercase tracking-wider">Tidak Tersedia</span>
                        @endif
                    </div>
                @if($network['is_online'])
                    </a>
                @else
                    </div>
                @endif
            @endforeach
        </div>
    @endif
</div>