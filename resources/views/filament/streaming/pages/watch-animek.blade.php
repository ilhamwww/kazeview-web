@php
    $m = $media;
    $current = collect($episodes)->firstWhere('id', $currentId);
    $servers = $stream['servers'] ?? [];
@endphp

<div class="min-h-screen bg-[#0b0b0d] text-zinc-100">
    <div class="relative">
        <div class="absolute inset-x-0 top-0 h-[380px] overflow-hidden">
            <img src="{{ $m['poster'] }}" alt="" class="h-full w-full object-cover opacity-20 blur-[2px]">
            <div class="absolute inset-0 bg-gradient-to-b from-[#0b0b0d]/40 via-[#0b0b0d]/80 to-[#0b0b0d]"></div>
        </div>

        <div class="relative mx-auto max-w-[1500px] px-4 pt-6 md:px-8 md:pt-10">
            <nav class="mb-4 flex items-center gap-2 text-xs text-zinc-400">
                <a href="/" class="hover:text-white">Home</a>
                <span>/</span>
                <a href="/animek" class="hover:text-white">Anime</a>
                <span>/</span>
                <span class="truncate text-zinc-300">{{ $m['title'] }}</span>
            </nav>

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
                <div class="min-w-0">
                    <div class="overflow-hidden rounded-xl border border-white/10 bg-black shadow-2xl shadow-black/60">
                        <div wire:ignore id="kz-animek-player" class="aspect-video w-full"></div>
                    </div>

                    @if(empty($stream))
                        <div class="mt-3 rounded-lg border border-red-500/40 bg-red-500/10 px-4 py-3 text-sm text-red-200">
                            Sumber video episode ini sedang tidak tersedia. Coba episode lain.
                        </div>
                    @endif

                    <div class="mt-6">
                        <h1 class="text-2xl font-bold tracking-tight md:text-3xl">{{ $m['title'] }}</h1>

                        @if($current)
                            <p class="mt-1 text-sm font-medium text-red-400">{{ $current['title'] }}</p>
                        @endif

                        <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-zinc-400">
                            @if(!empty($m['rating']))
                                <span class="flex items-center gap-1 font-semibold text-yellow-400">★ {{ number_format($m['rating'], 1) }}</span>
                            @endif
                            @if(!empty($m['year']))<span>{{ $m['year'] }}</span>@endif
                            @if(!empty($m['status']))<span class="capitalize">{{ $m['status'] }}</span>@endif
                            @if(!empty($m['duration']))<span>{{ $m['duration'] }}</span>@endif
                            @if(!empty($m['genres']))
                                <span>{{ implode(' • ', array_slice($m['genres'], 0, 3)) }}</span>
                            @endif
                        </div>

                        @if(!empty($m['synopsis']))
                            <div class="mt-5 max-w-3xl" x-data="{ more: false }">
                                <h2 class="text-[11px] font-black uppercase tracking-[0.18em] text-zinc-500">Sinopsis</h2>
                                <p @click="more = !more" :class="more ? '' : 'line-clamp-3'"
                                   class="mt-2 cursor-pointer text-sm leading-relaxed text-zinc-300">{{ $m['synopsis'] }}</p>
                            </div>
                        @endif

                        @if(count($servers) > 1)
                            <div class="mt-5 flex flex-wrap items-center gap-2">
                                <span class="text-[11px] font-semibold uppercase tracking-wider text-zinc-500">Server</span>
                                @foreach($servers as $i => $srv)
                                    <button type="button" data-src="{{ $srv['url'] }}"
                                            class="kz-server rounded-md border border-white/10 bg-white/5 px-2.5 py-1 text-[11px] font-semibold text-zinc-300 transition hover:bg-white/10 {{ $i === 0 ? 'border-red-500/50 bg-red-600/15 text-white' : '' }}">
                                        {{ $srv['name'] }}
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                <aside class="lg:pt-1">
                    <div class="rounded-xl border border-white/10 bg-white/[0.03] p-4">
                        <div class="mb-3 flex items-center justify-between">
                            <h2 class="text-sm font-bold uppercase tracking-wider text-zinc-300">Episode</h2>
                            <span class="text-xs text-zinc-500">{{ count($episodes) }} episode</span>
                        </div>

                        <div class="max-h-[520px] space-y-1.5 overflow-y-auto pr-1">
                            @foreach($episodes as $ep)
                                <a href="/animek/{{ $m['path'] }}/{{ $ep['id'] }}"
                                   class="flex items-center justify-between gap-3 rounded-lg px-3 py-2 text-sm transition {{ $ep['id'] === $currentId ? 'bg-red-600/90 font-semibold text-white' : 'bg-white/[0.04] text-zinc-300 hover:bg-white/10' }}">
                                    <span class="truncate">{{ $ep['title'] ?: ('Episode ' . $ep['number']) }}</span>
                                </a>
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
    const box = document.getElementById('kz-animek-player');

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

    // Ganti server: muat ulang sumber pada player yang sama.
    document.querySelectorAll('.kz-server').forEach((btn) => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.kz-server').forEach((b) => {
                b.classList.remove('border-red-500/50', 'bg-red-600/15', 'text-white');
                b.classList.add('border-white/10', 'bg-white/5', 'text-zinc-300');
            });
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
