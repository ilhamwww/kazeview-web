@php
    $m = $media;
    $servers = $stream['servers'] ?? [];
@endphp

<div class="min-h-screen bg-[#0b0b0f] pb-24 text-zinc-100">
    @include('filament.streaming.partials.animek-subnav', ['active' => ''])

    <div class="relative">
        <div class="absolute inset-x-0 top-0 h-[380px] overflow-hidden" aria-hidden="true">
            <img src="{{ $m['poster'] }}" alt="" class="h-full w-full scale-110 object-cover opacity-20 blur-2xl">
            <div class="absolute inset-0 bg-gradient-to-b from-[#0b0b0f]/40 via-[#0b0b0f]/80 to-[#0b0b0f]"></div>
        </div>

        <div class="relative mx-auto max-w-[1500px] px-4 md:px-8">
            <a href="{{ $m['url'] }}" class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1.5 text-[12.5px] font-semibold text-zinc-200 ring-1 ring-white/10 transition hover:bg-white/20">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg> Detail anime
            </a>

            <div class="mt-4 grid gap-6 lg:grid-cols-[minmax(0,1fr)_340px]">
                <div class="min-w-0">
                    <div class="overflow-hidden rounded-xl border border-white/10 bg-black shadow-2xl shadow-black/60">
                        <div wire:ignore id="kz-animek-player" class="aspect-video w-full"></div>
                    </div>

                    @if(empty($stream))
                        <div class="mt-3 rounded-lg border border-red-500/40 bg-red-500/10 px-4 py-3 text-sm text-red-200">
                            Sumber video episode ini sedang tidak tersedia. Coba episode lain.
                        </div>
                    @endif

                    {{-- Prev / Next + server --}}
                    <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                        <div class="flex gap-2">
                            @if($prev)
                                <a href="{{ $prev['url'] }}" class="rounded-lg bg-white/[0.07] px-3.5 py-2 text-[13px] font-semibold text-zinc-200 transition hover:bg-white/15">‹ Ep {{ $prev['number'] }}</a>
                            @endif
                            @if($next)
                                <a href="{{ $next['url'] }}" class="rounded-lg bg-red-600 px-3.5 py-2 text-[13px] font-bold text-white transition hover:bg-red-500">Ep {{ $next['number'] }} ›</a>
                            @endif
                        </div>
                        @if(count($servers) > 1)
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="text-[11px] font-semibold uppercase tracking-wider text-zinc-500">Server</span>
                                @foreach($servers as $i => $srv)
                                    <button type="button" data-src="{{ $srv['url'] }}"
                                            class="kz-server rounded-md border px-2.5 py-1 text-[11.5px] font-semibold transition {{ $i === 0 ? 'border-red-500/50 bg-red-600/15 text-white' : 'border-white/10 bg-white/5 text-zinc-300 hover:bg-white/10' }}">{{ $srv['name'] }}</button>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="mt-6">
                        <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                            <a href="{{ $m['url'] }}" class="hover:text-red-300">{{ $m['title'] }}</a>
                        </h1>
                        @if($current)
                            <p class="mt-1 text-sm font-semibold text-red-400">Episode {{ $current['number'] }}@if($current['date']) · <span class="font-normal text-zinc-400">{{ \Illuminate\Support\Carbon::parse($current['date'])->translatedFormat('j F Y') }}</span>@endif</p>
                        @endif

                        <div class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1.5 text-xs text-zinc-400">
                            @if($m['rating'])<span class="font-semibold text-yellow-400">★ {{ number_format($m['rating'], 1) }}</span>@endif
                            @if($m['year'])<span>{{ $m['year'] }}</span>@endif
                            @if($m['kind'])<span>{{ $m['kind'] }}</span>@endif
                            @if($m['status_label'])<span>{{ $m['status_label'] }}</span>@endif
                            @foreach($m['genre_links'] as $g)
                                <a href="/animek/genre?g={{ $g['id'] }}" class="rounded-full bg-white/[0.06] px-2 py-0.5 font-semibold text-zinc-300 hover:bg-white/15 hover:text-white">{{ $g['name'] }}</a>
                            @endforeach
                        </div>

                        @if($m['synopsis'])
                            <div class="mt-5 max-w-3xl" x-data="{ more: false }">
                                <h2 class="text-[11px] font-black uppercase tracking-[0.18em] text-zinc-500">Sinopsis</h2>
                                <p :class="more ? '' : 'line-clamp-3'" class="mt-2 text-sm leading-relaxed text-zinc-300">{{ $m['synopsis'] }}</p>
                                @if(mb_strlen($m['synopsis']) > 240)
                                    <button type="button" @click="more = !more" class="mt-1 text-xs font-bold text-red-400" x-text="more ? 'Tutup' : 'Baca selengkapnya'"></button>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>

                <aside>
                    <div class="rounded-xl border border-white/10 bg-white/[0.03] p-4" x-data="{ q: '' }">
                        <div class="mb-3 flex items-center justify-between gap-2">
                            <h2 class="text-sm font-bold uppercase tracking-wider text-zinc-300">Episode <span class="text-zinc-500">({{ count($episodes) }})</span></h2>
                            @if(count($episodes) > 12)
                                <input x-model="q" type="search" inputmode="numeric" placeholder="No. ep" aria-label="Cari nomor episode"
                                       class="w-24 rounded-full border border-white/10 bg-black/30 px-3 py-1 text-xs text-white outline-none focus:border-red-500/60">
                            @endif
                        </div>
                        <div id="kz-ep-list" class="grid max-h-[520px] grid-cols-4 gap-1.5 overflow-y-auto pr-1">
                            @foreach($episodes as $ep)
                                <a href="{{ $ep['url'] }}" x-show="q === '' || '{{ $ep['number'] }}'.includes(q.trim())"
                                   title="{{ $ep['title'] }}" @if($ep['id'] === $currentId) data-current="1" aria-current="true" @endif
                                   class="rounded-lg py-2 text-center text-sm font-bold transition {{ $ep['id'] === $currentId ? 'bg-red-600 text-white' : 'bg-white/[0.05] text-zinc-300 hover:bg-white/15 hover:text-white' }}">{{ $ep['number'] }}</a>
                            @endforeach
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/artplayer/5.1.7/artplayer.js"></script>
<script>
function initKzAnimek() {
    const videoUrl = @json($stream['video_url'] ?? '');
    const poster = @json($m['poster']);
    const nextUrl = @json($next['url'] ?? '');
    const box = document.getElementById('kz-animek-player');

    const cur = document.querySelector('#kz-ep-list [data-current]');
    if (cur) cur.scrollIntoView({ block: 'nearest' });

    if (!box || box.dataset.ready === '1' || !videoUrl) return;
    box.dataset.ready = '1';

    const art = new Artplayer({
        container: box,
        url: videoUrl,
        type: 'mp4',
        poster: poster,
        autoplay: false,
        volume: 0.8,
        setting: true,
        playbackRate: true,
        aspectRatio: true,
        fullscreen: true,
        fullscreenWeb: true,
        pip: true,
        theme: '#e50914',
    });

    // Lanjut otomatis ke episode berikutnya saat selesai.
    if (nextUrl) art.on('video:ended', () => { window.location.href = nextUrl; });

    document.querySelectorAll('.kz-server').forEach((btn) => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.kz-server').forEach((b) => {
                b.classList.remove('border-red-500/50', 'bg-red-600/15', 'text-white');
                b.classList.add('border-white/10', 'bg-white/5', 'text-zinc-300');
            });
            btn.classList.remove('border-white/10', 'bg-white/5', 'text-zinc-300');
            btn.classList.add('border-red-500/50', 'bg-red-600/15', 'text-white');
            art.switchUrl(btn.dataset.src);
        });
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => setTimeout(initKzAnimek, 150));
} else {
    setTimeout(initKzAnimek, 150);
}
</script>
