<x-filament-panels::page>
    @php
        $s = $compare['summary'] ?? [];
    @endphp

    @if(! ($compare['found'] ?? false))
        <div class="rounded-xl border border-amber-600 bg-amber-950 p-5 text-amber-100">
            <p class="font-bold">Data perbandingan belum ada untuk konten ini.</p>
            <p class="text-sm mt-1 text-amber-200">
                Jalankan <code class="font-mono">python3 compute.py {{ $contentId }}</code>
                di <code class="font-mono">/root/quality-compare</code> dulu.
            </p>
        </div>
    @else
        <div
            x-data="qualityCompare({
                photos: @js($compare['photos']),
                contentId: {{ (int) $contentId }}
            })"
            class="space-y-5 text-zinc-200"
        >
            {{-- RINGKASAN --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div class="rounded-xl bg-zinc-900 border border-zinc-800 p-4">
                    <p class="text-[11px] uppercase tracking-wide text-zinc-500">Korelasi AI focus vs klasik</p>
                    <div class="mt-2 flex items-end gap-4">
                        <div>
                            <p class="text-2xl font-black {{ ($s['spearman_norm'] ?? 0) > 0.4 ? 'text-emerald-400' : 'text-rose-400' }}">
                                {{ number_format((float) ($s['spearman_norm'] ?? 0), 2) }}
                            </p>
                            <p class="text-[10px] text-zinc-500">ternormalisasi</p>
                        </div>
                        <div>
                            <p class="text-2xl font-black text-zinc-400">{{ number_format((float) ($s['spearman_raw'] ?? 0), 2) }}</p>
                            <p class="text-[10px] text-zinc-500">mentah</p>
                        </div>
                    </div>
                    <p class="text-[11px] text-zinc-500 mt-2">Mendekati 0 = kedua metode tidak sejalan.</p>
                </div>

                <div class="rounded-xl bg-zinc-900 border border-zinc-800 p-4">
                    <p class="text-[11px] uppercase tracking-wide text-zinc-500">Daya beda (stdev)</p>
                    <div class="mt-2 space-y-1 text-sm font-mono">
                        <div class="flex justify-between"><span class="text-zinc-400">AI focus</span><span class="text-rose-300">{{ number_format((float) ($s['ai_focus_stdev'] ?? 0), 2) }}</span></div>
                        <div class="flex justify-between"><span class="text-zinc-400">AI rating</span><span class="text-rose-300">{{ number_format((float) ($s['ai_rating_stdev'] ?? 0), 2) }}</span></div>
                        <div class="flex justify-between"><span class="text-zinc-400">Klasik mentah</span><span class="text-emerald-300">{{ number_format((float) ($s['classic_raw_stdev'] ?? 0), 2) }}</span></div>
                        <div class="flex justify-between"><span class="text-zinc-400">Klasik norm</span><span class="text-emerald-300">{{ number_format((float) ($s['classic_norm_stdev'] ?? 0), 2) }}</span></div>
                    </div>
                    <p class="text-[11px] text-zinc-500 mt-2">Makin besar = makin bisa membedakan foto.</p>
                </div>

                <div class="rounded-xl bg-zinc-900 border border-zinc-800 p-4">
                    <p class="text-[11px] uppercase tracking-wide text-zinc-500">Temuan lain</p>
                    <div class="mt-2 space-y-1 text-sm font-mono">
                        <div class="flex justify-between"><span class="text-zinc-400">Foto</span><span>{{ (int) ($s['total'] ?? 0) }}</span></div>
                        <div class="flex justify-between"><span class="text-zinc-400">AI: potong mayor</span><span class="text-rose-300">{{ (int) ($s['ai_major'] ?? 0) }}</span></div>
                        <div class="flex justify-between"><span class="text-zinc-400">AI: kena tepi</span><span class="text-amber-300">{{ (int) ($s['ai_minor'] ?? 0) }}</span></div>
                        <div class="flex justify-between"><span class="text-zinc-400">Klasik: tepi tinggi</span><span class="text-amber-300">{{ (int) ($s['border_high'] ?? 0) }}</span></div>
                        <div class="flex justify-between"><span class="text-zinc-400">Foto kembar</span><span class="text-sky-300">{{ (int) ($s['dup_extra'] ?? 0) }}</span></div>
                        <div class="flex justify-between"><span class="text-zinc-400">Foto gelap</span><span class="text-sky-300">{{ (int) ($s['dark_photos'] ?? 0) }}</span></div>
                        <div class="flex justify-between"><span class="text-zinc-400">Selisih ≥4 poin</span><span class="text-rose-300">{{ (int) ($s['big_gap'] ?? 0) }}</span></div>
                    </div>
                </div>
            </div>

            {{-- KONTROL --}}
            <div class="flex flex-wrap items-center gap-2">
                <input
                    type="text"
                    x-model="search"
                    placeholder="Cari nama file…"
                    class="px-3 py-2 text-xs rounded-xl bg-zinc-950 border border-zinc-700 text-zinc-100 placeholder-zinc-500 focus:outline-none focus:border-amber-500"
                >
                <select x-model="sort" class="px-3 py-2 text-xs rounded-xl bg-zinc-950 border border-zinc-700 text-zinc-200">
                    <option value="gap">Urut: selisih terbesar (AI vs klasik)</option>
                    <option value="ai_asc">Urut: rating AI terendah</option>
                    <option value="ai_desc">Urut: rating AI tertinggi</option>
                    <option value="classic_asc">Urut: klasik terendah (paling blur)</option>
                    <option value="classic_desc">Urut: klasik tertinggi (paling tajam)</option>
                    <option value="name">Urut: nama file</option>
                </select>
                <select x-model="filter" class="px-3 py-2 text-xs rounded-xl bg-zinc-950 border border-zinc-700 text-zinc-200">
                    <option value="all">Semua foto</option>
                    <option value="gap">Hanya selisih ≥4 poin</option>
                    <option value="dark">Hanya foto gelap (luminance &lt; 45)</option>
                    <option value="dup">Hanya foto kembar</option>
                </select>
                <span class="text-xs text-zinc-400 font-mono ml-auto">
                    <span x-text="shown.length"></span> / <span x-text="photos.length"></span> foto
                </span>
            </div>

            {{-- GRID --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3">
                <template x-for="p in shown" :key="p.id">
                    <div class="rounded-xl bg-zinc-900 border border-zinc-800 overflow-hidden">
                        <div class="flex gap-3 p-3">
                            <div class="shrink-0">
                                <img
                                    :src="'/media/photos/' + p.id + '/thumbnail?mode=fit'"
                                    :alt="p.file_name"
                                    loading="lazy"
                                    class="w-24 h-24 object-contain rounded-lg bg-black"
                                >
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-mono text-zinc-300 truncate" x-text="p.file_name"></p>
                                <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                                    <span
                                        class="px-1.5 py-0.5 rounded text-[10px] font-bold"
                                        :class="p.ai_rating >= 8 ? 'bg-emerald-600 text-white' : (p.ai_rating >= 6 ? 'bg-amber-600 text-white' : 'bg-rose-600 text-white')"
                                        x-text="'AI ' + p.ai_rating"
                                    ></span>
                                    <span
                                        class="px-1.5 py-0.5 rounded text-[10px] font-bold"
                                        :class="p.classic_norm >= 7 ? 'bg-emerald-700 text-white' : (p.classic_norm >= 4 ? 'bg-amber-700 text-white' : 'bg-rose-700 text-white')"
                                        x-text="'Klasik ' + p.classic_norm"
                                    ></span>
                                    <button
                                        type="button"
                                        @click="open = (open === p.id ? null : p.id)"
                                        class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-zinc-800 hover:bg-zinc-700 text-zinc-200"
                                        x-text="open === p.id ? 'Tutup' : 'Detail'"
                                    ></button>
                                </div>
                                <p class="text-[10px] text-zinc-500 mt-1.5">
                                    selisih <span class="font-mono" x-text="gapOf(p)"></span> poin
                                </p>
                            </div>
                        </div>

                        {{-- DETAIL --}}
                        <div x-show="open === p.id" x-cloak class="border-t border-zinc-800 p-3 space-y-2 text-[11px]">
                            <div class="grid grid-cols-2 gap-x-3 gap-y-1 font-mono">
                                <div class="text-zinc-500">AI rating</div><div x-text="p.ai_rating"></div>
                                <div class="text-zinc-500">AI focus</div><div x-text="p.ai_focus"></div>
                                <div class="text-zinc-500">AI framing</div><div x-text="p.ai_framing"></div>
                                <div class="text-zinc-500">AI potongan</div><div x-text="p.ai_crop || '-'"></div>
                                <div class="text-zinc-500">Klasik tajam (mentah)</div><div x-text="p.classic_sharp"></div>
                                <div class="text-zinc-500">Klasik tajam (norm)</div><div x-text="p.classic_norm"></div>
                                <div class="text-zinc-500">laplacian</div><div x-text="p.laplacian"></div>
                                <div class="text-zinc-500">tenengrad</div><div x-text="p.tenengrad"></div>
                                <div class="text-zinc-500">border ratio</div><div x-text="p.border_ratio"></div>
                                <div class="text-zinc-500">luminance</div><div x-text="p.mean_lum"></div>
                                <div class="text-zinc-500">kontras (std)</div><div x-text="p.std"></div>
                                <div class="text-zinc-500">grup kembar</div><div x-text="p.dup_group"></div>
                                <div class="text-zinc-500">resolusi</div><div x-text="p.orig_w + '×' + p.orig_h"></div>
                            </div>
                            <div x-show="p.ai_note">
                                <p class="text-zinc-500">catatan AI</p>
                                <p class="text-zinc-300 italic" x-text="p.ai_note"></p>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <p class="text-[11px] text-zinc-500">
                Data klasik digenerate {{ $compare['generated_at'] ?? '-' }} tanpa API AI.
                Skor klasik 1–10 dipetakan dari persentil batch, jadi relatif terhadap konten ini.
            </p>
        </div>
    @endif

    @script
    <script>
        // Alpine component: dipasang sekali per halaman.
        if (!window.qualityCompare) {
            window.qualityCompare = function (config) {
                return {
                    photos: config.photos || [],
                    contentId: config.contentId,
                    search: '',
                    sort: 'gap',
                    filter: 'all',
                    open: null,

                    gapOf(p) {
                        return Math.abs((p.ai_rating || 0) - (p.classic_norm || 0)).toFixed(1);
                    },

                    get shown() {
                        const q = this.search.trim().toLowerCase();

                        let list = this.photos.filter((p) => {
                            if (q && !p.file_name.toLowerCase().includes(q)) return false;
                            if (this.filter === 'gap' && Math.abs((p.ai_rating || 0) - (p.classic_norm || 0)) < 4) return false;
                            if (this.filter === 'dark' && (p.mean_lum || 0) >= 45) return false;
                            if (this.filter === 'dup' && (p.dup_group || 0) === 0) return false;
                            return true;
                        });

                        const byName = (a, b) => a.file_name.localeCompare(b.file_name);

                        switch (this.sort) {
                            case 'ai_asc': list.sort((a, b) => (a.ai_rating - b.ai_rating) || byName(a, b)); break;
                            case 'ai_desc': list.sort((a, b) => (b.ai_rating - a.ai_rating) || byName(a, b)); break;
                            case 'classic_asc': list.sort((a, b) => (a.classic_norm - b.classic_norm) || byName(a, b)); break;
                            case 'classic_desc': list.sort((a, b) => (b.classic_norm - a.classic_norm) || byName(a, b)); break;
                            case 'name': list.sort(byName); break;
                            default:
                                list.sort((a, b) =>
                                    Math.abs((b.ai_rating || 0) - (b.classic_norm || 0)) -
                                    Math.abs((a.ai_rating || 0) - (a.classic_norm || 0))
                                );
                        }

                        return list;
                    },
                };
            };
        }
    </script>
    @endscript
</x-filament-panels::page>
