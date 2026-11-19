@php
    $m = $media;
    $backdrop = $m['backdrop'] ?: $m['poster'];
    $poster = $m['poster'];
    $episodes = $episodes ?? [];
    $streamUrl = $stream['stream_url'] ?? '';
    $subtitles = $stream['subtitles'] ?? [];
@endphp

<div class="min-h-screen bg-[#0b0b0d] text-zinc-100">

    {{-- Latar belakang --}}
    <div class="relative">
        <div class="absolute inset-x-0 top-0 h-[420px] overflow-hidden">
            <img src="{{ $backdrop }}" alt="" class="h-full w-full object-cover object-center opacity-30 blur-[2px]">
            <div class="absolute inset-0 bg-gradient-to-b from-[#0b0b0d]/40 via-[#0b0b0d]/80 to-[#0b0b0d]"></div>
        </div>

        <div class="relative mx-auto max-w-[1600px] px-4 pt-6 md:px-8 md:pt-10">
            {{-- Breadcrumb --}}
            <nav class="mb-4 flex items-center gap-2 text-xs text-zinc-400">
                <a href="/" class="hover:text-white">Home</a>
                <span>/</span>
                <a href="/indonesia/vidio" class="hover:text-white">Indonesia</a>
                <span>/</span>
                <span class="truncate text-zinc-300">{{ $m['title'] }}</span>
            </nav>

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">

                {{-- Kolom player --}}
                <div class="min-w-0">
                    <div class="overflow-hidden rounded-xl border border-white/10 bg-black shadow-2xl shadow-black/60">
                        <div wire:ignore id="kz-vidio-player" class="aspect-video w-full"></div>
                    </div>

                    @if(empty($streamUrl))
                        <div class="mt-3 rounded-lg border border-red-500/40 bg-red-500/10 px-4 py-3 text-sm text-red-200">
                            Sumber video untuk tayangan ini belum tersedia. Coba episode lain atau buka lagi beberapa saat.
                        </div>
                    @endif

                    {{-- Info judul --}}
                    <div class="mt-6">
                        <h1 class="text-2xl font-bold tracking-tight md:text-3xl">{{ $m['title'] }}</h1>

                        @if(!empty($m['current_episode_title']))
                            <p class="mt-1 text-sm font-medium text-red-400">{{ $m['current_episode_title'] }}</p>
                        @endif

                        <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-zinc-400">
                            @if(!empty($m['rating']))
                                <span class="flex items-center gap-1 font-semibold text-yellow-400">★ {{ number_format($m['rating'], 1) }}</span>
                            @endif
                            @if(!empty($m['year']))<span>{{ $m['year'] }}</span>@endif
                            @if(!empty($m['age_rating']))
                                <span class="rounded border border-white/30 px-1.5 py-0.5 text-[10px] font-semibold text-zinc-300">{{ $m['age_rating'] }}</span>
                            @endif
                            @if(!empty($m['genres']))
                                <span>{{ implode(' • ', array_slice($m['genres'], 0, 3)) }}</span>
                            @endif
                            <span class="rounded bg-red-600/20 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-red-300">Vidio</span>
                        </div>

                        @if(!empty($m['description']))
                            <p class="mt-4 max-w-3xl text-sm leading-relaxed text-zinc-300">{{ $m['description'] }}</p>
                        @endif
                    </div>
                </div>

                {{-- Kolom episode --}}
                <aside class="lg:pt-1">
                    @if(!empty($episodes))
                        <div class="rounded-xl border border-white/10 bg-white/[0.03] p-4">
                            <div class="mb-3 flex items-center justify-between">
                                <h2 class="text-sm font-bold uppercase tracking-wider text-zinc-300">Daftar Episode</h2>
                                <span class="text-xs text-zinc-500">{{ count($episodes) }} episode</span>
                            </div>

                            <div class="max-h-[520px] space-y-1.5 overflow-y-auto pr-1">
                                @foreach($episodes as $ep)
                                    <a href="/vidio/{{ $m['id'] }}/{{ $ep['id'] }}"
                                       class="flex items-center justify-between gap-3 rounded-lg px-3 py-2 text-sm transition {{ (string) $ep['id'] === (string) $episodeId ? 'bg-red-600/90 font-semibold text-white' : 'bg-white/[0.04] text-zinc-300 hover:bg-white/10' }}">
                                        <span class="truncate">{{ $ep['title'] ?: 'Episode ' . $ep['number'] }}</span>
                                        @if((string) $ep['id'] === (string) $episodeId)
                                            <svg class="h-4 w-4 shrink-0 fill-current" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="rounded-xl border border-white/10 bg-white/[0.03] p-4 text-sm text-zinc-400">
                            Ini film tunggal, tidak ada daftar episode.
                        </div>
                    @endif
                </aside>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/artplayer/5.1.7/artplayer.js"></script>
