<div class="min-h-screen bg-[#050505] text-white pb-28">
    <div class="max-w-7xl mx-auto px-4 md:px-6 pt-24 md:pt-28">

        {{-- Header Ala /network --}}
        <div class="mb-8 md:mb-10">
            <div class="flex items-center gap-2 text-xs text-zinc-500 mb-3">
                <a href="{{ \App\Filament\Streaming\Pages\StreamingHome::getUrl(panel: 'streaming') }}" class="hover:text-white transition">Home</a>
                <span>/</span>
                <span class="text-zinc-300 font-semibold">Indonesia</span>
            </div>
            <div class="flex items-center gap-3">
                <h1 class="text-3xl md:text-4xl font-extrabold tracking-tight">Indonesian Networks</h1>
                <span class="text-xs font-bold text-red-400 bg-red-600/20 border border-red-500/30 px-2.5 py-1 rounded-full uppercase tracking-wider">
                    Lokal & Bioskop
                </span>
            </div>
            <p class="text-sm md:text-base text-zinc-400 mt-2 max-w-2xl">
                Jelajahi film bioskop dan web series lokal berdasarkan platform provider favoritmu.
            </p>
        </div>

        {{-- Network Grid Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 md:gap-6">
            @foreach($networks as $net)
                <a href="{{ $net['url'] }}"
                   aria-label="Buka katalog {{ $net['name'] }}"
                   class="group relative block overflow-hidden rounded-2xl border border-white/10 bg-gradient-to-br {{ $net['accent'] }} {{ $net['border'] }} transition duration-300 hover:scale-[1.01] active:scale-[0.98] focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500">

                    {{-- Brand Glow --}}
                    <div class="absolute -top-16 -right-16 w-48 h-48 rounded-full blur-3xl opacity-30 group-hover:opacity-50 transition"
                         style="background: {{ $net['color'] }}"></div>

                    <div class="relative p-6 md:p-7 flex flex-col h-full min-h-[220px]">
                        {{-- Top Bar: Logo & Badge --}}
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex items-center">
                                @if($net['slug'] === 'rebahin')
                                    <span class="text-2xl font-black italic tracking-wider text-red-500 drop-shadow-[0_0_12px_rgba(229,9,20,0.6)]">
                                        REBAHIN
                                    </span>
                                @elseif($net['slug'] === 'lk21')
                                    <span class="text-2xl font-black italic tracking-wider text-yellow-400 drop-shadow-[0_0_12px_rgba(234,179,8,0.6)]">
                                        LAYARKACA<span class="text-brand-500 text-white">21</span>
                                    </span>
                                @else
                                    <span class="text-2xl font-black tracking-tight text-white">
                                        {{ $net['name'] }}
                                    </span>
                                @endif
                            </div>
                            <span class="shrink-0 text-[11px] font-bold text-zinc-200 bg-black/50 border border-white/10 px-2.5 py-1 rounded-full">
                                {{ $net['badge'] }}
                            </span>
                        </div>

                        {{-- Middle Description --}}
                        <div class="mt-auto pt-6">
                            <h2 class="text-lg font-bold text-white group-hover:text-red-400 transition">{{ $net['name'] }}</h2>
                            <p class="text-xs md:text-sm text-zinc-400 mt-1 line-clamp-2 leading-relaxed">{{ $net['description'] }}</p>
                        </div>

                        {{-- Bottom CTA --}}
                        <div class="mt-4 pt-2 border-t border-white/5 flex items-center justify-between">
                            <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-zinc-300 group-hover:text-white transition">
                                Buka katalog {{ $net['name'] }}
                                <svg class="w-3.5 h-3.5 group-hover:translate-x-1 transition duration-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </span>
                            <span class="text-[10px] font-mono text-zinc-500 uppercase tracking-widest group-hover:text-zinc-400 transition">
                                {{ $net['slug'] }}
                            </span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</div>
