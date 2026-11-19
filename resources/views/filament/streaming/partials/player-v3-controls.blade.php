{{--
    Player v3 — kontrol bersama untuk Film, TV Series, dan Indonesia.
    Butuh state Alpine di parent: qualityOptions, activeQuality, setQuality(),
    subtitles, activeSubtitleLang, setSubtitle().
--}}
<div class="grid grid-cols-2 gap-2" x-data="{ qOpen: false, sOpen: false }">
    {{-- Kualitas --}}
    <div class="relative">
        <button type="button" @click="qOpen = !qOpen" :aria-expanded="qOpen"
                class="w-full flex items-center justify-between gap-2 px-3 py-2.5 rounded-xl bg-zinc-950 ring-1 ring-white/10 hover:ring-white/25 transition text-left">
            <span class="flex flex-col leading-tight min-w-0">
                <span class="text-[10px] font-bold uppercase tracking-[0.16em] text-zinc-500">Kualitas</span>
                <span class="text-xs font-bold text-zinc-100 truncate" x-text="activeQuality ? activeQuality + 'p' : 'Auto'"></span>
            </span>
            <svg class="w-4 h-4 text-zinc-500 shrink-0 transition" :class="qOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
            </svg>
        </button>

        <div x-show="qOpen" x-cloak @click.outside="qOpen = false"
             class="absolute left-0 right-0 bottom-full mb-2 z-50 rounded-xl bg-zinc-950 ring-1 ring-white/10 shadow-2xl shadow-black/60 overflow-hidden">
            <template x-for="opt in qualityOptions" :key="opt.quality">
                <button type="button" @click="setQuality(opt.quality); qOpen = false"
                        class="w-full flex items-center justify-between px-3 py-2.5 text-xs font-semibold transition hover:bg-white/5"
                        :class="activeQuality === opt.quality ? 'text-red-400' : 'text-zinc-300'">
                    <span x-text="opt.label"></span>
                    <svg x-show="activeQuality === opt.quality" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                    </svg>
                </button>
            </template>
            <p x-show="!qualityOptions.length" class="px-3 py-2.5 text-xs text-zinc-500">Auto</p>
        </div>
    </div>

    {{-- Subtitle --}}
    <div class="relative">
        <button type="button" @click="sOpen = !sOpen" :aria-expanded="sOpen"
                class="w-full flex items-center justify-between gap-2 px-3 py-2.5 rounded-xl bg-zinc-950 ring-1 ring-white/10 hover:ring-white/25 transition text-left">
            <span class="flex flex-col leading-tight min-w-0">
                <span class="text-[10px] font-bold uppercase tracking-[0.16em] text-zinc-500">Subtitle</span>
                <span class="text-xs font-bold text-zinc-100 truncate" x-text="activeSubtitleLang === 'off' ? 'Mati' : (subtitleLabel(activeSubtitleLang) || 'Mati')"></span>
            </span>
            <svg class="w-4 h-4 text-zinc-500 shrink-0 transition" :class="sOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
            </svg>
        </button>

        <div x-show="sOpen" x-cloak @click.outside="sOpen = false"
             class="absolute left-0 right-0 bottom-full mb-2 z-50 rounded-xl bg-zinc-950 ring-1 ring-white/10 shadow-2xl shadow-black/60 overflow-hidden">
            <template x-for="sub in subtitles" :key="sub.lang">
                <button type="button" @click="setSubtitle(sub.lang); sOpen = false"
                        class="w-full flex items-center justify-between px-3 py-2.5 text-xs font-semibold transition hover:bg-white/5"
                        :class="activeSubtitleLang === sub.lang ? 'text-red-400' : 'text-zinc-300'">
                    <span x-text="sub.label || sub.lang"></span>
                    <svg x-show="activeSubtitleLang === sub.lang" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                    </svg>
                </button>
            </template>
            <button type="button" @click="setSubtitle('off'); sOpen = false"
                    class="w-full flex items-center justify-between px-3 py-2.5 text-xs font-semibold transition hover:bg-white/5 border-t border-white/5"
                    :class="activeSubtitleLang === 'off' ? 'text-red-400' : 'text-zinc-400'">
                <span>Mati</span>
                <svg x-show="activeSubtitleLang === 'off'" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                </svg>
            </button>
        </div>
    </div>
</div>
