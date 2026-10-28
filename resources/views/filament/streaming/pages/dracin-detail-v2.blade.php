{{-- Fullscreen vertical player Dracin V2 untuk short drama. --}}
{{-- Menutupi topbar Filament via position: fixed + z-index tinggi. --}}
<div class="fixed inset-0 z-[100] bg-black text-white overflow-hidden"
     x-data="dracinVerticalPlayer()"
     x-init="init()"
     wire:init="autoPlayMelolo"
     wire:ignore.self
     @pointermove.throttle.150ms="showControls()"
     @pointerdown="showControls()"
     @focusin="showControls()"
     @keydown.window="showControls()">

    {{-- Preload data drama untuk Alpine (episode list drawer) --}}
    <script type="application/json" id="dracin-v2-drama-data">
        @php
            $totalEps = $drama['episodes'] ?? 0;
            $videos = $drama['videos'] ?? [];
            $episodeList = [];
            if (!empty($videos)) {
                foreach ($videos as $v) {
                    $episodeList[] = [
                        'episode' => (int) $v['episode'],
                        'is_lock' => !empty($v['is_lock']),
                        'duration' => $v['duration'] ?? null,
                    ];
                }
            } elseif ($totalEps > 0) {
                for ($i = 1; $i <= $totalEps; $i++) {
                    $episodeList[] = ['episode' => $i, 'is_lock' => false, 'duration' => null];
                }
            }

            $shortmaxDramaData = [
                'title' => $drama['title'] ?? '',
                'intro' => $drama['intro'] ?? '',
                'totalEpisodes' => $totalEps,
                'episodeList' => $episodeList,
                'watchedEpisodes' => $watchedEpisodes ?? [],
                'lastWatchedEpisode' => $lastWatchedEpisode ?? null,
                'isFavorited' => $isFavorited ?? false,
            ];
        @endphp
        {!! json_encode(
            $shortmaxDramaData,
            JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
                | JSON_HEX_TAG
                | JSON_HEX_AMP
                | JSON_HEX_APOS
                | JSON_HEX_QUOT
        ) !!}
    </script>

    {{-- Stage: layar hitam penuh dengan video di tengah (pillarbox pada desktop) --}}
    <div class="w-full h-full flex items-center justify-center relative">

        {{-- Wrapper video 9:16 --}}
        <div class="kz-swipe-zone relative h-full aspect-[9/16] max-w-full max-h-[100dvh] bg-black overflow-hidden select-none"
             @touchstart="handleTouchStart($event)"
             @touchmove="handleTouchMove($event)"
             @touchend="handleTouchEnd($event)"
             @touchcancel="handleTouchCancel()"
             @wheel.passive="handleWheel($event)"
             @keydown.window="handleKeyDown($event)">

            {{-- Buffer 3 slot (prev / current / next). Satu slot tampil, dua lainnya
                 memuat episode sebelum & sesudahnya di background supaya swipe dan
                 auto-next langsung main. wire:ignore: morph Livewire tidak boleh
                 menghapus src yang dipasang JS. --}}
            <div wire:ignore class="absolute inset-0">
                @foreach ([0, 1, 2] as $slotIndex)
                    <video x-ref="slotVideo{{ $slotIndex }}"
                           class="absolute inset-0 w-full h-full object-contain bg-black will-change-transform"
                           :class="activeSlot === {{ $slotIndex }}
                               ? ('opacity-100 z-[1] ' + (isSwiping ? '' : 'transition-transform duration-300 ease-out'))
                               : 'opacity-0 z-0 pointer-events-none'"
                           :style="activeSlot === {{ $slotIndex }} && dragY ? `transform: translateY(${dragY * 0.45}px) scale(${1 - Math.min(Math.abs(dragY), 400) / 4000})` : ''"
                           @click="togglePlayPause()"
                           preload="auto"
                           playsinline></video>
                @endforeach
            </div>

            {{-- Petunjuk saat menggeser --}}
            <template x-if="swipeHint">
                <div class="absolute inset-x-0 z-40 flex justify-center pointer-events-none"
                     :class="swipeHint.dir === 'up' ? 'bottom-24' : 'top-24'">
                    <div class="flex items-center gap-2 px-4 py-2 rounded-full backdrop-blur-md border text-sm font-bold shadow-2xl transition-colors"
                         :class="swipeHint.ready ? 'bg-red-600/90 border-red-400 text-white' : 'bg-black/70 border-white/20 text-white/90'">
                        <svg class="w-4 h-4 transition-transform" :class="swipeHint.dir === 'down' ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                        </svg>
                        <span x-text="swipeHint.text"></span>
                    </div>
                </div>
            </template>

            {{-- Gesture feedback toast --}}
            <div x-show="showGestureToast"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 -translate-y-4 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                 x-transition:leave-end="opacity-0 -translate-y-4 scale-95"
                 class="absolute top-20 inset-x-0 flex items-center justify-center pointer-events-none z-50"
                 style="display: none;">
                <div class="bg-black/90 backdrop-blur-md border border-white/20 text-white text-xs md:text-sm font-bold px-4 py-2 rounded-full shadow-2xl flex items-center gap-2">
                    <span x-text="gestureToastText"></span>
                </div>
            </div>

            {{-- Overlay klik-tengah play/pause (untuk area video) --}}
            <div x-show="showCenterIndicator"
                 x-transition.opacity.duration.300ms
                 class="absolute inset-0 flex items-center justify-center pointer-events-none z-20"
                 style="display: none;">
                <div class="bg-black/50 rounded-full p-6">
                    <svg x-show="isPaused" class="w-12 h-12 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M8 5v14l11-7z" />
                    </svg>
                    <svg x-show="!isPaused" class="w-12 h-12 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M6 4h4v16H6zm8 0h4v16h-4z" />
                    </svg>
                </div>
            </div>

            {{-- Loading spinner --}}
            <div x-show="isBuffering"
                 class="absolute inset-0 flex items-center justify-center pointer-events-none z-20"
                 style="display: none;">
                <div class="w-12 h-12 border-4 border-white/30 border-t-white rounded-full animate-spin"></div>
            </div>

            {{-- Unmute banner --}}
            <div x-show="isMutedAutoplay && controlsVisible"
                 x-transition.opacity.duration.200ms
                 class="absolute inset-0 z-40 pointer-events-none"
                 style="display: none;">
                <button type="button"
                        @click.stop="unmute()"
                        class="absolute pointer-events-auto whitespace-nowrap bg-red-600 hover:bg-red-700 text-white font-bold text-xs md:text-sm px-4 py-2 rounded-full flex items-center gap-2 shadow-lg animate-pulse"
                        style="left: 50%; top: 50%; transform: translate(-50%, -50%);">
                    <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                        <path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02z" />
                    </svg>
                    Ketuk Untuk Bersuara
                </button>
            </div>

            {{-- Header: back + title + eps count --}}
            <div x-show="controlsVisible"
                 x-transition.opacity.duration.200ms
                 class="absolute top-0 left-0 right-0 z-30 p-4 flex items-start gap-3 bg-gradient-to-b from-black/70 via-black/30 to-transparent pointer-events-none">
                <a href="{{ \App\Filament\Streaming\Pages\DracinNetwork::getUrl(['slug' => $networkSlug], panel: 'streaming') }}"
                   class="pointer-events-auto w-9 h-9 flex items-center justify-center rounded-full hover:bg-white/10 transition"
                   title="Kembali">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>

                <div class="flex-1 min-w-0 mt-1">
                    <p class="text-sm md:text-base font-bold truncate drop-shadow-md">
                        {{ $drama['title'] ?? 'Detail Drama' }}
                    </p>
                </div>

                <div class="pointer-events-auto flex flex-col items-end gap-1.5">
                    <div class="flex items-center gap-2">
                        <span class="text-[11px] md:text-xs font-semibold text-white/80 drop-shadow-md">
                            Eps <span x-text="currentEpisode"></span>/<span x-text="drama.totalEpisodes"></span>
                        </span>
                        <button @click.stop="toggleManualHide()"
                                class="w-7 h-7 rounded-full bg-black/40 backdrop-blur border border-white/20 hover:bg-black/60 flex items-center justify-center transition text-white/80 hover:text-white"
                                title="Sembunyikan UI">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                            </svg>
                        </button>
                    </div>
                    <button @click="showEpisodes = true"
                            class="bg-black/40 backdrop-blur border border-white/20 hover:bg-black/60 rounded-full px-3 py-1 text-xs font-bold flex items-center gap-1.5 transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        <span><span x-text="currentEpisode"></span>/<span x-text="drama.totalEpisodes"></span></span>
                    </button>
                </div>
            </div>

            {{-- Floating Unhide Button (Muncul di kanan atas saat UI disembunyikan) --}}
            <button x-show="!controlsVisible"
                    x-transition.opacity.duration.200ms
                    @click.stop="toggleManualHide()"
                    type="button"
                    class="absolute top-4 right-4 z-50 bg-black/60 backdrop-blur-md border border-white/25 hover:bg-black/80 text-white rounded-full px-3 py-1.5 flex items-center gap-1.5 shadow-xl transition active:scale-95 cursor-pointer"
                    style="display: none;"
                    title="Tampilkan UI">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span class="text-xs font-bold tracking-wide">Tampilkan UI</span>
            </button>

            {{-- Floating action panel: kanan tengah --}}
            <div x-show="controlsVisible"
                 x-transition.opacity.duration.200ms
                 class="absolute right-3 top-1/2 -translate-y-1/2 z-30 flex flex-col items-center gap-5 pointer-events-none">
                <div class="flex flex-col items-center gap-1 pointer-events-auto">
                    <button @click="showEpisodes = true"
                            class="w-12 h-12 rounded-full bg-black/40 backdrop-blur border border-white/20 hover:bg-black/60 flex items-center justify-center transition"
                            title="Episode">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <span class="text-[11px] font-semibold drop-shadow-md">Episode</span>
                </div>

                <div class="flex flex-col items-center gap-1 pointer-events-auto">
                    <button @click="toggleMute()"
                            class="w-12 h-12 rounded-full bg-black/40 backdrop-blur border border-white/20 hover:bg-black/60 flex items-center justify-center transition"
                            :title="muted ? 'Nyalakan suara' : 'Bisukan'">
                        <svg x-show="!muted" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.536 8.464a5 5 0 010 7.072M17.828 5.172a9 9 0 010 13.656M11 5L6 9H2v6h4l5 4V5z" />
                        </svg>
                        <svg x-show="muted" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display: none;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5L6 9H2v6h4l5 4V5zM23 9l-6 6M17 9l6 6" />
                        </svg>
                    </button>
                    <span class="text-[11px] font-semibold drop-shadow-md" x-text="muted ? 'Bisu' : 'Suara'"></span>
                </div>

                <div class="flex flex-col items-center gap-1 pointer-events-auto">
                    <button wire:click="toggleFavorite"
                            class="w-12 h-12 rounded-full backdrop-blur border transition flex items-center justify-center
                                {{ ($isFavorited ?? false) ? 'bg-red-600 border-red-500 hover:bg-red-700' : 'bg-black/40 border-white/20 hover:bg-black/60' }}"
                            title="Favorit">
                        @if($isFavorited ?? false)
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" />
                            </svg>
                        @else
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" />
                            </svg>
                        @endif
                    </button>
                    <span class="text-[11px] font-semibold drop-shadow-md">{{ ($isFavorited ?? false) ? 'Favorit' : 'Simpan' }}</span>
                </div>

                {{-- Tombol Mata / Bersihkan Layar (Hide UI) --}}
                <div class="flex flex-col items-center gap-1 pointer-events-auto">
                    <button @click.stop="toggleManualHide()"
                            class="w-12 h-12 rounded-full bg-black/40 backdrop-blur border border-white/20 hover:bg-black/60 flex items-center justify-center transition cursor-pointer text-white/90 hover:text-white"
                            title="Sembunyikan UI">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                        </svg>
                    </button>
                    <span class="text-[11px] font-semibold drop-shadow-md">Hide UI</span>
                </div>
            </div>

            {{-- Bottom info panel: title, intro, progress, quality, episode card --}}
            <div x-show="controlsVisible"
                 x-transition.opacity.duration.200ms
                 class="absolute bottom-0 left-0 right-0 z-30 bg-gradient-to-t from-black/95 via-black/70 to-transparent p-4 pt-16 space-y-3">
                <p class="font-bold text-sm md:text-base leading-tight">
                    {{ $drama['title'] ?? '' }} - Eps <span x-text="currentEpisode"></span>
                </p>

                @if(!empty($drama['intro']))
                    <p class="text-[11px] md:text-xs text-white/70 leading-snug line-clamp-2">
                        {{ $drama['intro'] }}
                    </p>
                @endif

                {{-- Progress bar & Seeking Controls (5s skip icon) --}}
                <div class="flex items-center gap-2 text-[11px] font-mono">
                    <button type="button" @click.stop="skip(-5)"
                            class="w-7 h-7 rounded-full bg-white/10 hover:bg-white/20 active:scale-95 flex items-center justify-center text-white/90 hover:text-white transition cursor-pointer"
                            title="Mundur 5 detik">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0019 16V8a1 1 0 00-1.6-.8l-5.334 4zM4.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0011 16V8a1 1 0 00-1.6-.8l-5.334 4z"/>
                        </svg>
                    </button>
                    <span x-text="fmtTime(currentTime)" class="w-9 text-white/80 text-center select-none"></span>
                    <div class="flex-1 py-2 cursor-pointer relative group flex items-center"
                         @click="seek($event)">
                        <div class="w-full h-1.5 bg-white/20 rounded-full relative overflow-hidden">
                            <div class="absolute inset-y-0 left-0 bg-red-600 rounded-full transition-[width] duration-100"
                                 :style="`width: ${progressPercent}%`"></div>
                        </div>
                        <div class="absolute top-1/2 -translate-y-1/2 w-3.5 h-3.5 rounded-full bg-red-600 shadow-md transition pointer-events-none"
                             :style="`left: calc(${progressPercent}% - 7px)`"></div>
                    </div>
                    <span x-text="fmtTime(duration)" class="w-9 text-white/80 text-center select-none"></span>
                    <button type="button" @click.stop="skip(5)"
                            class="w-7 h-7 rounded-full bg-white/10 hover:bg-white/20 active:scale-95 flex items-center justify-center text-white/90 hover:text-white transition cursor-pointer"
                            title="Maju 5 detik">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.934 12.8a1 1 0 000-1.6l-5.334-4A1 1 0 005 8v8a1 1 0 001.6.8l5.334-4zM19.934 12.8a1 1 0 000-1.6l-5.334-4A1 1 0 0013 8v8a1 1 0 001.6.8l5.334-4z"/>
                        </svg>
                    </button>
                </div>

                {{-- Quality selector --}}
                <div class="flex flex-wrap items-center gap-2">
                    <template x-for="q in qualityList" :key="q.label">
                        <button @click="changeQuality(q)"
                                :class="currentQualityLabel === q.label
                                    ? 'bg-red-600 text-white border-red-600'
                                    : 'bg-white/10 text-white/80 border-white/10 hover:bg-white/20'"
                                class="px-3 py-1 rounded-md text-[11px] font-bold border transition">
                            <span x-text="q.label"></span>
                        </button>
                    </template>
                </div>

                {{-- Episode card (shortcut to drawer) --}}
                <button @click="showEpisodes = true"
                        class="w-full bg-white/5 hover:bg-white/10 border border-white/10 rounded-lg p-3 flex items-center justify-between text-left transition">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-white/60" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        <span class="text-xs font-semibold">Episode <span x-text="currentEpisode"></span></span>
                    </div>
                    <svg class="w-4 h-4 text-white/50" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Drawer daftar episode --}}
    <div x-show="showEpisodes"
         x-transition:enter="transition-opacity duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click.self="showEpisodes = false"
         class="fixed inset-0 z-[110] bg-black/70 backdrop-blur-sm"
         style="display: none;">
        <div x-show="showEpisodes"
             x-transition:enter="transition transform duration-300"
             x-transition:enter-start="translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition transform duration-200"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="translate-x-full"
             class="absolute right-0 top-0 bottom-0 w-full max-w-md bg-[#0e0e0f] border-l border-white/10 flex flex-col">

            <div class="flex items-center justify-between p-5 border-b border-white/10 flex-shrink-0">
                <div>
                    <h2 class="text-sm font-bold uppercase tracking-wider">Pilih Episode</h2>
                    <p class="text-[10px] text-white/50 mt-0.5">
                        <span x-text="drama.totalEpisodes"></span> episode
                    </p>
                </div>
                <button @click="showEpisodes = false"
                        class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-white/10 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto p-5">
                @if(!empty($lastWatchedEpisode))
                    <div class="p-3 mb-4 bg-red-600/10 border border-red-500/25 rounded-xl flex items-center justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <p class="text-[9px] text-red-400/80 font-bold uppercase tracking-wider">Terakhir Ditonton</p>
                            <p class="text-xs font-bold text-red-300 mt-0.5">Episode {{ $lastWatchedEpisode }}</p>
                        </div>
                        <button wire:click="playEpisode({{ (int) $lastWatchedEpisode }})"
                                @click="showEpisodes = false"
                                class="px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white rounded-lg text-[10px] font-bold transition flex-shrink-0">
                            Lanjutkan
                        </button>
                    </div>
                @endif

                <div class="grid grid-cols-4 sm:grid-cols-5 gap-2">
                    <template x-for="ep in drama.episodeList" :key="ep.episode">
                        <button @click="pickEpisode(ep.episode)"
                                :class="{
                                    'bg-red-600 text-white border-red-600 shadow-md shadow-red-600/20': ep.episode === currentEpisode,
                                    'bg-white/5 hover:bg-white/10 border-white/10 text-white/80': ep.episode !== currentEpisode && !isWatched(ep.episode),
                                    'bg-white/[0.02] hover:bg-white/10 border-white/10 text-white/60': ep.episode !== currentEpisode && isWatched(ep.episode),
                                }"
                                class="p-2 rounded-lg border font-bold text-center text-[11px] h-12 flex items-center justify-center relative">
                            <span>Ep <span x-text="ep.episode"></span></span>
                            <template x-if="isWatched(ep.episode) && ep.episode !== currentEpisode">
                                <svg class="w-2.5 h-2.5 text-emerald-400 absolute top-1 left-1" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </template>
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
    <style>
        /* Sembunyikan navbar Filament di halaman shortmax player */
        body>.fi-topbar,
        body>header,
        nav[aria-label="Global"] {
            display: none !important;
        }

        /* Player fullscreen: kunci scroll halaman & matikan pull-to-refresh,
           supaya swipe atas/bawah sepenuhnya dipegang player. */
        html, body {
            overscroll-behavior-y: none !important;
            overflow: hidden !important;
            height: 100% !important;
        }
        .kz-swipe-zone {
            touch-action: none;
            -webkit-user-select: none;
            -webkit-touch-callout: none;
        }
    </style>
