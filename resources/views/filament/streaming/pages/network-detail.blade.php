<div class="min-h-screen bg-[#050505] text-white pb-28"
     x-data
     x-on:network-page-changed.window="window.scrollTo({ top: 0, behavior: 'smooth' })">

    @php
        $networkListUrl = \App\Filament\Streaming\Pages\Network::getUrl(panel: 'streaming');
        $currentPage = (int) ($pagination['page'] ?? $page);
        $totalPages = max(1, (int) ($pagination['totalPages'] ?? 1));
        $total = (int) ($pagination['total'] ?? count($items));
        $start = max(1, $currentPage - 2);
        $end = min($totalPages, $start + 4);
        $start = max(1, $end - 4);
    @endphp

    {{-- Hero --}}
    <div class="relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br {{ $network['accent'] }}"></div>
        <div class="absolute -top-24 right-0 w-96 h-96 rounded-full blur-3xl opacity-25" style="background: {{ $network['color'] }}"></div>
        <div class="absolute inset-x-0 bottom-0 h-24 bg-gradient-to-b from-transparent to-[#050505]"></div>

        <div class="relative max-w-7xl mx-auto px-4 md:px-6 pt-24 md:pt-28 pb-8 md:pb-10">
            <div class="flex items-center gap-2 text-xs text-zinc-400 mb-6">
                <a href="{{ $networkListUrl }}" class="inline-flex items-center gap-1.5 text-zinc-300 hover:text-white bg-black/40 px-3 py-1.5 rounded-full border border-white/10 transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <span>Network</span>
                </a>
                <span>/</span>
                <span class="text-white font-semibold truncate">{{ $network['name'] }}</span>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center gap-5">
                <div class="w-20 h-20 md:w-24 md:h-24 shrink-0 rounded-2xl bg-black/50 border border-white/10 flex items-center justify-center p-3"
                     style="color: {{ $network['slug'] === 'netflix' ? $network['color'] : '#fff' }}">
                    {!! $network['logo_svg'] !!}
                </div>
                <div>
                    <h1 class="text-2xl md:text-4xl font-extrabold tracking-tight">{{ $network['title'] }}</h1>
                    <p class="text-sm text-zinc-400 mt-2 max-w-2xl">{{ $network['description'] }}</p>
                    <p class="text-xs text-zinc-300 mt-2">{{ number_format($total, 0, ',', '.') }} judul tersedia</p>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 md:px-6 pt-6">
        {{-- Sort tabs --}}
        <div class="flex items-center gap-2 overflow-x-auto pb-2 mb-6" role="tablist" aria-label="Urutkan katalog">
            @foreach(\App\Filament\Streaming\Pages\NetworkDetail::SORTS as $key => $label)
                <button type="button" role="tab"
                        aria-selected="{{ $sort === $key ? 'true' : 'false' }}"
                        wire:click="setSort('{{ $key }}')"
                        class="shrink-0 px-4 py-2 rounded-full text-xs font-bold border transition
                               {{ $sort === $key ? 'bg-red-600 border-red-500 text-white' : 'bg-zinc-900/70 border-white/10 text-zinc-300 hover:text-white' }}">
                    {{ $label }}
                </button>
            @endforeach
            <span wire:loading class="text-xs text-zinc-500 ml-2">Memuat…</span>
        </div>

        {{-- Grid --}}
        <div wire:loading.class="opacity-50 pointer-events-none" class="transition">
            @if(empty($items))
                <div class="py-16 text-center text-zinc-500 bg-zinc-900/30 rounded-xl border border-dashed border-zinc-800">
                    Katalog belum bisa dimuat. Coba muat ulang sebentar lagi.
                </div>
            @else
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
                    @foreach($items as $item)
                        <div class="group relative flex flex-col" wire:key="net-{{ $item['id'] }}">
                            <a href="{{ $item['url'] }}"
                               class="block relative w-full aspect-[2/3] overflow-hidden bg-zinc-950 rounded-xl border border-zinc-800/80 group-hover:border-zinc-700 transition">
                                <img src="{{ $item['thumbnail'] }}" alt="{{ $item['title'] }}" loading="lazy"
                                     class="w-full h-full object-cover group-hover:scale-105 transition duration-300">

                                <div class="absolute inset-x-0 bottom-0 h-1/4 bg-gradient-to-t from-black/70 to-transparent pointer-events-none"></div>
                                <span class="absolute top-2 left-2 px-1.5 py-0.5 text-[9px] font-extrabold text-gray-200 bg-black/60 rounded border border-white/5 uppercase tracking-wider">
                                    {{ $item['type'] === 'tv_series' ? 'TV' : 'MOVIE' }}
                                </span>

                                @if(!empty($item['rating']) && $item['rating'] !== '0.0')
                                    <span class="absolute bottom-2 left-2 flex items-center gap-0.5 text-yellow-400 font-extrabold text-[10px] bg-black/70 px-1.5 py-0.5 rounded border border-white/5">
                                        <svg class="w-3 h-3 fill-current" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                                        {{ $item['rating'] }}
                                    </span>
                                @endif
                            </a>
                            <div class="mt-2.5">
                                <h3 class="text-xs md:text-[13px] font-bold text-zinc-200 group-hover:text-red-500 transition truncate" title="{{ $item['title'] }}">
                                    <a href="{{ $item['url'] }}">{{ $item['title'] }}</a>
                                </h3>
                                @if(!empty($item['year']))
                                    <span class="text-zinc-500 text-[11px] block mt-0.5">{{ $item['year'] }}</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Pagination --}}
        @if($totalPages > 1)
            <nav class="flex items-center justify-center gap-1.5 mt-10" aria-label="Halaman katalog">
                <button type="button" wire:click="goToPage({{ $currentPage - 1 }})" @disabled($currentPage <= 1)
                        class="px-3 py-2 rounded-lg text-xs font-bold bg-zinc-900 border border-white/10 text-zinc-300 disabled:opacity-30">‹ Prev</button>
                @for($p = $start; $p <= $end; $p++)
                    <button type="button" wire:click="goToPage({{ $p }})"
                            aria-current="{{ $p === $currentPage ? 'page' : 'false' }}"
                            class="w-9 h-9 rounded-lg text-xs font-bold border transition {{ $p === $currentPage ? 'bg-red-600 border-red-500 text-white' : 'bg-zinc-900 border-white/10 text-zinc-300' }}">{{ $p }}</button>
                @endfor
                <button type="button" wire:click="goToPage({{ $currentPage + 1 }})" @disabled($currentPage >= $totalPages)
                        class="px-3 py-2 rounded-lg text-xs font-bold bg-zinc-900 border border-white/10 text-zinc-300 disabled:opacity-30">Next ›</button>
            </nav>
            <p class="text-center text-[11px] text-zinc-500 mt-3">Halaman {{ $currentPage }} dari {{ $totalPages }}</p>
        @endif
    </div>
</div>
