<div class="min-h-screen bg-[#050505] text-white pb-24"
     x-data="{
         currentSlide: 0,
         totalSlides: {{ count($banners) }},
         genreModalOpen: false,
         timer: null,
         init() {
             if (this.totalSlides > 1) {
                 this.timer = setInterval(() => {
                     this.currentSlide = (this.currentSlide + 1) % this.totalSlides;
                 }, 5000);
             }
         }
     }">

    {{-- ================= HERO BANNER SLIDER ================= --}}
    @if(!empty($banners))
        <section class="relative w-full max-w-[1800px] mx-auto px-4 sm:px-6 pt-4 lg:pt-8">
            <div class="relative w-full h-[320px] sm:h-[400px] md:h-[460px] rounded-3xl overflow-hidden bg-zinc-950 border border-white/10 shadow-2xl group">
                @foreach($banners as $idx => $b)
                    <div x-show="currentSlide === {{ $idx }}"
                         x-transition:enter="transition ease-out duration-500"
                         x-transition:enter-start="opacity-0 scale-98"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-300"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-98"
                         class="absolute inset-0">
                        {{-- Background Image with Vignette --}}
                        <img src="{{ $b['image'] }}" alt="{{ $b['title'] }}"
                             class="w-full h-full object-cover object-center filter brightness-90 transform group-hover:scale-105 transition duration-1000"
                             referrerpolicy="no-referrer" />
                        <div class="absolute inset-0 bg-gradient-to-t from-[#050505] via-[#050505]/40 to-transparent"></div>
                        <div class="absolute inset-0 bg-gradient-to-r from-[#050505] via-[#050505]/60 to-transparent"></div>

                        {{-- Slide Content --}}
                        <div class="absolute bottom-6 left-6 sm:bottom-10 sm:left-10 max-w-xl pr-6 z-10">
                            <div class="flex items-center gap-2 mb-3">
                                <span class="px-2.5 py-1 rounded-lg bg-red-600 text-white text-[10px] font-black uppercase tracking-wider shadow-lg shadow-red-600/30">
                                    18+ HOT
                                </span>
                                <span class="px-2.5 py-1 rounded-lg bg-zinc-900/80 backdrop-blur-md text-zinc-300 text-[10px] font-bold uppercase tracking-wider border border-white/10">
                                    {{ $b['type'] }}
                                </span>
                                @if(!empty($b['rating']))
                                    <span class="flex items-center gap-1 text-amber-400 text-xs font-bold bg-black/60 px-2 py-0.5 rounded-lg border border-white/5">
                                        ★ {{ $b['rating'] }}
                                    </span>
                                @endif
                            </div>
                            <h2 class="text-xl sm:text-2xl md:text-3xl font-black text-white leading-tight drop-shadow-md line-clamp-2">
                                {{ $b['title'] }}
                            </h2>
                            @if(!empty($b['description']))
                                <p class="mt-2 text-xs sm:text-sm text-zinc-300 line-clamp-2 leading-relaxed drop-shadow">
                                    {{ $b['description'] }}
                                </p>
                            @endif

                            <div class="mt-4 flex items-center gap-3">
                                @if(!empty($b['slug']))
                                    <a href="/doujin/detail/{{ $b['slug'] }}"
                                       class="px-5 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white text-xs font-bold flex items-center gap-2 transition shadow-lg shadow-red-600/40 active:scale-95">
                                        <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                        Baca Sekarang
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach

                {{-- Slider Nav Dots & Arrows --}}
                <div class="absolute bottom-4 right-6 z-20 flex items-center gap-2">
                    <button type="button" @click="currentSlide = (currentSlide - 1 + totalSlides) % totalSlides"
                            class="w-8 h-8 rounded-full bg-black/60 hover:bg-red-600 text-white flex items-center justify-center transition border border-white/10 backdrop-blur-md">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
                    </button>
                    <button type="button" @click="currentSlide = (currentSlide + 1) % totalSlides"
                            class="w-8 h-8 rounded-full bg-black/60 hover:bg-red-600 text-white flex items-center justify-center transition border border-white/10 backdrop-blur-md">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                    </button>
                </div>
            </div>
        </section>
    @endif

    {{-- ================= FILTER CONTROLS & TABS ================= --}}
    <main class="w-full max-w-[1800px] mx-auto px-4 sm:px-6 pt-8">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-6 border-b border-zinc-800">
            {{-- Left: Type Tabs (Semua, Manhwa, Doujinshi, Manga) --}}
            <div class="flex items-center gap-2 overflow-x-auto kz-noscroll py-1">
                @php
                    $types = [
                        'all' => 'Semua',
                        'manhwa' => 'Manhwa 18+',
                        'doujinshi' => 'Doujinshi',
                        'manga' => 'Manga 18+',
                    ];
                @endphp
                @foreach($types as $k => $label)
                    <button type="button" wire:click="filterType('{{ $k }}')"
                            class="px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition cursor-pointer {{ $type === $k ? 'bg-red-600 text-white shadow-lg shadow-red-600/30' : 'bg-zinc-900 text-zinc-400 hover:text-white border border-zinc-800' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            {{-- Right: Sort & Filter Genre Button --}}
            <div class="flex items-center gap-2 flex-wrap">
                {{-- Sort Dropdown / Buttons --}}
                <div class="flex items-center bg-zinc-900 border border-zinc-800 rounded-xl p-1 text-xs">
                    <button type="button" wire:click="filterSort('latest_chapter')"
                            class="px-3 py-1.5 rounded-lg font-bold transition {{ $sort === 'latest_chapter' ? 'bg-red-600 text-white' : 'text-zinc-400 hover:text-white' }}">
                        Update
                    </button>
                    <button type="button" wire:click="filterSort('popular')"
                            class="px-3 py-1.5 rounded-lg font-bold transition {{ $sort === 'popular' ? 'bg-red-600 text-white' : 'text-zinc-400 hover:text-white' }}">
                        Populer
                    </button>
                    <button type="button" wire:click="filterSort('newest')"
                            class="px-3 py-1.5 rounded-lg font-bold transition {{ $sort === 'newest' ? 'bg-red-600 text-white' : 'text-zinc-400 hover:text-white' }}">
                        Terbaru
                    </button>
                    <button type="button" wire:click="filterSort('rating')"
                            class="px-3 py-1.5 rounded-lg font-bold transition {{ $sort === 'rating' ? 'bg-red-600 text-white' : 'text-zinc-400 hover:text-white' }}">
                        Rating
                    </button>
                </div>

                {{-- Genre Filter Trigger --}}
                <button type="button" @click="genreModalOpen = true"
                        class="px-3.5 py-2 rounded-xl bg-zinc-900 hover:bg-zinc-800 border border-zinc-800 text-xs font-bold text-zinc-300 flex items-center gap-1.5 transition">
                    <svg class="w-3.5 h-3.5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z"/></svg>
                    <span>Genre</span>
                    @if(!empty($selectedGenres))
                        <span class="px-1.5 py-0.2 bg-red-600 text-white rounded-full text-[10px] font-black">
                            {{ count($selectedGenres) }}
                        </span>
                    @endif
                </button>
            </div>
        </div>

        {{-- Active Selected Genres Tags --}}
        @if(!empty($selectedGenres))
            <div class="mt-4 flex items-center gap-2 flex-wrap">
                <span class="text-xs text-zinc-500 font-semibold">Filter aktif:</span>
                @foreach($selectedGenres as $sg)
                    <button type="button" wire:click="toggleGenre('{{ $sg }}')"
                            class="px-2.5 py-1 rounded-lg bg-red-600/20 border border-red-500/40 text-red-300 text-xs font-semibold flex items-center gap-1.5 hover:bg-red-600/30 transition">
                        <span>{{ ucwords(str_replace('-', ' ', $sg)) }}</span>
                        <svg class="w-3 h-3 text-red-400" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                @endforeach
                <button type="button" wire:click="clearGenres()" class="text-xs text-zinc-500 hover:text-white underline ml-2">
                    Reset Semua
                </button>
            </div>
        @endif

        {{-- ================= MANGA CATALOG GRID ================= --}}
        <div class="mt-8">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base sm:text-lg font-black tracking-tight flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-red-600"></span>
                    Katalog {{ $types[$type] ?? 'Doujin' }}
                </h3>
                <span class="text-xs text-zinc-500">Halaman {{ $page }}</span>
            </div>

            @if(!empty($mangaList))
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3 sm:gap-4">
                    @foreach($mangaList as $item)
                        <a href="/doujin/detail/{{ $item['slug'] }}"
                           class="group flex flex-col rounded-2xl bg-zinc-900/60 hover:bg-zinc-800/90 border border-zinc-800 hover:border-red-500/40 overflow-hidden transition duration-300 hover:-translate-y-1 shadow-lg hover:shadow-red-600/10">
                            {{-- Cover Aspect 3:4 --}}
                            <div class="relative w-full aspect-[3/4] bg-zinc-950 overflow-hidden">
                                @if(!empty($item['cover']))
                                    <img src="{{ $item['cover'] }}" alt="{{ $item['title'] }}"
                                         class="w-full h-full object-cover group-hover:scale-105 transition duration-500"
                                         loading="lazy" referrerpolicy="no-referrer" />
                                @endif
                                <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent opacity-60 group-hover:opacity-40 transition"></div>

                                {{-- Badges --}}
                                <div class="absolute top-2 left-2 flex flex-col gap-1">
                                    <span class="px-2 py-0.5 rounded-md bg-black/75 backdrop-blur-md text-[9px] font-black uppercase tracking-wider text-red-400 border border-white/5">
                                        {{ $item['type'] }}
                                    </span>
                                </div>

                                @if(!empty($item['rating']) && $item['rating'] !== '0.0')
                                    <div class="absolute top-2 right-2 px-1.5 py-0.5 rounded-md bg-black/75 backdrop-blur-md text-[10px] font-bold text-amber-400 border border-white/5 flex items-center gap-0.5">
                                        ★ {{ $item['rating'] }}
                                    </div>
                                @endif

                                @if(!empty($item['latest_chapter']))
                                    <div class="absolute bottom-2 left-2 right-2 flex items-center justify-between text-[10px] font-bold text-white px-2 py-1 rounded-lg bg-black/75 backdrop-blur-md border border-white/10">
                                        <span class="text-red-400 font-black">CH. {{ $item['latest_chapter'] }}</span>
                                        <span class="text-zinc-400 text-[9px] capitalize">{{ $item['status'] }}</span>
                                    </div>
                                @endif
                            </div>

                            {{-- Title & Genres --}}
                            <div class="p-3 flex-1 flex flex-col justify-between gap-1.5">
                                <h4 class="text-xs font-bold text-zinc-200 group-hover:text-red-400 transition line-clamp-2 leading-snug">
                                    {{ $item['title'] }}
                                </h4>
                                @if(!empty($item['genres']))
                                    <p class="text-[10px] text-zinc-500 truncate">
                                        {{ implode(' • ', $item['genres']) }}
                                    </p>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>

                {{-- Pagination Buttons --}}
                <div class="mt-10 flex items-center justify-center gap-3">
                    <button type="button" wire:click="prevPage" {{ $page <= 1 ? 'disabled' : '' }}
                            class="px-5 py-2.5 rounded-xl bg-zinc-900 border border-zinc-800 text-xs font-bold text-white hover:bg-zinc-800 disabled:opacity-40 disabled:cursor-not-allowed transition flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
                        Sebelumnya
                    </button>
                    <span class="px-4 py-2 rounded-xl bg-zinc-950 border border-zinc-800 text-xs font-black text-red-400">
                        Halaman {{ $page }}
                    </span>
                    <button type="button" wire:click="nextPage" {{ !$hasNext ? 'disabled' : '' }}
                            class="px-5 py-2.5 rounded-xl bg-zinc-900 border border-zinc-800 text-xs font-bold text-white hover:bg-zinc-800 disabled:opacity-40 disabled:cursor-not-allowed transition flex items-center gap-2">
                        Berikutnya
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                    </button>
                </div>
            @else
                <div class="py-20 text-center text-zinc-500 bg-zinc-900/40 rounded-2xl border border-dashed border-zinc-800">
                    <p class="text-sm font-medium">Tidak ada konten yang cocok dengan filter yang dipilih.</p>
                </div>
            @endif
        </div>
    </main>

    {{-- ================= GENRE MULTI-SELECT MODAL ================= --}}
    <div x-show="genreModalOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md"
         style="display: none;">
        <div @click.away="genreModalOpen = false"
             class="w-full max-w-3xl max-h-[85vh] bg-zinc-950 border border-zinc-800 rounded-3xl p-6 flex flex-col shadow-2xl">
            <div class="flex items-center justify-between pb-4 border-b border-zinc-800">
                <div>
                    <h3 class="text-base font-black text-white flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-red-600"></span>
                        Pilih Multi Genre 18+
                    </h3>
                    <p class="text-xs text-zinc-500 mt-0.5">Pilih satu atau beberapa genre sekaligus</p>
                </div>
                <button type="button" @click="genreModalOpen = false" class="text-zinc-500 hover:text-white p-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Genre Cloud Grid --}}
            <div class="flex-1 overflow-y-auto py-4 pr-1 space-y-2 kz-thin">
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2">
                    @foreach($genresList as $g)
                        @php
                            $isSelected = in_array($g['slug'], $selectedGenres);
                        @endphp
                        <button type="button" wire:click="toggleGenre('{{ $g['slug'] }}')"
                                class="px-3 py-2 rounded-xl text-xs font-semibold text-left transition flex items-center justify-between gap-2 border {{ $isSelected ? 'bg-red-600 text-white border-red-500 shadow-md shadow-red-600/30' : 'bg-zinc-900/80 text-zinc-400 hover:text-white border-zinc-800 hover:border-zinc-700' }}">
                            <span class="truncate">{{ $g['name'] }}</span>
                            @if($g['count'] > 0)
                                <span class="text-[9px] opacity-60">{{ $g['count'] }}</span>
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="pt-4 border-t border-zinc-800 flex items-center justify-between gap-3">
                <button type="button" wire:click="clearGenres()" class="text-xs font-bold text-zinc-400 hover:text-red-400">
                    Hapus Pilihan
                </button>
                <button type="button" @click="genreModalOpen = false"
                        class="px-6 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white text-xs font-bold transition shadow-lg shadow-red-600/30">
                    Terapkan Filter
                </button>
            </div>
        </div>
    </div>
</div>
