<div class="min-h-screen bg-[#050505] text-white pb-48 md:pb-28">
    <div class="max-w-7xl mx-auto px-4 md:px-6 pt-24 md:pt-28">

        {{-- Header --}}
        <div class="mb-8 md:mb-10">
            <div class="flex items-center gap-2 text-xs text-zinc-500 mb-3">
                <a href="{{ \App\Filament\Streaming\Pages\StreamingHome::getUrl(panel: 'streaming') }}" class="hover:text-white transition">Home</a>
                <span>/</span>
                <span class="text-zinc-300 font-semibold">Anime</span>
            </div>
            <h1 class="text-3xl md:text-4xl font-extrabold tracking-tight">Pilih Sumber Anime</h1>
            <p class="text-sm md:text-base text-zinc-400 mt-2 max-w-2xl">
                Pilih server anime yang mau kamu tonton. Kalau satu server lagi lambat atau judul belum ada, coba server lainnya.
            </p>
        </div>

        {{-- Source cards (5 cards grid) --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 md:gap-5 max-w-7xl">
            @foreach($sources as $src)
                @php
                    $tag = $src['available'] ? 'a' : 'div';
                @endphp
                <{{ $tag }}
                    @if($src['available'])
                        href="{{ $src['url'] }}"
                        aria-label="Buka anime {{ $src['label'] }} ({{ $src['name'] }})"
                    @else
                        aria-disabled="true"
                    @endif
                    class="group relative block overflow-hidden rounded-2xl border border-white/10 bg-gradient-to-br {{ $src['accent'] }} transition duration-300
                           {{ $src['available'] ? 'hover:border-white/25 active:scale-[0.98] focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500' : 'cursor-not-allowed' }}">

                    <div class="absolute -top-16 -right-16 w-48 h-48 rounded-full blur-3xl opacity-30 group-hover:opacity-50 transition"
                         style="background: {{ $src['color'] }}"></div>

                    <div class="relative p-6 md:p-7 flex flex-col min-h-[230px]">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-4xl md:text-5xl font-black tracking-tight {{ $src['available'] ? '' : 'opacity-50' }}" style="color: {{ $src['color'] }}">{{ $src['label'] }}</span>
                            @if($src['available'])
                                <span class="shrink-0 inline-flex items-center gap-1.5 text-[11px] font-bold text-emerald-300 bg-emerald-950/60 border border-emerald-500/30 px-2.5 py-1 rounded-full">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> Online
                                </span>
                            @else
                                <span class="shrink-0 text-[11px] font-bold text-amber-300 bg-amber-950/60 border border-amber-500/30 px-2.5 py-1 rounded-full">
                                    Segera Hadir
                                </span>
                            @endif
                        </div>

                        <div class="mt-5">
                            <h2 class="text-lg font-bold text-white">{{ $src['name'] }}</h2>
                            <p class="text-xs md:text-sm {{ $src['available'] ? 'text-zinc-400' : 'text-zinc-300' }} mt-1 line-clamp-2">{{ $src['description'] }}</p>
                        </div>

                        <div class="mt-4 flex flex-wrap gap-1.5">
                            @foreach($src['features'] as $f)
                                <span class="text-[10px] font-bold uppercase tracking-wider text-zinc-300 bg-black/40 border border-white/10 px-2 py-0.5 rounded">{{ $f }}</span>
                            @endforeach
                        </div>

                        <div class="mt-auto pt-5 inline-flex items-center gap-1.5 text-xs font-semibold {{ $src['available'] ? 'text-zinc-300 group-hover:text-white' : 'text-zinc-500' }} transition">
                            @if($src['available'])
                                Tonton di {{ $src['label'] }}
                                <svg class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            @else
                                Belum tersedia
                            @endif
                        </div>
                    </div>
                </{{ $tag }}>
            @endforeach
        </div>
    </div>
</div>
