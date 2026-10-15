@php
    use App\Filament\Streaming\Pages\DracinNetwork;

    $online = array_values(array_filter($networks, fn ($n) => !empty($n['is_online'])));
    $offline = array_values(array_filter($networks, fn ($n) => empty($n['is_online'])));
    $netUrl = fn ($n) => $n['id'] === 'bstation' ? '/streaming/bstation' : DracinNetwork::getUrl(['slug' => $n['id']], panel: 'streaming');
    $logo = fn ($n) => !empty($n['logo']) ? url('/api/proxy-image?url=' . urlencode($n['logo'])) : null;
    // Warna aksen per network supaya kartu tidak seragam
    $accents = ['from-rose-600/30', 'from-violet-600/30', 'from-amber-500/30', 'from-sky-600/30', 'from-emerald-600/30', 'from-fuchsia-600/30', 'from-orange-600/30', 'from-cyan-600/30'];
@endphp

<div class="min-h-screen bg-[#050505] text-white pb-28 md:pb-16">

    {{-- ================= HERO ================= --}}
    <section class="relative overflow-hidden">
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_left,rgba(220,38,38,0.25),transparent_55%),radial-gradient(ellipse_at_bottom_right,rgba(147,51,234,0.18),transparent_55%)]" aria-hidden="true"></div>
        <div class="relative w-full max-w-[1400px] mx-auto px-5 md:px-10 pt-24 md:pt-28 pb-8 md:pb-12">
            <span class="inline-flex items-center gap-2 text-[10px] font-black uppercase tracking-[0.2em] text-red-400 bg-red-600/10 border border-red-600/30 px-3 py-1 rounded-full">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3"/></svg>
                Short Drama · Vertikal
            </span>
            <h1 class="mt-4 text-4xl md:text-6xl font-black tracking-tight leading-[0.95]">Dracin</h1>
            <p class="mt-3 max-w-xl text-sm md:text-base text-zinc-400 leading-relaxed">
                Drama pendek format HP. Pilih platform, lalu geser ke atas untuk episode berikutnya dan ke bawah untuk episode sebelumnya.
            </p>

            <div class="mt-6 flex flex-wrap items-center gap-2 text-xs font-semibold">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-white/5 border border-white/10">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>{{ count($online) }} platform aktif
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-white/5 border border-white/10 text-zinc-300">↕ Swipe untuk ganti episode</span>
            </div>
        </div>
    </section>

    <main class="w-full max-w-[1400px] mx-auto px-5 md:px-10">
        @if(empty($networks))
            <div class="flex flex-col items-center justify-center py-24 text-center bg-zinc-900/40 rounded-3xl border border-white/5">
                <h3 class="text-lg font-bold text-zinc-200 mb-2">Belum Ada Platform</h3>
                <p class="text-sm text-zinc-500 max-w-sm mb-6">Platform drama belum tersedia saat ini. Coba lagi nanti.</p>
                <a href="/admin/scraper-setting-page" class="px-5 py-2.5 bg-red-600 hover:bg-red-700 transition rounded-xl text-xs font-bold uppercase tracking-wider">Buka Pengaturan</a>
            </div>
        @else
            {{-- ================= ONLINE ================= --}}
            <h2 class="text-lg md:text-xl font-black tracking-tight mb-4">Pilih Platform</h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3 md:gap-4">
                @foreach($online as $i => $n)
                    <a href="{{ $netUrl($n) }}"
                       class="group relative overflow-hidden rounded-2xl bg-zinc-900/70 ring-1 ring-white/10 hover:ring-red-500/60 active:scale-[0.97] transition p-4 flex flex-col gap-4 min-h-[150px]">
                        <div class="absolute inset-0 bg-gradient-to-br {{ $accents[$i % count($accents)] }} to-transparent opacity-80 group-hover:opacity-100 transition" aria-hidden="true"></div>
                        <div class="relative flex items-start justify-between">
                            <div class="w-14 h-14 md:w-16 md:h-16 rounded-2xl bg-black/60 ring-1 ring-white/10 flex items-center justify-center overflow-hidden p-2">
                                @if($logo($n))
                                    <img src="{{ $logo($n) }}" alt="" class="w-full h-full object-contain" referrerpolicy="no-referrer" loading="lazy"
                                         onerror="this.style.display='none';this.nextElementSibling.classList.remove('hidden')">
                                    <span class="hidden text-xl md:text-2xl font-black">{{ mb_substr($n['name'], 0, 1) }}</span>
                                @else
                                    <span class="text-xl md:text-2xl font-black">{{ mb_substr($n['name'], 0, 1) }}</span>
                                @endif
                            </div>
                            <span class="inline-flex items-center gap-1 text-[9px] font-black uppercase tracking-wider text-emerald-300 bg-emerald-500/15 border border-emerald-500/30 px-1.5 py-0.5 rounded-full">
                                <span class="w-1 h-1 rounded-full bg-emerald-400"></span>Online
                            </span>
                        </div>
                        <div class="relative mt-auto flex items-end justify-between gap-2">
                            <div class="min-w-0">
                                <h3 class="text-base md:text-lg font-black leading-tight line-clamp-1">{{ $n['name'] }}</h3>
                                <p class="text-[11px] text-zinc-400 mt-0.5">Lihat drama</p>
                            </div>
                            <span class="shrink-0 w-9 h-9 rounded-full bg-white text-black flex items-center justify-center group-hover:bg-red-600 group-hover:text-white transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>

            {{-- ================= OFFLINE ================= --}}
            @if(count($offline))
                <h2 class="text-sm font-bold uppercase tracking-[0.2em] text-zinc-500 mt-10 mb-3">Sedang Tidak Tersedia</h2>
                <div class="flex flex-wrap gap-2">
                    @foreach($offline as $n)
                        <span class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-zinc-950 border border-zinc-900 text-xs font-semibold text-zinc-500 select-none">
                            <span class="w-1.5 h-1.5 rounded-full bg-zinc-600"></span>{{ $n['name'] }}
                        </span>
                    @endforeach
                </div>
            @endif
        @endif
    </main>
</div>