@endpush

@push('scripts')
    <script src="/aes-js.min.js"></script>
    <script src="/melolo-decrypt.js"></script>
    <script defer src="https://cdnjs.cloudflare.com/ajax/libs/shaka-player/4.7.9/shaka-player.compiled.js"></script>
    {{-- hls.js dipakai khusus Raptdrama: audio stream Bunny ber-MIME octet-stream
         ditolak Shaka/MSE, tetapi berhasil ditransmux oleh hls.js. --}}
    <script defer src="https://cdn.jsdelivr.net/npm/hls.js@1.5.18/dist/hls.min.js"></script>
    <script>
        let meloloSwReady = false;

        async function initMeloloServiceWorker() {
            if (!('serviceWorker' in navigator)) {
                return false;
            }

            try {
                await navigator.serviceWorker.register('/sw.js', { scope: '/' });

                if (!navigator.serviceWorker.controller) {
                    await new Promise((resolve) => {
                        navigator.serviceWorker.addEventListener(
                            'controllerchange',
                            resolve,
                            { once: true },
                        );
                        setTimeout(resolve, 1500);
                    });
                }

                meloloSwReady = !!navigator.serviceWorker.controller;
                return true;
            } catch (error) {
                console.warn('Service Worker Melolo tidak tersedia:', error);
                return false;
            }
        }

        initMeloloServiceWorker();
    </script>
    <script>
        function registerDracinVerticalPlayer() {
            Alpine.data('dracinVerticalPlayer', () => {
            // Pool slot video disimpan di luar state Alpine (non-reaktif) supaya
            // elemen <video> & instance Shaka/hls.js tidak dibungkus Proxy.
            const pool = {
                slots: [],
                active: 0,
                busy: false,
                dirty: false,
                payloads: {},
                lastSaved: 0,
                neighborsTimer: null,
            };

            return {
                // State player (episode yang sedang tampil)
                activeSlot: 0,
                videoUrl: '',
                qualityList: [],
                currentQualityLabel: '',
                currentEpisode: {{ (int) ($lastWatchedEpisode ?? 1) }},
                nextEpisode: null,
                subtitles: [],
                muted: false,
                userMuted: false,
                isMutedAutoplay: false,
                isPaused: true,
                isBuffering: false,
                currentTime: 0,
                duration: 0,
                showEpisodes: false,
                showCenterIndicator: false,
                centerIndicatorTimeout: null,
                controlsVisible: true,
                controlsTimer: null,
                isManualHide: false,
                useHlsJs: {{ in_array($networkSlug, ['raptdrama', 'reelshort', 'freereels', 'flickreels', 'stardusttv', 'dotdrama', 'reelbuzz', 'shortswave', 'starshort'], true) ? 'true' : 'false' }},

                // Gesture state
                touchStartY: 0,
                touchStartX: 0,
                touchStartTime: 0,
                isSwiping: false,
                dragY: 0,
                lastWheelTime: 0,
                showGestureToast: false,
                gestureToastText: '',
                gestureToastTimeout: null,

                // Metadata dari server (di-hydrate dari <script> tag)
                drama: {
                    title: '',
                    intro: '',
                    totalEpisodes: 0,
                    episodeList: [],
                    watchedEpisodes: [],
                    lastWatchedEpisode: null,
                    isFavorited: false,
                },

                get progressPercent() {
                    if (!this.duration) return 0;
                    return Math.min(100, Math.max(0, (this.currentTime / this.duration) * 100));
                },

                init() {
                    // Alpine memanggil init() otomatis DAN lewat x-init; cegah listener ganda.
                    if (this._booted) return;
                    this._booted = true;

                    try {
                        const raw = document.getElementById('dracin-v2-drama-data')?.textContent;
                        if (raw) this.drama = { ...this.drama, ...JSON.parse(raw) };
                    } catch (e) {
                        console.warn('Failed to parse drama data', e);
                    }

                    window.addEventListener('init-player', (e) => this.handleInitPlayer(e));
                    this.$watch('showEpisodes', () => this.showControls());

                    this.$nextTick(() => {
                        this.setupSlots();
                        this.showControls();
                    });
                },

                // ================= 3-SLOT BUFFER (prev / current / next) =================
                // Slot aktif tampil & bersuara; dua slot lain memuat episode tetangga
                // (mis. Ep 2 - [3] - 4) dalam keadaan pause + mute, sehingga swipe dan
                // auto-next tinggal "tukar slot" tanpa round-trip ke server.

                activeVideo() {
                    return pool.slots[pool.active]?.el || null;
                },

                setupSlots() {
                    if (pool.slots.length) return;

                    [0, 1, 2].forEach((index) => {
                        const el = this.$refs['slotVideo' + index];
                        if (!el) return;

                        const slot = {
                            index, el, ep: null, payload: null, qualityLabel: '',
                            player: null, engine: null, blobUrl: null, gen: 0,
                            loaded: false, ready: false, failed: false, playWhenReady: false,
                        };
                        el.muted = true;
                        pool.slots.push(slot);
                        this.attachSlotEvents(slot);
                    });

                    window.addEventListener('beforeunload', () => this.saveProgress(true));
                },

                saveProgress(force = false) {
                    const v = this.activeVideo();
                    if (!v) return;

                    const ct = v.currentTime || 0;
                    const dur = v.duration || 0;
                    if (!Number.isFinite(dur) || dur < 10 || ct < 3) return;
                    if (!force && Math.abs(ct - pool.lastSaved) < 8) return;
                    pool.lastSaved = ct;

                    const body = JSON.stringify({
                        item_id: '{{ $networkSlug }}:{{ $dramaId }}',
                        item_type: 'dracin',
                        episode: String(this.currentEpisode || 1),
                        title: this.drama?.title || this.drama?.name || 'Dracin',
                        thumbnail: this.drama?.cover || null,
                        url: window.location.pathname,
                        progress: ct,
                        duration: dur,
                    });
                    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
                    if (navigator.sendBeacon && force) {
                        navigator.sendBeacon('/api/watch-progress', new Blob([body], { type: 'application/json' }));
                    } else {
                        fetch('/api/watch-progress', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                            body, keepalive: true,
                        }).catch(() => {});
                    }
                },

                attachSlotEvents(slot) {
                    const v = slot.el;
                    const isActive = () => pool.slots[pool.active] === slot;

                    v.addEventListener('timeupdate', () => {
                        if (!isActive()) return;
                        this.currentTime = v.currentTime;
                        if (Math.floor(v.currentTime) % 10 === 0) this.saveProgress();

                        // Sisa <= 5 detik: pastikan episode berikutnya sudah ter-buffer.
                        const remaining = (v.duration || 0) - v.currentTime;
                        if (Number.isFinite(remaining) && remaining > 0 && remaining <= 5) {
                            this.ensureNextBuffered();
                        }
                    });

                    const syncDuration = () => { if (isActive()) this.duration = v.duration || 0; };
                    v.addEventListener('durationchange', syncDuration);
                    v.addEventListener('loadedmetadata', syncDuration);

                    v.addEventListener('play', () => {
                        if (!isActive()) return;
                        this.isPaused = false;
                        this.showControls();
                    });
                    v.addEventListener('pause', () => {
                        if (!isActive()) return;
                        this.isPaused = true;
                        this.showControls();
                        this.saveProgress(true);
                    });
                    v.addEventListener('waiting', () => {
                        if (!isActive()) return;
                        this.isBuffering = true;
                        this.showControls();
                    });
                    v.addEventListener('canplay', () => {
                        slot.ready = true;
                        if (!isActive()) return;
                        this.isBuffering = false;
                        this.showControls();
                    });
                    v.addEventListener('playing', () => {
                        if (!isActive()) return;
                        this.isBuffering = false;
                        this.showControls();
                    });
                    v.addEventListener('volumechange', () => {
                        if (isActive()) this.muted = v.muted;
                    });
                    v.addEventListener('error', () => {
                        if (!slot.payload || !v.error) return;
                        slot.failed = true;
                        if (isActive()) this.isBuffering = false;
                    });
                    v.addEventListener('ended', () => {
                        if (!isActive()) return;
                        this.saveProgress(true);
                        const nextEp = this.getNextEpisodeNumber();
                        if (nextEp) {
                            // 100% selesai: langsung tukar ke slot yang sudah di-buffer.
                            this.goToEpisode(nextEp, `⏭️ Episode ${nextEp}`);
                        } else {
                            this.triggerGestureToast('🎉 Seluruh episode telah selesai!');
                        }
                    });
                },

                applyEpisodeState(ep, payload, qualityLabel = null) {
                    this.videoUrl = payload?.videoUrl || '';
                    this.qualityList = Array.isArray(payload?.qualityList) ? payload.qualityList : [];
                    const q = (qualityLabel && this.qualityList.find((x) => x.label === qualityLabel))
                        || this.qualityList.find((x) => x.isDefault)
                        || this.qualityList[0];
                    this.currentQualityLabel = q?.label || 'HD';
                    this.currentEpisode = Number(ep);
                    this.nextEpisode = payload?.nextEpisode || null;
                    this.subtitles = Array.isArray(payload?.subtitles) ? payload.subtitles : [];

                    const epStr = String(this.currentEpisode);
                    if (!this.drama.watchedEpisodes.includes(epStr)) {
                        this.drama.watchedEpisodes = [...this.drama.watchedEpisodes, epStr];
                    }
                },

                // Payload dari server (autoplay awal, tombol "Lanjutkan", atau fallback).
                handleInitPlayer(e) {
                    let payload = e.detail;
                    if (payload && payload[0]) payload = payload[0]; // fallback array style
                    if (!payload || !payload.videoUrl) return;

                    this.setupSlots();
                    const slot = pool.slots[pool.active];
                    if (!slot) {
                        setTimeout(() => this.handleInitPlayer(e), 100);
                        return;
                    }

                    const ep = Number(payload.episode || this.currentEpisode || 1);
                    pool.payloads[ep] = { data: payload, at: Date.now() };

                    this.showControls();
                    this.applyEpisodeState(ep, payload);
                    this.loadSlot(slot, ep, payload, { play: true }).then(() => this.refreshNeighbors());
                },

                async fetchPayload(ep) {
                    const cached = pool.payloads[ep];
                    // URL stream sebagian network bertanda tangan; anggap basi setelah 10 menit.
                    if (cached && Date.now() - cached.at < 10 * 60 * 1000) return cached.data;

                    try {
                        const data = await this.$wire.prefetchEpisode(ep);
                        if (data && data.videoUrl) {
                            pool.payloads[ep] = { data, at: Date.now() };
                            return data;
                        }
                    } catch (error) {
                        console.warn('Prefetch episode gagal:', ep, error);
                    }

                    return null;
                },

                ensureNextBuffered() {
                    const next = this.getNextEpisodeNumber();
                    if (!next) return;
                    if (pool.slots.some((s) => s.ep === next && !s.failed)) return;
                    if (Date.now() - (pool.lastEnsure || 0) < 2000) return;
                    pool.lastEnsure = Date.now();
                    this.refreshNeighbors();
                },

                pickFreeSlot(wanted) {
                    const active = pool.slots[pool.active];
                    const keep = new Set([Number(this.currentEpisode), ...wanted]);

                    return pool.slots.find((s) => s !== active && !keep.has(s.ep))
                        || pool.slots.find((s) => s !== active && s.failed)
                        || null;
                },

                // Isi dua slot non-aktif dengan episode berikutnya (prioritas) & sebelumnya.
                async refreshNeighbors() {
                    if (pool.busy) {
                        pool.dirty = true;
                        return;
                    }
                    pool.busy = true;

                    try {
                        do {
                            pool.dirty = false;
                            const current = Number(this.currentEpisode);
                            const wanted = [this.getNextEpisodeNumber(), this.getPrevEpisodeNumber()].filter(Boolean);

                            for (const ep of wanted) {
                                if (Number(this.currentEpisode) !== current) { pool.dirty = true; break; }
                                if (pool.slots.some((s) => s.ep === ep && !s.failed)) continue;

                                const slot = this.pickFreeSlot(wanted);
                                if (!slot) continue;

                                const payload = await this.fetchPayload(ep);
                                if (!payload) continue;
                                if (Number(this.currentEpisode) !== current) { pool.dirty = true; break; }
                                if (pool.slots[pool.active] === slot) continue;

                                await this.loadSlot(slot, ep, payload, { play: false });
                            }
                        } while (pool.dirty);
                    } finally {
                        pool.busy = false;
                    }
                },

                async goToEpisode(ep, label = null) {
                    ep = Number(ep);
                    if (!ep || ep === Number(this.currentEpisode)) return;
                    if (label) this.triggerGestureToast(label);

                    const from = pool.slots[pool.active];
                    const buffered = pool.slots.find((s) => s.ep === ep && s.payload && !s.failed);
                    if (buffered) {
                        this.activateSlot(buffered);
                        return;
                    }

                    // Belum ter-buffer (mis. lompat dari drawer): pakai slot yang paling tidak terpakai.
                    const target = pool.slots.find((s) => s !== from && s.ep == null)
                        || pool.slots.find((s) => s !== from && s.failed)
                        || pool.slots.find((s) => s !== from && Math.abs((s.ep || 0) - ep) > 1)
                        || pool.slots.find((s) => s !== from);
                    if (!target) return;

                    this.activateSlot(target, { pendingEp: ep });
                    this.isBuffering = true;

                    const payload = await this.fetchPayload(ep);
                    if (pool.slots[pool.active] !== target || Number(this.currentEpisode) !== ep) return;

                    if (payload) {
                        this.applyEpisodeState(ep, payload);
                        await this.loadSlot(target, ep, payload, { play: true });
                        this.$wire.markEpisodeWatched(ep);
                        this.refreshNeighbors();
                    } else {
                        // Fallback jalur lama: server memutar & mengirim init-player ke slot aktif.
                        this.$wire.playEpisode(ep);
                    }
                },

                activateSlot(target, opts = {}) {
                    const from = pool.slots[pool.active];
                    if (from && from !== target) this.saveProgress(true);

                    pool.active = target.index;
                    this.activeSlot = target.index;
                    pool.lastSaved = 0;

                    if (from && from !== target) {
                        try { from.el.pause(); } catch (e) {}
                        from.el.muted = true;
                        this.lowerBuffer(from);
                    }

                    if (opts.pendingEp) {
                        this.currentEpisode = Number(opts.pendingEp);
                        this.nextEpisode = null;
                        this.currentTime = 0;
                        this.duration = 0;
                        this.showControls();
                        return;
                    }

                    this.applyEpisodeState(target.ep, target.payload, target.qualityLabel);

                    const v = target.el;
                    if (v.ended || (v.duration && v.duration - v.currentTime < 1)) v.currentTime = 0;
                    this.duration = v.duration || 0;
                    this.currentTime = v.currentTime || 0;
                    this.isBuffering = !target.ready;
                    this.raiseBuffer(target);

                    if (target.loaded) {
                        this.attemptPlay(v);
                    } else {
                        target.playWhenReady = true;
                    }

                    this.$wire.markEpisodeWatched(target.ep);
                    this.refreshNeighbors();
                    this.showControls();
                },

                raiseBuffer(slot) {
                    try {
                        if (slot.engine === 'shaka') slot.player?.configure('streaming.bufferingGoal', 60);
                        if (slot.engine === 'hls' && slot.player) slot.player.config.maxBufferLength = 60;
                    } catch (e) {}
                },

                lowerBuffer(slot) {
                    try {
                        if (slot.engine === 'shaka') slot.player?.configure('streaming.bufferingGoal', 15);
                        if (slot.engine === 'hls' && slot.player) slot.player.config.maxBufferLength = 15;
                    } catch (e) {}
                },

                async resetSlot(slot) {
                    const player = slot.player;
                    slot.player = null;
                    slot.engine = null;
                    if (player) {
                        try { await player.destroy(); } catch (error) { console.warn('Gagal membersihkan player slot:', error); }
                    }
                    if (slot.blobUrl) {
                        URL.revokeObjectURL(slot.blobUrl);
                        slot.blobUrl = null;
                    }

                    const v = slot.el;
                    try { v.pause(); } catch (e) {}
                    if (v.getAttribute('src')) {
                        v.removeAttribute('src');
                        v.load();
                    }

                    slot.ep = null;
                    slot.payload = null;
                    slot.loaded = false;
                    slot.ready = false;
                    slot.failed = false;
                    slot.playWhenReady = false;
                },

                waitFor(condition, isStale, timeout = 10000) {
                    return new Promise((resolve) => {
                        const started = Date.now();
                        const tick = () => {
                            if (condition() || isStale() || Date.now() - started > timeout) return resolve();
                            setTimeout(tick, 100);
                        };
                        tick();
                    });
                },

                async loadSlot(slot, ep, payload, opts = {}) {
                    const { play = false, resumeTime = 0 } = opts;

                    // Generation guard per slot: load baru membatalkan load lama di slot yang sama
                    // (mencegah Shaka 7002 LOAD_INTERRUPTED lalu 3015).
                    const gen = ++slot.gen;
                    const isStale = () => slot.gen !== gen;
                    const isActive = () => pool.slots[pool.active] === slot;

                    await this.resetSlot(slot);
                    if (isStale()) return;

                    const qualityList = Array.isArray(payload.qualityList) ? payload.qualityList : [];
                    const quality = (opts.qualityLabel && qualityList.find((q) => q.label === opts.qualityLabel))
                        || qualityList.find((q) => q.isDefault)
                        || qualityList[0]
                        || null;
                    const url = quality?.url || payload.videoUrl;

                    slot.ep = Number(ep);
                    slot.payload = payload;
                    slot.qualityLabel = quality?.label || 'HD';

                    const video = slot.el;
                    video.muted = isActive() ? !!this.userMuted : true;
                    if (isActive()) this.isBuffering = true;

                    try {
                        const isHls = (url && (url.includes('.m3u8') || url.includes('proxy-hls'))) || (this.useHlsJs && !url.includes('.mp4') && !url.includes('proxy-stream'));
                        if (isHls) {
                            await this.loadWithHlsJs(slot, url, isStale);
                            if (isStale()) return;
                            await this.loadSubtitleTracks(slot, payload.subtitles);
                        } else if (quality?.kid) {
                            await this.loadEncryptedMelolo(slot, url, quality, isStale);
                        } else if (url && (url.toLowerCase().includes('.mp4') || url.includes('proxy-stream'))) {
                            video.preload = 'auto';
                            video.src = url;
                            video.load();
                            await this.loadSubtitleTracks(slot, payload.subtitles);
                        } else {
                            await this.loadWithShaka(slot, url, payload.subtitles, isStale);
                        }

                        if (isStale()) return;

                        if (resumeTime > 0 && Number.isFinite(resumeTime)) {
                            video.currentTime = resumeTime;
                        }

                        slot.loaded = true;
                        if (isActive() && (play || slot.playWhenReady)) {
                            slot.playWhenReady = false;
                            this.attemptPlay(video);
                        } else if (isActive() && video.readyState >= 3) {
                            this.isBuffering = false;
                        }
                    } catch (error) {
                        if (isStale() || error?.code === 7002) return;

                        slot.failed = true;
                        console.error('Gagal memuat episode ' + ep + ':', error);
                        if (isActive()) {
                            this.isBuffering = false;
                            if (quality?.kid) alert('Gagal mendekripsi video Melolo. Silakan coba episode lain.');
                        }
                    }
                },

                async loadWithShaka(slot, url, subtitles, isStale) {
                    await this.waitFor(() => window.shaka, isStale);
                    if (isStale()) return;
                    if (!window.shaka) throw new Error('Shaka Player gagal dimuat');

                    shaka.polyfill.installAll();
                    if (!shaka.Player.isBrowserSupported()) throw new Error('Browser tidak mendukung Shaka Player');

                    const player = new shaka.Player(slot.el);
                    slot.player = player;
                    slot.engine = 'shaka';
                    player.configure({
                        streaming: {
                            // Slot latar cukup buffer awal; dinaikkan ke 60 saat aktif.
                            bufferingGoal: pool.slots[pool.active] === slot ? 60 : 15,
                            rebufferingGoal: 2,
                            bufferBehind: 30,
                            retryParameters: { timeout: 30000, maxAttempts: 5, baseDelay: 500, backoffFactor: 2 },
                        },
                        manifest: {
                            retryParameters: { timeout: 20000, maxAttempts: 3 },
                        },
                    });

                    await player.load(url);
                    if (isStale()) return;
                    await this.loadSubtitleTracks(slot, subtitles);
                },

                async loadWithHlsJs(slot, url, isStale) {
                    await this.waitFor(() => window.Hls, isStale);
                    if (isStale()) return;
                    if (!window.Hls) throw new Error('hls.js gagal dimuat');
                    if (!window.Hls.isSupported()) throw new Error('Browser tidak mendukung playback HLS');

                    const isActive = () => pool.slots[pool.active] === slot;
                    const player = new window.Hls({
                        enableWorker: true,
                        lowLatencyMode: false,
                        maxBufferLength: isActive() ? 60 : 15,
                        backBufferLength: 30,
                    });
                    slot.player = player;
                    slot.engine = 'hls';

                    await new Promise((resolve, reject) => {
                        let settled = false;

                        player.on(window.Hls.Events.ERROR, (_event, data) => {
                            if (isStale() || !data.fatal) return;
                            if (data.type === window.Hls.ErrorTypes.NETWORK_ERROR) { player.startLoad(); return; }
                            if (data.type === window.Hls.ErrorTypes.MEDIA_ERROR) { player.recoverMediaError(); return; }

                            if (!settled) {
                                settled = true;
                                reject(new Error('HLS error: ' + (data.details || 'fatal')));
                                return;
                            }
                            console.error('HLS Raptdrama error', data);
                            slot.failed = true;
                            if (isActive()) this.isBuffering = false;
                        });

                        player.on(window.Hls.Events.MANIFEST_PARSED, () => {
                            if (!settled) { settled = true; resolve(); }
                        });

                        player.loadSource(url);
                        player.attachMedia(slot.el);
                    });
                },

                async loadEncryptedMelolo(slot, url, quality, isStale) {
                    const video = slot.el;

                    let keyHex = quality?.key;
                    if (!keyHex && quality?.kid) {
                        try {
                            const keyResponse = await fetch(
                                `https://melolo-api.dramabos.fun/api/melolo/key?vid=${encodeURIComponent(quality.kid)}`,
                            );
                            if (keyResponse.ok) {
                                const keyPayload = await keyResponse.json();
                                keyHex = keyPayload?.key;
                            }
                        } catch (e) {
                            console.warn('Gagal fetch key Melolo:', e);
                        }
                    }
                    if (isStale()) return;
                    if (!keyHex) throw new Error('Kunci dekripsi Melolo tidak tersedia.');

                    const useBlob = async () => {
                        const blob = await MeloloDecrypt.decrypt(url, keyHex);
                        if (isStale()) return false;
                        if (slot.blobUrl) URL.revokeObjectURL(slot.blobUrl);
                        slot.blobUrl = URL.createObjectURL(blob);
                        video.src = slot.blobUrl;
                        video.load();
                        return true;
                    };

                    const waitCanPlay = (timeoutMs) => new Promise((resolve, reject) => {
                        const timer = window.setTimeout(() => { cleanup(); reject(new Error('timeout')); }, timeoutMs);
                        const onCanPlay = () => { cleanup(); resolve(); };
                        const onError = () => { cleanup(); reject(new Error('error')); };
                        const cleanup = () => {
                            video.removeEventListener('canplay', onCanPlay);
                            video.removeEventListener('error', onError);
                            window.clearTimeout(timer);
                        };
                        video.addEventListener('canplay', onCanPlay, { once: true });
                        video.addEventListener('error', onError, { once: true });
                    });

                    // 100% In-Memory Decryption: download file 10MB utuh dan dekripsi via WebCrypto menjadi Blob URL.
                    // Ini menyelesaikan masalah video stuck/hang akibat Service Worker yang mati di background,
                    // dan memungkinkan video Melolo dimaju-mundurkan (seek) secara instan tanpa re-buffering!
                    if (!(await useBlob())) return;
                    await waitCanPlay(25000).catch(() => {
                        throw new Error('Video Melolo hasil dekripsi gagal diputar.');
                    });
                },

                async loadSubtitleTracks(slot, subtitles) {
                    const list = Array.isArray(subtitles) ? subtitles : [];
                    if (list.length === 0) return;

                    // Bersihkan track subtitle lama dari elemen video
                    const oldTracks = slot.el.querySelectorAll('track');
                    oldTracks.forEach(t => t.remove());

                    if (slot.engine === 'shaka' && slot.player) {
                        for (const subtitle of list) {
                            if (!subtitle?.url) continue;

                            try {
                                const rawLanguage = String(subtitle.lang || 'id');
                                const language = rawLanguage.split(/[-_]/)[0].toLowerCase();

                                await slot.player.addTextTrackAsync(
                                    subtitle.url,
                                    language,
                                    'subtitles',
                                    'text/vtt',
                                    null,
                                    rawLanguage
                                );
                            } catch (error) {
                                console.warn('Gagal menambahkan subtitle:', subtitle, error);
                            }
                        }

                        const tracks = slot.player.getTextTracks();
                        if (tracks.length > 0) {
                            slot.player.selectTextTrack(tracks[0]);
                            slot.player.setTextTrackVisibility(true);
                        }
                    } else {
                        // Native HTML5 track untuk MP4 dan HLS.js
                        list.forEach((sub, idx) => {
                            if (!sub?.url) return;
                            const track = document.createElement('track');
                            track.kind = 'subtitles';
                            track.label = sub.label || sub.lang || (idx === 0 ? 'Indonesia' : 'Subtitle');
                            track.srclang = sub.lang || 'id';
                            track.src = sub.url;
                            if (idx === 0) {
                                track.default = true;
                            }
                            slot.el.appendChild(track);
                        });

                        setTimeout(() => {
                            if (slot.el.textTracks && slot.el.textTracks.length > 0) {
                                for (let i = 0; i < slot.el.textTracks.length; i++) {
                                    slot.el.textTracks[i].mode = (i === 0) ? 'showing' : 'disabled';
                                }
                            }
                        }, 200);
                    }
                },

                attemptPlay(video) {
                    // Hormati pilihan mute user. Jika autoplay bersuara ditolak browser,
                    // mute dan tampilkan banner "Ketuk Untuk Bersuara".
                    const wantMuted = !!this.userMuted;
                    video.muted = wantMuted;
                    this.muted = wantMuted;
                    const p = video.play();
                    if (p !== undefined) {
                        p.catch((err) => {
                            if (wantMuted || err?.name !== 'NotAllowedError') return;
                            console.log('Autoplay bersuara ditolak, coba muted...', err);
                            video.muted = true;
                            this.muted = true;
                            this.isMutedAutoplay = true;
                            video.play().catch(e => console.warn('Muted autoplay juga gagal:', e));
                        });
                    }
                },

                unmute() {
                    const v = this.activeVideo();
                    if (!v) return;
                    v.muted = false;
                    this.muted = false;
                    this.userMuted = false;
                    this.isMutedAutoplay = false;
                    this.showControls();
                },

                toggleMute() {
                    const v = this.activeVideo();
                    if (!v) return;
                    v.muted = !v.muted;
                    this.muted = v.muted;
                    this.userMuted = v.muted;
                    this.isMutedAutoplay = false;
                    this.showControls();
                },

                togglePlayPause() {
                    const v = this.activeVideo();
                    if (!v) return;
                    if (this.isManualHide) {
                        this.isManualHide = false;
                        this.showControls();
                        return;
                    }
                    this.showControls();
                    if (v.paused) {
                        v.play().catch(() => {});
                    } else {
                        v.pause();
                    }
                    this.flashCenterIndicator();
                },

                flashCenterIndicator() {
                    this.showCenterIndicator = true;
                    if (this.centerIndicatorTimeout) clearTimeout(this.centerIndicatorTimeout);
                    this.centerIndicatorTimeout = setTimeout(() => {
                        this.showCenterIndicator = false;
                    }, 500);
                },

                toggleManualHide() {
                    if (this.controlsVisible) {
                        this.controlsVisible = false;
                        this.isManualHide = true;
                        if (this.controlsTimer) {
                            window.clearTimeout(this.controlsTimer);
                            this.controlsTimer = null;
                        }
                    } else {
                        this.isManualHide = false;
                        this.showControls();
                    }
                },

                showControls() {
                    if (this.isManualHide) {
                        return;
                    }

                    this.controlsVisible = true;

                    if (this.controlsTimer) {
                        window.clearTimeout(this.controlsTimer);
                        this.controlsTimer = null;
                    }

                    if (
                        this.isPaused
                        || this.isBuffering
                        || this.showEpisodes
                        || this.isMutedAutoplay
                    ) {
                        return;
                    }

                    this.controlsTimer = window.setTimeout(() => {
                        if (
                            !this.isPaused
                            && !this.isBuffering
                            && !this.showEpisodes
                            && !this.isMutedAutoplay
                        ) {
                            this.controlsVisible = false;
                        }
                    }, 3500);
                },

                seek(evt) {
                    const el = evt.currentTarget;
                    const rect = el.getBoundingClientRect();
                    const pct = Math.min(1, Math.max(0, (evt.clientX - rect.left) / rect.width));
                    const v = this.activeVideo();
                    if (v && v.duration) {
                        v.currentTime = pct * v.duration;
                        this.currentTime = v.currentTime;
                        this.showControls();
                    }
                },

                skip(delta) {
                    const v = this.activeVideo();
                    if (v && v.duration) {
                        const target = Math.min(v.duration, Math.max(0, v.currentTime + delta));
                        v.currentTime = target;
                        this.currentTime = target;
                        this.showControls();
                    }
                },

                async changeQuality(q) {
                    if (!q || this.currentQualityLabel === q.label) return;

                    const slot = pool.slots[pool.active];
                    const video = slot?.el;
                    if (!video || !slot.payload) return;

                    const wasPaused = video.paused;
                    const savedTime = video.currentTime;
                    this.currentQualityLabel = q.label;
                    this.videoUrl = q.url;

                    await this.loadSlot(slot, slot.ep, slot.payload, {
                        play: !wasPaused,
                        resumeTime: savedTime,
                        qualityLabel: q.label,
                    });
                },

                pickEpisode(ep) {
                    this.showEpisodes = false;
                    if (Number(ep) === Number(this.currentEpisode)) return;
                    this.goToEpisode(ep, `Episode ${ep}`);
                },

                isWatched(ep) {
                    return (this.drama.watchedEpisodes || []).includes(String(ep));
                },

                fmtTime(secs) {
                    if (!secs || isNaN(secs) || !isFinite(secs)) return '0:00';
                    const m = Math.floor(secs / 60);
                    const s = Math.floor(secs % 60).toString().padStart(2, '0');
                    return `${m}:${s}`;
                },

                // --- SWIPE GESTURES & BINGE MODE ---
                // Gestur vertikal ala TikTok:
                // - geser ke ATAS  = episode berikutnya
                // - geser ke BAWAH = episode sebelumnya
                // Wrapper memakai `touch-action: none` + overscroll-behavior none,
                // supaya browser tidak mengambil alih tarikan ke bawah (pull-to-refresh)
                // yang dulu memicu touchcancel sehingga "episode sebelumnya" tidak pernah jalan.
                handleTouchStart(e) {
                    if (this.showEpisodes) return;
                    if (e.target.closest('button, a, input, select, [data-no-swipe]')) return;
                    this.touchStartY = e.touches[0].clientY;
                    this.touchStartX = e.touches[0].clientX;
                    this.touchStartTime = Date.now();
                    this.isSwiping = false;
                    this.dragY = 0;
                },

                handleTouchMove(e) {
                    if (this.showEpisodes || !this.touchStartTime) return;
                    const deltaY = e.touches[0].clientY - this.touchStartY;
                    const deltaX = e.touches[0].clientX - this.touchStartX;
                    if (!this.isSwiping && Math.abs(deltaY) > 12 && Math.abs(deltaY) > Math.abs(deltaX)) {
                        this.isSwiping = true;
                    }
                    if (this.isSwiping) {
                        if (e.cancelable) e.preventDefault();
                        // Beri resistensi di ujung (tidak ada episode sebelumnya/berikutnya)
                        const blocked = (deltaY > 0 && !this.getPrevEpisodeNumber()) || (deltaY < 0 && !this.getNextEpisodeNumber());
                        this.dragY = blocked ? deltaY * 0.2 : deltaY;
                    }
                },

                handleTouchEnd(e) {
                    if (this.showEpisodes) return;
                    if (!this.touchStartTime) return;
                    const touch = e.changedTouches && e.changedTouches[0];
                    const deltaY = touch ? touch.clientY - this.touchStartY : this.dragY;
                    const deltaX = touch ? touch.clientX - this.touchStartX : 0;
                    const elapsed = Date.now() - this.touchStartTime;
                    const h = this.activeVideo()?.clientHeight || window.innerHeight;

                    // Lolos kalau: geser jauh (>18% tinggi layar) ATAU flick cepat (>50px < 450ms)
                    const isVertical = Math.abs(deltaY) > Math.abs(deltaX) * 1.2;
                    const far = Math.abs(deltaY) > h * 0.18;
                    const flick = elapsed < 450 && Math.abs(deltaY) > 50;

                    if (this.isSwiping && isVertical && (far || flick)) {
                        if (deltaY < 0) {
                            this.playNextEpisode();
                        } else {
                            this.playPrevEpisode();
                        }
                    }
                    this.resetDrag();
                },

                handleTouchCancel() {
                    this.resetDrag();
                },

                resetDrag() {
                    this.touchStartTime = 0;
                    this.isSwiping = false;
                    this.dragY = 0;
                },

                get swipeHint() {
                    if (!this.isSwiping || Math.abs(this.dragY) < 30) return null;
                    const h = this.activeVideo()?.clientHeight || window.innerHeight;
                    const ready = Math.abs(this.dragY) > h * 0.18;
                    if (this.dragY < 0) {
                        const n = this.getNextEpisodeNumber();
                        return { dir: 'up', ready, text: n ? (ready ? `Lepas untuk Episode ${n}` : `Episode ${n}`) : 'Episode terakhir' };
                    }
                    const p = this.getPrevEpisodeNumber();
                    return { dir: 'down', ready, text: p ? (ready ? `Lepas untuk Episode ${p}` : `Episode ${p}`) : 'Sudah episode pertama' };
                },

                handleWheel(e) {
                    if (this.showEpisodes) return;
                    const now = Date.now();
                    if (now - this.lastWheelTime < 1000) return;

                    if (Math.abs(e.deltaY) > 75) {
                        this.lastWheelTime = now;
                        if (e.deltaY > 0) {
                            this.playNextEpisode();
                        } else {
                            this.playPrevEpisode();
                        }
                    }
                },

                handleKeyDown(e) {
                    if (['INPUT', 'TEXTAREA'].includes(document.activeElement?.tagName)) return;

                    if (e.key === 'ArrowDown' || e.key === 'PageDown') {
                        e.preventDefault();
                        this.playNextEpisode();
                    } else if (e.key === 'ArrowUp' || e.key === 'PageUp') {
                        e.preventDefault();
                        this.playPrevEpisode();
                    } else if (e.key === 'ArrowLeft' || e.key === 'j') {
                        e.preventDefault();
                        this.skip(-5);
                    } else if (e.key === 'ArrowRight' || e.key === 'l') {
                        e.preventDefault();
                        this.skip(5);
                    } else if (e.key === ' ' || e.key === 'k') {
                        e.preventDefault();
                        this.togglePlayPause();
                    } else if (e.key === 'm') {
                        e.preventDefault();
                        this.toggleMute();
                    }
                },

                getNextEpisodeNumber() {
                    if (this.nextEpisode) return Number(this.nextEpisode);
                    const curr = Number(this.currentEpisode) || 1;
                    if (Array.isArray(this.drama.episodeList) && this.drama.episodeList.length > 0) {
                        const nextObj = this.drama.episodeList.find(e => Number(e.episode) > curr);
                        if (nextObj) return Number(nextObj.episode);
                    }
                    if (this.drama.totalEpisodes && curr < Number(this.drama.totalEpisodes)) {
                        return curr + 1;
                    }
                    return null;
                },

                getPrevEpisodeNumber() {
                    const curr = Number(this.currentEpisode) || 1;
                    if (Array.isArray(this.drama.episodeList) && this.drama.episodeList.length > 0) {
                        const prevList = this.drama.episodeList.filter(e => Number(e.episode) < curr);
                        if (prevList.length > 0) {
                            const prevObj = prevList[prevList.length - 1];
                            return Number(prevObj.episode);
                        }
                    }
                    if (curr > 1) return curr - 1;
                    return null;
                },

                playNextEpisode() {
                    const nextEp = this.getNextEpisodeNumber();
                    if (!nextEp) {
                        this.triggerGestureToast('Ini adalah episode terakhir');
                        return;
                    }
                    this.goToEpisode(nextEp, `⏭️ Episode ${nextEp}`);
                },

                playPrevEpisode() {
                    const prevEp = this.getPrevEpisodeNumber();
                    if (!prevEp) {
                        this.triggerGestureToast('Ini sudah episode pertama');
                        return;
                    }
                    this.goToEpisode(prevEp, `⏮️ Episode ${prevEp}`);
                },

                triggerGestureToast(msg) {
                    this.gestureToastText = msg;
                    this.showGestureToast = true;
                    if (this.gestureToastTimeout) clearTimeout(this.gestureToastTimeout);
                    this.gestureToastTimeout = setTimeout(() => {
                        this.showGestureToast = false;
                    }, 1500);
                },
            };
            });
        }

        if (window.Alpine) {
            registerDracinVerticalPlayer();
        } else {
            document.addEventListener('alpine:init', registerDracinVerticalPlayer);
        }
    </script>
@endpush