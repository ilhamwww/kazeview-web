@php
    use App\Filament\Streaming\Pages\Dracin;
    use App\Filament\Streaming\Pages\DracinDetail;

    $img = fn ($u) => $u ? url('/api/proxy-image?url=' . urlencode($u)) : null;
    $tabs = match ($slugVal) {
        'shortmax' => ['popular' => 'Populer', 'new' => 'Baru', 'hot' => 'Hot', 'vip' => 'VIP'],
        'dramabox' => ['trending' => 'Trending 🔥', 'latest' => 'Terbaru ✨', 'foryou' => 'Untuk Kamu ❤️', 'dubbed' => 'Sulih Suara 🎙️'],
        'netshort' => [
            'all' => 'Semua',
            '2045076348099100678' => 'Kepuasan ✨',
            '2099382095592521735' => 'Romantis Urban 🏙️',
            '2099383175755825160' => 'Menghukum Penjahat ⚖️',
            '2099341280560070658' => 'Sang Juara 👑',
            '2099383543214604294' => 'Balas Dendam ⚔️',
            '2045076660566360070' => 'Romantis ❤️',
            '2099384660480720902' => 'Wanita Mandiri 💃',
        ],
        'reelshort' => [
            'POPULER' => 'Populer 🔥',
            'TERBARU' => 'Terbaru ✨',
            'ASIA' => 'Asia 🌏',
            'RANKING' => 'Ranking 🏆',
            'KHUSUS PRIA' => 'Pria 👔',
            'KHUSUS WANITA' => 'Wanita 👠',
        ],
        'vibeshort' => [
            '337' => 'Buat Kamu ❤️',
            '349' => 'Baru 🆕',
            '328' => 'Populer 🔥',
        ],
        'storyreel' => [
            '337' => 'Buat Kamu ❤️',
            '348' => 'HOT / Populer 🔥',
            '349' => 'Baru Rilis ✨',
            '491' => 'Baper Cinta 💖',
            '446' => 'Gudang Drama 📚',
        ],
        'raptdrama' => [
            'recommend' => 'Semua / Rekomendasi ⭐',
            'trending' => 'Trending 🔥',
            'new_releases' => 'Terbaru ✨',
            'featured' => 'Unggulan 👑',
        ],
        'serealplus', 'serialplus' => [
            'popular' => 'Populer 🔥',
            'latest' => 'Terbaru ✨',
        ],
        'flextv' => [
            'popular' => 'Populer 🔥',
            'home' => 'Semua 📺',
            'newest' => 'Terbaru ✨',
        ],
        'freereels' => [
            'popular' => 'Populer 🔥',
            'foryou' => 'Untuk Kamu ❤️',
            'new' => 'Terbaru ✨',
        ],
        'starshort' => [
            'hot' => 'Populer 🔥',
            'latest' => 'Terbaru ✨',
        ],
        'dramawave' => [
            'recommend' => 'Rekomendasi ⭐',
            'home' => 'Populer / Beranda 🔥',
            'anime' => 'Anime 🎭',
        ],
        default => [],
    };
    $langs = match ($slugVal) {
        'reelshort' => [
            'in' => 'Indonesia 🇮🇩',
            'en' => 'English 🇺🇸',
            'zh' => 'Mandarin 🇨🇳',
            'zh-TW' => 'Taiwan 🇹🇼',
            'ja' => 'Jepang 🇯🇵',
            'ko' => 'Korea 🇰🇷',
            'th' => 'Thai 🇹🇭',
            'vi' => 'Tiếng Việt 🇻🇳',
            'ru' => 'Rusia 🇷🇺',
            'fr' => 'Français 🇫🇷',
            'de' => 'Deutsch 🇩🇪',
            'it' => 'Italiano 🇮🇹',
            'es' => 'Español 🇪🇸',
            'hi' => 'Hindi 🇮🇳',
            'pt' => 'Português 🇵🇹',
            'ar' => 'Arab 🇸🇦',
            'ms' => 'Melayu 🇲🇾',
            'tr' => 'Türkçe 🇹🇷',
            'pl' => 'Polski 🇵🇱',
        ],
        'raptdrama' => [
            'id' => 'Indonesia 🇮🇩',
            'en' => 'English 🇺🇸',
            'es' => 'Español 🇪🇸',
            'fr' => 'Français 🇫🇷',
            'de' => 'Deutsch 🇩🇪',
            'pt' => 'Português 🇵🇹',
            'it' => 'Italiano 🇮🇹',
            'ja' => 'Jepang 🇯🇵',
            'ko' => 'Korea 🇰🇷',
            'th' => 'Thai 🇹🇭',
            'vi' => 'Tiếng Việt 🇻🇳',
            'ms' => 'Melayu 🇲🇾',
            'ar' => 'Arab 🇸🇦',
            'hi' => 'Hindi 🇮🇳',
            'zh' => 'Mandarin 🇨🇳',
            'tr' => 'Türkçe 🇹🇷',
            'ru' => 'Rusia 🇷🇺',
            'tl' => 'Filipino 🇵🇭',
        ],
        'goodshort' => ['id' => 'Indonesia', 'en' => 'English', 'es' => 'Español', 'ko' => 'Korea'],
        'moboreels' => ['in' => 'Indonesia 🇮🇩', 'en' => 'English 🇺🇸'],
        'shortswave' => ['in' => 'Indonesia 🇮🇩', 'en' => 'English 🇺🇸'],
        'stardusttv' => ['id' => 'Indonesia 🇮🇩', 'en' => 'English 🇺🇸', 'ja' => 'Jepang 🇯🇵', 'ko' => 'Korea 🇰🇷'],
        'serealplus', 'serialplus' => ['id' => 'Indonesia 🇮🇩', 'en' => 'English 🇺🇸', 'ko' => 'Korea 🇰🇷', 'ja' => 'Jepang 🇯🇵'],
        'dramawave' => [
            'in' => 'Indonesia 🇮🇩',
            'en' => 'English 🇺🇸',
            'ja' => 'Jepang 🇯🇵',
            'ko' => 'Korea 🇰🇷',
            'th' => 'Thai 🇹🇭',
            'vi' => 'Tiếng Việt 🇻🇳',
            'ar' => 'Arab 🇸🇦',
            'ru' => 'Rusia 🇷🇺',
            'pl' => 'Polski 🇵🇱',
            'hi' => 'Hindi 🇮🇳',
            'tr' => 'Türkçe 🇹🇷',
        ],
        default => [],
    };
    $valid = array_values(array_filter($dramas, fn ($d) => !empty($d['id'])));
    $featured = blank($search) ? array_slice($valid, 0, 1)[0] ?? null : null;
