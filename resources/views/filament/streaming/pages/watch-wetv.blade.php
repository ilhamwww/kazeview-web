@php
    $m = $media;
    $backdrop = $m['backdrop'] ?: $m['poster'];
    $streamUrl = $stream['stream_url'] ?? '';
    $subtitles = $stream['subtitles'] ?? [];
    $qualities = $stream['qualities'] ?? [];
    $current = collect($episodes)->firstWhere('vid', $currentVid);
@endphp

<div class="min-h-screen bg-[#0b0b0d] text-zinc-100">
    <div class="relative">
        <div class="absolute inset-x-0 top-0 h-[420px] overflow-hidden">
            <img src="{{ $backdrop }}" alt="" class="h-full w-full object-cover object-center opacity-25 blur-[2px]">
            <div class="absolute inset-0 bg-gradient-to-b from-[#0b0b0d]/40 via-[#0b0b0d]/80 to-[#0b0b0d]"></div>
        </div>

        <div class="relative mx-auto max-w-[1600px] px-4 pt-6 md:px-8 md:pt-10">
            <nav class="mb-4 flex items-center gap-2 text-xs text-zinc-400">
                <a href="/" class="hover:text-white">Home</a>
                <span>/</span>
                <a href="/wetv" class="hover:text-white">WeTV</a>
                <span>/</span>
                <span class="truncate text-zinc-300">{{ $m['title'] }}</span>
            </nav>

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
                <div class="min-w-0">
                    <div class="overflow-hidden rounded-xl border border-white/10 bg-black shadow-2xl shadow-black/60">
                        <div wire:ignore id="kz-wetv-player" class="aspect-video w-full"></div>
                    </div>

                    @if(empty($streamUrl))
                        <div class="mt-3 rounded-lg border border-red-500/40 bg-red-500/10 px-4 py-3 text-sm text-red-200">
                            Sumber video episode ini tidak tersedia. Coba episode lain.
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
                            @if(!empty($m['region']))<span>{{ $m['region'] }}</span>@endif
                            @if(!empty($m['genres']))
                                <span>{{ implode(' • ', array_slice($m['genres'], 0, 3)) }}</span>
                            @endif
                            <span class="rounded bg-red-600/20 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-red-300">WeTV</span>
                        </div>

                        @if(!empty($m['description']))
                            <p class="mt-4 max-w-3xl whitespace-pre-line text-sm leading-relaxed text-zinc-300">{{ $m['description'] }}</p>
                        @endif
                    </div>
                </div>

                <aside class="lg:pt-1">
                    <div class="rounded-xl border border-white/10 bg-white/[0.03] p-4">
                        <div class="mb-3 flex items-center justify-between">
                            <h2 class="text-sm font-bold uppercase tracking-wider text-zinc-300">Daftar Episode</h2>
                            <span class="text-xs text-zinc-500">{{ count($episodes) }} episode</span>
                        </div>

                        <div class="max-h-[520px] space-y-1.5 overflow-y-auto pr-1">
                            @foreach($episodes as $ep)
                                <a href="/wetv/{{ $m['cid'] }}/{{ $ep['vid'] }}"
                                   class="flex items-center justify-between gap-3 rounded-lg px-3 py-2 text-sm transition {{ $ep['vid'] === $currentVid ? 'bg-red-600/90 font-semibold text-white' : 'bg-white/[0.04] text-zinc-300 hover:bg-white/10' }}">
                                    <span class="truncate">{{ $ep['title'] ?: 'Episode ' . $ep['number'] }}</span>
                                    @if(!empty($ep['free']))
                                        <span class="shrink-0 rounded bg-emerald-500/20 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider text-emerald-300">Gratis</span>
                                    @endif
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
<script src="https://cdn.jsdelivr.net/npm/hls.js@1.5.18/dist/hls.min.js"></script>
<script>
function initKzWetv() {
    const streamUrl = @json($streamUrl);
    const qualities = @json($qualities);
    const subtitles = @json($subtitles);
    const itemId = 'wetv-' + @json((string) $m['cid']);
    const episodeId = @json((string) $currentVid);
    const title = @json($m['title']);
    const poster = @json($m['poster']);
    const progressUrl = @json(route('api.watch-progress'));
    const csrf = @json(csrf_token());

    const box = document.getElementById('kz-wetv-player');
    if (!box || box.dataset.ready === '1') return;

    if (!streamUrl) {
        box.innerHTML = '<div class="flex h-full w-full items-center justify-center bg-black text-sm text-zinc-500">Sumber video tidak tersedia</div>';
        return;
    }

    box.dataset.ready = '1';
    let lastSaved = 0;

    const saveProgress = (art) => {
        const video = art.video;
        if (!video || !video.duration || video.currentTime < 3) return;
        const now = Date.now();
        if (now - lastSaved < 15000) return;
        lastSaved = now;

        fetch(progressUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify({
                item_id: itemId,
                item_type: 'indonesia_series',
                progress: video.currentTime,
                duration: video.duration,
                episode: episodeId,
                title: title,
                thumbnail: poster,
                url: location.pathname,
            }),
        }).catch(() => {});
    };

    const options = {
        container: box,
        url: streamUrl,
        type: 'm3u8',
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
        customType: {
            m3u8: function (video, url, artInstance) {
                if (!window.Hls || !window.Hls.isSupported()) {
                    video.src = url;
                    return;
                }

                const hls = new window.Hls({ enableWorker: true, lowLatencyMode: false, maxBufferLength: 60, backBufferLength: 30 });
                artInstance.hls = hls;
                hls.loadSource(url);
                hls.attachMedia(video);

                hls.on(window.Hls.Events.MANIFEST_PARSED, function () {
                    if (qualities.length < 2) return;
                    artInstance.setting.update({
                        name: 'resolution',
                        html: 'Resolusi',
                        selector: qualities.map((q, i) => ({ html: q.label, url: q.url, default: i === 0, level: i })),
                        onSelect(item) {
                            hls.loadSource(item.url);
                            hls.attachMedia(video);
                            return item.html;
                        },
                    });
                });
            },
        },
    };

    if (subtitles.length) {
        options.subtitle = {
            url: subtitles[0].url,
            type: 'srt',
            style: { color: '#fff', fontSize: '20px' },
        };
        options.settings = [{
            name: 'subtitle',
            html: 'Subtitle',
            selector: subtitles.map((s, i) => ({ html: s.label, url: s.url, default: i === 0 })),
            onSelect(item) {
                art.subtitle.switch(item.url, { name: item.html, type: 'srt' });
                return item.html;
            },
        }];
    }

    const art = new Artplayer(options);

    art.on('video:timeupdate', () => saveProgress(art));
    art.on('video:pause', () => saveProgress(art));
    window.addEventListener('pagehide', () => saveProgress(art));
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => setTimeout(initKzWetv, 150));
} else {
    setTimeout(initKzWetv, 150);
}
</script>
