<div class="min-h-screen bg-[#050505] text-white pb-28">
    <div class="max-w-7xl mx-auto px-4 md:px-6 pt-24 md:pt-28">

        {{-- Header --}}
        <div class="mb-8 md:mb-10">
            <div class="flex items-center gap-2 text-xs text-zinc-500 mb-3">
                <a href="{{ \App\Filament\Streaming\Pages\StreamingHome::getUrl(panel: 'streaming') }}" class="hover:text-white transition">Home</a>
                <span>/</span>
                <span class="text-zinc-300 font-semibold">Network</span>
            </div>
            <h1 class="text-3xl md:text-4xl font-extrabold tracking-tight">Streaming Networks</h1>
            <p class="text-sm md:text-base text-zinc-400 mt-2 max-w-2xl">
                Jelajahi film dan serial berdasarkan platform streaming favoritmu.
            </p>
        </div>

        {{-- Network grid --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-5">
            @foreach($networks as $net)
                <a href="{{ \App\Filament\Streaming\Pages\NetworkDetail::getUrl(parameters: ['slug' => $net['slug']], panel: 'streaming') }}"
                   aria-label="Buka katalog {{ $net['name'] }}"
                   class="group relative block overflow-hidden rounded-2xl border border-white/10 bg-gradient-to-br {{ $net['accent'] }} {{ $net['border'] }} transition duration-300 active:scale-[0.98] focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500">

                    {{-- Brand glow --}}
                    <div class="absolute -top-16 -right-16 w-48 h-48 rounded-full blur-3xl opacity-30 group-hover:opacity-50 transition"
                         style="background: {{ $net['color'] }}"></div>

                    <div class="relative p-6 md:p-7 flex flex-col h-full min-h-[190px]">
                        <div class="flex items-center justify-between gap-3">
                            <div class="text-white h-10 flex items-center" style="color: {{ $net['slug'] === 'netflix' ? $net['color'] : '#fff' }}">
                                {!! $net['logo_svg'] !!}
                            </div>
                            <span class="shrink-0 text-[11px] font-bold text-zinc-200 bg-black/40 border border-white/10 px-2.5 py-1 rounded-full">
                                {{ $net['badge'] }}
                            </span>
                        </div>

                        <div class="mt-auto pt-6">
                            <h2 class="text-lg font-bold text-white">{{ $net['name'] }}</h2>
                            <p class="text-xs md:text-sm text-zinc-400 mt-1 line-clamp-2">{{ $net['description'] }}</p>
                        </div>

                        <div class="mt-4 inline-flex items-center gap-1.5 text-xs font-semibold text-zinc-300 group-hover:text-white transition">
                            Lihat katalog
                            <svg class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</div>