@endphp

<div class="min-h-screen bg-[#050505] text-white pb-28 md:pb-16">

    {{-- ================= HEADER ================= --}}
    <section class="relative overflow-hidden">
        @if($featured && !empty($featured['cover']))
            <div class="absolute inset-0" aria-hidden="true">
                <img src="{{ $img($featured['cover']) }}" alt="" class="w-full h-full object-cover opacity-30 blur-3xl scale-125" referrerpolicy="no-referrer">
            </div>
        @endif
        <div class="absolute inset-0 bg-gradient-to-b from-[#050505]/40 via-[#050505]/85 to-[#050505]" aria-hidden="true"></div>

        <div class="relative w-full max-w-[1400px] mx-auto px-4 md:px-10 pt-20 md:pt-28 pb-5">
            <a href="{{ Dracin::getUrl(panel: 'streaming') }}"
               class="inline-flex items-center gap-1.5 text-xs font-semibold text-zinc-300 hover:text-white bg-white/5 border border-white/10 px-3 py-1.5 rounded-full transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Semua Platform
            </a>

            <div class="mt-5 flex items-center gap-4">
                <div class="w-16 h-16 md:w-20 md:h-20 shrink-0 rounded-2xl bg-black/60 ring-1 ring-white/10 flex items-center justify-center overflow-hidden p-2.5">
                    @if(!empty($network['logo']))
                        <img src="{{ $img($network['logo']) }}" alt="" class="w-full h-full object-contain" referrerpolicy="no-referrer"
                             onerror="this.style.display='none';this.nextElementSibling.classList.remove('hidden')">
                        <span class="hidden text-2xl font-black">{{ mb_substr($network['name'] ?? '?', 0, 1) }}</span>
                    @else
                        <span class="text-2xl font-black">{{ mb_substr($network['name'] ?? '?', 0, 1) }}</span>
                    @endif
                </div>
                <div class="min-w-0">
                    <h1 class="text-2xl md:text-4xl font-black tracking-tight leading-tight line-clamp-1">{{ $network['name'] ?? 'Dracin' }}</h1>
                    <p class="text-xs md:text-sm text-zinc-400 mt-1">Drama pendek vertikal · {{ count($valid) }} judul dimuat</p>
                </div>
            </div>

            {{-- Lanjutkan Nonton: hanya tampil kalau user punya riwayat di network ini --}}
            @php $history = $history ?? []; @endphp
            @if(blank($search) && count($history))
                <div class="mt-6">
                    <div class="flex items-center justify-between mb-2.5">
                        <h2 class="text-sm md:text-base font-black tracking-tight">Lanjutkan Nonton</h2>
                        <span class="text-[10px] font-bold text-zinc-500">{{ count($history) }} drama</span>
                    </div>
                    <div class="flex gap-3 overflow-x-auto kz-noscroll snap-x snap-mandatory scroll-pl-4 md:scroll-pl-0 -mx-4 px-4 md:mx-0 md:px-0 pb-1">
                        @foreach($history as $h)
                            <a href="{{ DracinDetail::getUrl(['network_slug' => $slugVal, 'id' => $h['id']], panel: 'streaming') }}"
                               class="group snap-start shrink-0 w-[78%] sm:w-72 flex gap-3 p-2.5 rounded-2xl bg-white/[0.04] ring-1 ring-white/10 hover:ring-red-500/50 active:scale-[0.99] transition">
                                <div class="relative w-16 aspect-[3/4] shrink-0 rounded-lg overflow-hidden bg-zinc-900">
                                    @if(!empty($h['cover']))
                                        <img src="{{ $img($h['cover']) }}" alt="{{ $h['title'] }}" class="w-full h-full object-cover" referrerpolicy="no-referrer" loading="lazy">
                                    @endif
                                    <span class="absolute inset-0 m-auto w-7 h-7 rounded-full bg-red-600/90 flex items-center justify-center shadow-lg">
                                        <svg class="w-3 h-3 fill-white ml-0.5" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
                                    </span>
                                </div>
                                <div class="min-w-0 flex-1 py-0.5 flex flex-col">
                                    <span class="self-start text-[9px] font-black uppercase tracking-[0.18em] text-emerald-300 bg-emerald-500/15 border border-emerald-500/30 px-2 py-0.5 rounded-full">Terakhir Dilihat</span>
                                    <h3 class="mt-1.5 text-sm font-black leading-snug line-clamp-2">{{ $h['title'] }}</h3>
                                    <div class="mt-auto pt-1 flex items-center gap-2 text-[11px] font-bold">
                                        @if(!empty($h['last_episode']))<span class="text-zinc-300">Episode {{ $h['last_episode'] }}</span>@endif
                                        <span class="text-red-400 group-hover:text-red-300">Lanjut →</span>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Featured (hanya saat tidak mencari) --}}
            @if($featured)
                <a href="{{ DracinDetail::getUrl(['network_slug' => $slugVal, 'id' => $featured['id']], panel: 'streaming') }}"
                   class="group mt-6 flex gap-4 p-3 rounded-2xl bg-white/[0.04] ring-1 ring-white/10 hover:ring-red-500/50 active:scale-[0.99] transition">
                    <div class="relative w-24 md:w-28 aspect-[3/4] shrink-0 rounded-xl overflow-hidden bg-zinc-900">
                        @if(!empty($featured['cover']))
                            <img src="{{ $img($featured['cover']) }}" alt="{{ $featured['name'] }}" class="w-full h-full object-cover" referrerpolicy="no-referrer">
                        @endif
                        <span class="absolute inset-0 m-auto w-10 h-10 rounded-full bg-red-600/90 flex items-center justify-center shadow-xl">
                            <svg class="w-4 h-4 fill-white ml-0.5" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
                        </span>
                    </div>
                    <div class="min-w-0 flex-1 py-1 flex flex-col">
                        <span class="self-start text-[9px] font-black uppercase tracking-[0.2em] text-red-400 bg-red-600/15 border border-red-600/30 px-2 py-0.5 rounded-full">Pilihan Teratas</span>
                        <h2 class="mt-2 text-base md:text-xl font-black leading-snug line-clamp-2">{{ $featured['name'] }}</h2>
                        @if(!empty($featured['intro']))
                            <p class="mt-1.5 text-xs md:text-sm text-zinc-400 leading-relaxed line-clamp-3">{{ $featured['intro'] }}</p>
                        @endif
                        <div class="mt-auto pt-2 flex items-center gap-2 text-[11px] font-bold">
                            @if(!empty($featured['episodes']))<span class="text-zinc-300">{{ $featured['episodes'] }} episode</span>@endif
                            <span class="text-red-400 group-hover:text-red-300">Tonton →</span>
                        </div>
                    </div>
                </a>
            @endif
        </div>
    </section>

    {{-- ================= STICKY TOOLBAR ================= --}}
    <div class="sticky top-0 md:top-20 z-30 bg-[#050505]/90 backdrop-blur-xl border-b border-white/5">
        <div class="w-full max-w-[1400px] mx-auto px-4 md:px-10 py-3 space-y-3">
            <div class="relative">
                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-500 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="search" wire:model.live.debounce.500ms="search" placeholder="Cari judul drama..." aria-label="Cari drama"
                       class="w-full pl-10 pr-10 py-3 bg-zinc-900 border border-zinc-800 rounded-2xl text-sm placeholder:text-zinc-500 focus:outline-none focus:border-red-600/60 focus:ring-1 focus:ring-red-600/40">
                @if(filled($search))
                    <button type="button" wire:click="$set('search', '')" aria-label="Hapus pencarian"
                            class="absolute right-2 top-1/2 -translate-y-1/2 w-8 h-8 flex items-center justify-center rounded-full text-zinc-400 hover:text-white hover:bg-white/10">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                @endif
            </div>

            @if(count($tabs) || count($langs))
                <div class="flex gap-2 overflow-x-auto kz-noscroll -mx-4 px-4 md:mx-0 md:px-0">
                    @foreach($tabs as $k => $label)
                        <button type="button" wire:click="setTab('{{ $k }}')" wire:loading.attr="disabled" wire:target="setTab"
                                class="shrink-0 px-4 py-2 rounded-full text-sm font-bold transition border {{ $tab === $k ? 'bg-white text-black border-white' : 'bg-zinc-900 text-zinc-300 border-zinc-800 active:bg-zinc-800' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                    @foreach($langs as $k => $label)
                        <button type="button" wire:click="changeLang('{{ $k }}')"
                                class="shrink-0 px-4 py-2 rounded-full text-sm font-bold transition border {{ $lang === $k ? 'bg-white text-black border-white' : 'bg-zinc-900 text-zinc-300 border-zinc-800 active:bg-zinc-800' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- ================= GRID ================= --}}
    <main class="w-full max-w-[1400px] mx-auto px-4 md:px-10 pt-5">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-base md:text-xl font-black tracking-tight">{{ filled($search) ? 'Hasil pencarian' : 'Semua Drama' }}</h2>
            <span class="text-[11px] font-bold text-zinc-400 bg-zinc-900 border border-zinc-800 px-2.5 py-1 rounded-full">{{ count($valid) }} judul</span>
        </div>

        <div wire:loading.flex wire:target="setTab, changeLang, search" class="items-center justify-center py-16 gap-3 text-zinc-400">
            <span class="w-5 h-5 rounded-full border-2 border-red-600/30 border-t-red-600 animate-spin"></span>
            <span class="text-sm font-semibold">Memuat drama...</span>
        </div>

        <div wire:loading.remove wire:target="setTab, changeLang, search">
            @if(empty($valid))
                <div class="py-16 text-center bg-zinc-900/40 rounded-3xl border border-dashed border-zinc-800">
                    <p class="text-sm font-semibold text-zinc-300">{{ filled($search) ? 'Drama tidak ditemukan' : 'Belum ada drama' }}</p>
                    <p class="text-xs text-zinc-500 mt-1 px-6">
                        {{ filled($search) ? 'Tidak ada yang cocok dengan "' . $search . '". Coba kata kunci lain.' : 'Drama untuk platform ini sedang disiapkan.' }}
                    </p>
                </div>
            @else
                <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 xl:grid-cols-7 gap-x-2.5 gap-y-4 md:gap-x-4 md:gap-y-6">
                    @foreach($valid as $d)
                        <a href="{{ DracinDetail::getUrl(['network_slug' => $slugVal, 'id' => $d['id']], panel: 'streaming') }}"
                           wire:key="d-{{ $d['id'] }}" class="group block active:scale-[0.97] transition">
                            <div class="relative aspect-[3/4] rounded-xl overflow-hidden bg-zinc-900 ring-1 ring-white/5 group-hover:ring-red-500/60 transition">
                                @if(!empty($d['cover']))
                                    <img src="{{ $img($d['cover']) }}" alt="{{ $d['name'] }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-500" loading="lazy" referrerpolicy="no-referrer">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-[10px] text-zinc-600 font-bold">No Cover</div>
                                @endif
                                <div class="absolute inset-x-0 bottom-0 h-1/2 bg-gradient-to-t from-black/85 to-transparent"></div>

                                <div class="absolute top-1.5 left-1.5 flex flex-col gap-1">
                                    @if(!empty($d['is_hot']) || !empty($d['label_hot']))
                                        <span class="px-1.5 py-0.5 bg-red-600 text-[8px] md:text-[9px] font-black rounded uppercase">Hot</span>
                                    @endif
                                    @if(!empty($d['is_new']) || !empty($d['label_new']))
                                        <span class="px-1.5 py-0.5 bg-emerald-600 text-[8px] md:text-[9px] font-black rounded uppercase">Baru</span>
                                    @endif
                                </div>
                                @if(!empty($d['episodes']))
                                    <span class="absolute bottom-1.5 left-1.5 inline-flex items-center gap-1 text-[10px] font-bold text-white">
                                        <svg class="w-3 h-3 fill-current" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>{{ $d['episodes'] }} eps
                                    </span>
                                @endif
                            </div>
                            <h3 class="mt-1.5 text-xs md:text-sm font-bold text-zinc-100 group-hover:text-red-400 transition line-clamp-2 leading-snug">{{ $d['name'] }}</h3>
                        </a>
                    @endforeach
                </div>

                @if($hasMore)
                    <div x-data x-intersect.margin.600px="$wire.loadDramas()" class="flex justify-center pt-8">
                        <button type="button" wire:click="loadDramas" wire:loading.attr="disabled" wire:target="loadDramas"
                                class="inline-flex items-center gap-2 px-6 py-3 rounded-full bg-zinc-900 border border-zinc-700 text-sm font-bold text-zinc-200 active:scale-95 transition">
                            <span wire:loading.remove wire:target="loadDramas">Muat lebih banyak</span>
                            <span wire:loading.flex wire:target="loadDramas" class="items-center gap-2">
                                <span class="w-4 h-4 rounded-full border-2 border-white/30 border-t-white animate-spin"></span>Memuat...
                            </span>
                        </button>
                    </div>
                @endif
            @endif
        </div>
    </main>
</div>

@once
    @push('styles')
        <style>
            .kz-noscroll { scrollbar-width: none; }
            .kz-noscroll::-webkit-scrollbar { display: none; }
        </style>
    @endpush
@endonce
