<x-filament-panels::page>
    <div class="space-y-5 text-zinc-200">

        {{-- FORM UPLOAD --}}
        <div class="rounded-xl bg-zinc-900 border border-zinc-800 p-4 space-y-3">
            <div>
                <h2 class="font-bold text-sm text-zinc-100">Eksperimen: unggah gambar</h2>
                <p class="text-[11px] text-zinc-500 mt-1">
                    Gambar dinilai dua kali: metrik klasik (GD, tanpa AI, gratis & instan) dan model AI.
                    Maksimal 25 MB, format JPG/PNG/WEBP.
                </p>
            </div>

            <div class="flex flex-wrap items-end gap-3">
                <div class="flex-1 min-w-[240px]">
                    <label class="block text-[11px] text-zinc-400 mb-1">Berkas gambar</label>
                    <input
                        type="file"
                        wire:model="upload"
                        accept="image/jpeg,image/png,image/webp"
                        class="block w-full text-xs text-zinc-300 bg-zinc-950 border border-zinc-700 rounded-xl px-3 py-2 file:mr-3 file:px-3 file:py-1.5 file:rounded-lg file:border-0 file:bg-amber-600 file:text-white file:text-xs file:font-semibold hover:file:bg-amber-500"
                    >
                    @error('upload') <p class="text-[11px] text-rose-400 mt-1">{{ $message }}</p> @enderror
                </div>

                <label class="flex items-center gap-2 text-xs text-zinc-300 px-3 py-2 rounded-xl bg-zinc-950 border border-zinc-700 cursor-pointer">
                    <input type="checkbox" wire:model="fullAnalysis" class="rounded bg-zinc-900 border-zinc-700 text-amber-500">
                    Analisis lengkap (deskriptor motor)
                </label>

                <button
                    type="button"
                    wire:click="analyse"
                    wire:loading.attr="disabled"
                    wire:target="analyse"
                    class="px-4 py-2 rounded-xl bg-amber-600 hover:bg-amber-500 disabled:opacity-50 text-white text-xs font-bold"
                >
                    <span wire:loading.remove wire:target="analyse">Analisis sekarang</span>
                    <span wire:loading wire:target="analyse">Memproses…</span>
                </button>

                <button
                    type="button"
                    wire:click="reset3"
                    class="px-3 py-2 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-zinc-200 text-xs font-semibold"
                >Bersihkan</button>
            </div>

            <div wire:loading wire:target="upload" class="text-[11px] text-amber-400">Mengunggah…</div>

            @if($error)
                <div class="rounded-lg border border-rose-700 bg-rose-950 p-3 text-[11px] text-rose-200">
                    {{ $error }}
                </div>
            @endif
        </div>

        {{-- HASIL --}}
        @if($previewUrl)
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-3">

                {{-- GAMBAR --}}
                <div class="rounded-xl bg-zinc-900 border border-zinc-800 p-3">
                    <p class="text-[11px] uppercase tracking-wide text-zinc-500 mb-2">Gambar</p>
                    <img src="{{ $previewUrl }}" alt="{{ $fileName }}" class="w-full rounded-lg bg-black max-h-[420px] object-contain">
                    <p class="text-[11px] font-mono text-zinc-400 mt-2 truncate">{{ $fileName }}</p>
                    @if($classic)
                        <p class="text-[11px] text-zinc-500">
                            {{ $classic['orig_w'] }}×{{ $classic['orig_h'] }} px
                            · dianalisis pada {{ $classic['analysis_w'] }}×{{ $classic['analysis_h'] }}
                        </p>
                    @endif
                </div>

                {{-- KLASIK --}}
                <div class="rounded-xl bg-zinc-900 border border-zinc-800 p-4">
                    <div class="flex items-center justify-between">
                        <p class="text-[11px] uppercase tracking-wide text-zinc-500">Klasik (tanpa AI)</p>
                        @if($classicSeconds !== null)
                            <span class="text-[10px] font-mono text-emerald-400">{{ $classicSeconds }}s</span>
                        @endif
                    </div>

                    @if($classic)
                        <p class="text-2xl font-black mt-2 {{ $classic['centre_laplacian'] >= 1200 ? 'text-emerald-400' : ($classic['centre_laplacian'] >= 800 ? 'text-emerald-300' : ($classic['centre_laplacian'] >= 400 ? 'text-amber-400' : 'text-rose-400')) }}">
                            {{ $classic['centre_laplacian'] }}
                        </p>
                        <p class="text-xs text-zinc-300 -mt-1">
                            Laplacian area tengah — <span class="font-semibold">{{ $classic['verdict'] }}</span>
                        </p>

                        <div class="mt-3 space-y-1 text-[11px] font-mono text-zinc-300">
                            <div class="flex justify-between"><span class="text-zinc-500">ketajaman tengah (norm)</span><span>{{ $classic['norm_center'] }}</span></div>
                            <div class="flex justify-between"><span class="text-zinc-500">laplacian seluruh frame</span><span>{{ $classic['laplacian'] }}</span></div>
                            <div class="flex justify-between"><span class="text-zinc-500">tenengrad</span><span>{{ $classic['tenengrad'] }}</span></div>
                            <div class="flex justify-between"><span class="text-zinc-500">kontras (std)</span><span>{{ $classic['std'] }}</span></div>
                            <div class="flex justify-between"><span class="text-zinc-500">luminance</span><span>{{ $classic['mean_lum'] }}{{ $classic['is_dark'] ? ' (gelap)' : '' }}</span></div>
                            <div class="flex justify-between"><span class="text-zinc-500">tepi pinggir</span><span>{{ $classic['border_ratio'] }} ({{ $classic['border_label'] }})</span></div>
                        </div>

                        <p class="text-[10px] text-zinc-500 mt-3">
                            Ambang ketajaman: ≥1200 tajam · 800–1199 cukup · 400–799 agak soft · 150–399 blur ringan · &lt;150 blur.
                            Diuji pada foto yang sama: 1872 (asli) → 1406 → 690 → 288 → 132 → 39 (blur berat).
                        </p>
                    @else
                        <p class="text-xs text-zinc-500 mt-3">Belum ada hasil.</p>
                    @endif
                </div>

                {{-- AI --}}
                <div class="rounded-xl bg-zinc-900 border border-zinc-800 p-4">
                    <div class="flex items-center justify-between">
                        <p class="text-[11px] uppercase tracking-wide text-zinc-500">AI ({{ $ai['model'] ?? 'model' }})</p>
                        @if($aiSeconds !== null)
                            <span class="text-[10px] font-mono text-amber-400">{{ $aiSeconds }}s</span>
                        @endif
                    </div>

                    @if($ai)
                        <p class="text-3xl font-black mt-2 {{ $ai['rating'] >= 8 ? 'text-emerald-400' : ($ai['rating'] >= 5 ? 'text-amber-400' : 'text-rose-400') }}">
                            {{ $ai['rating'] }}<span class="text-sm font-normal text-zinc-500">/10</span>
                        </p>
                        <p class="text-xs text-zinc-300 -mt-1">rating keseluruhan</p>

                        <div class="mt-3 space-y-1 text-[11px] font-mono text-zinc-300">
                            <div class="flex justify-between"><span class="text-zinc-500">focus</span><span>{{ $ai['focus'] ?? '–' }}/10</span></div>
                            <div class="flex justify-between"><span class="text-zinc-500">framing</span><span>{{ $ai['framing'] ?? '–' }}/10</span></div>
                            <div class="flex justify-between"><span class="text-zinc-500">potongan</span><span>{{ $ai['crop_severity'] ?? '–' }}</span></div>
                        </div>

                        @if(! empty($ai['note']))
                            <p class="text-[11px] text-zinc-400 italic mt-3">{{ $ai['note'] }}</p>
                        @endif
                    @else
                        <p class="text-xs text-zinc-500 mt-3">Belum ada hasil AI.</p>
                    @endif
                </div>
            </div>

            {{-- PERBANDINGAN --}}
            @if($ai && $classic)
                @php
                    $aiFocus = (int) ($ai['focus'] ?? 0);
                    $conflictBlur = $classic['blur_flag'] && $aiFocus >= 7;
                    $conflictSharp = (! $classic['blur_flag'] && $classic['centre_laplacian'] >= 1200) && $aiFocus <= 5;
                    $conflict = $conflictBlur || $conflictSharp;
                @endphp
                <div class="rounded-xl border p-4 {{ $conflict ? 'border-rose-700 bg-rose-950' : 'border-emerald-800 bg-zinc-900' }}">
                    <p class="text-sm font-bold {{ $conflict ? 'text-rose-200' : 'text-emerald-200' }}">
                        @if($conflictBlur)
                            AI menilai fokus bagus ({{ $aiFocus }}/10), metrik klasik mendeteksi blur
                        @elseif($conflictSharp)
                            Piksel sangat tajam ({{ $classic['centre_laplacian'] }}), AI menilai fokus rendah ({{ $aiFocus }}/10)
                        @else
                            Kedua metode sejalan
                        @endif
                    </p>
                    <p class="text-[11px] text-zinc-400 mt-1">
                        Klasik: <span class="font-semibold">{{ $classic['verdict'] }}</span>
                        (Laplacian tengah {{ $classic['centre_laplacian'] }}) ·
                        AI: rating {{ $ai['rating'] }}/10, focus {{ $aiFocus }}/10, potongan {{ $ai['crop_severity'] ?? '–' }}
                        @if($classic['is_dark']) · <span class="text-amber-300">foto gelap: metrik klasik cenderung turun</span> @endif
                    </p>
                </div>
            @endif

            {{-- DESKRIPTOR LENGKAP --}}
            @if($descriptor)
                <div class="rounded-xl bg-zinc-900 border border-zinc-800 p-4 space-y-2">
                    <p class="text-[11px] uppercase tracking-wide text-zinc-500">Deskriptor motor (AI)</p>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-[11px] font-mono text-zinc-300">
                        <div>merek: <span class="text-zinc-100">{{ $descriptor['brand_guess'] ?? '–' }}</span></div>
                        <div>model: <span class="text-zinc-100">{{ $descriptor['model_guess'] ?? '–' }}</span></div>
                        <div>kategori: <span class="text-zinc-100">{{ $descriptor['category'] ?? '–' }}</span></div>
                        <div>plat: <span class="text-zinc-100">{{ $descriptor['license_plate'] ?? '–' }}</span></div>
                    </div>
                    <details class="mt-2">
                        <summary class="text-[11px] text-zinc-400 cursor-pointer select-none">JSON mentah</summary>
                        <pre class="mt-2 text-[10px] text-zinc-400 whitespace-pre-wrap break-words">{{ json_encode($descriptor, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
                    </details>
                </div>
            @endif
        @endif

        <p class="text-[11px] text-zinc-500">
            Metrik klasik memakai GD di PHP: grayscale 512 px, Laplacian variance, gradien,
            energi tepi, dan kontras. Tidak ada panggilan API — jadi hasilnya selalu sama untuk gambar yang sama.
        </p>
    </div>
</x-filament-panels::page>