<script src="https://cdn.jsdelivr.net/npm/hls.js@1.5.18/dist/hls.min.js"></script>
<script>
function initKzVidio() {
    const streamUrl = @json(\App\Services\VidioService::proxyUrl($stream['stream_direct'] ?? ''));
    const subtitles = @json($subtitles);
    const itemId = 'vidio-' + @json((string) $m['id']);
    const episodeId = @json((string) $episodeId);
    const isSeries = @json(!empty($episodes));
    const title = @json($m['title']);
    const poster = @json($poster);
    const progressUrl = @json(route('api.watch-progress'));
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || @json(csrf_token());

    const box = document.getElementById('kz-vidio-player');
    if (!box) return;

    if (!streamUrl) {
        box.innerHTML = '<div class="flex h-full w-full items-center justify-center bg-black text-sm text-zinc-500">Sumber video tidak tersedia</div>';
        return;
    }

    let lastSaved = 0;

    const saveProgress = (art) => {
        const video = art.video;
        if (!video || !video.duration || video.currentTime < 3) return;
        const now = Date.now();
        if (now - lastSaved < 15000) return;
        lastSaved = now;

        fetch(progressUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                item_id: itemId,
                item_type: isSeries ? 'indonesia_series' : 'indonesia_movie',
                progress: video.currentTime,
                duration: video.duration,
                episode: isSeries ? episodeId : null,
                title: title,
                thumbnail: poster,
                url: location.pathname,
            }),
        }).catch(() => {});
    };

    const artOptions = {
        container: box,
        url: streamUrl,
        // Vidio selalu HLS; URL proxy tidak berakhiran .m3u8 sehingga tipenya dipaksa.
        type: 'm3u8',
        poster: poster,
        autoplay: false,
        muted: false,
        volume: 0.8,
        setting: true,
        playbackRate: true,
        aspectRatio: true,
        fullscreen: true,
        fullscreenWeb: true,
        pip: true,
        airplay: true,
        theme: '#e50914',
        customType: {
            m3u8: function (video, url, artInstance) {
                if (!window.Hls || !window.Hls.isSupported()) {
                    video.src = url;
                    return;
                }

                const hls = new window.Hls({
                    enableWorker: true,
                    lowLatencyMode: false,
                    maxBufferLength: 60,
                    backBufferLength: 30,
                });

                artInstance.hls = hls;
                hls.loadSource(url);
                hls.attachMedia(video);

                hls.on(window.Hls.Events.MANIFEST_PARSED, function (_e, data) {
                    if (!data.levels || !data.levels.length) return;
                    artInstance.setting.update({
                        name: 'resolution',
                        html: 'Resolusi',
                        selector: [
                            { html: 'Auto', level: -1, default: true },
                            ...data.levels.map((lv, i) => ({
                                html: (lv.height ? lv.height + 'p' : Math.round(lv.bitrate / 1000) + 'k'),
                                level: i,
                            })),
                        ],
                        onSelect(item) {
                            hls.currentLevel = item.level;
                            return item.html;
                        },
                    });
                });
            },
        },
    };

    if (subtitles.length) {
        artOptions.subtitle = {
            url: subtitles[0].url,
            type: 'vtt',
            style: { color: '#fff', fontSize: '20px' },
        };
        artOptions.settings = [
            {
                name: 'subtitle',
                html: 'Subtitle',
                selector: subtitles.map((s, i) => ({ html: s.label, url: s.url, default: i === 0 })),
                onSelect(item) {
                    art.subtitle.switch(item.url, { name: item.html });
                    return item.html;
                },
            },
        ];
    }

    const art = new Artplayer(artOptions);

    art.on('video:timeupdate', () => saveProgress(art));
    art.on('video:pause', () => saveProgress(art));
    window.addEventListener('pagehide', () => saveProgress(art));
}

// Livewire mengganti isi halaman setelah hydrate; jalankan setelah itu selesai.
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => setTimeout(initKzVidio, 120));
} else {
    setTimeout(initKzVidio, 120);
}
</script>
