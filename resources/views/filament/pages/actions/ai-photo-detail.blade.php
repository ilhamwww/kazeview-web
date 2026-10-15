@php
    $d = $descriptor ?? [];
    $helmet = $d['helmet'] ?? [];
    $brand = ($d['brand_guess'] ?? 'unknown') !== 'unknown' ? ucfirst($d['brand_guess']) : '-';
    $model = ($d['model_guess'] ?? 'unknown') !== 'unknown' ? strtoupper($d['model_guess']) : '-';
    $category = ($d['category'] ?? 'unknown') !== 'unknown' ? ucfirst($d['category']) : '-';
    $primaryColor = ($d['primary_color'] ?? 'unknown') !== 'unknown' ? ucfirst($d['primary_color']) : '-';
    $secondaryColors = !empty($d['secondary_colors']) ? implode(', ', $d['secondary_colors']) : '-';
    $plate = $d['license_plate'] ?? ($d['visible_text'][0] ?? null);
    $plate = ($plate && $plate !== 'unknown') ? strtoupper($plate) : '-';

    $storagePath = $record->storagePath();
    $imageExists = \Illuminate\Support\Facades\Storage::disk('public')->exists($storagePath);
    $imageUrl = $imageExists ? \Illuminate\Support\Facades\Storage::disk('public')->url($storagePath) : null;
@endphp

<div class="space-y-5 text-sm text-zinc-200">
    {{-- Header: Foto & Meta Badges (Dark Container) --}}
    <div class="p-4 rounded-xl bg-zinc-900/90 border border-zinc-800 shadow-sm">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-5 items-center">
            @if($imageUrl)
                <div class="md:col-span-5 flex flex-col items-center justify-center">
                    <a href="{{ $imageUrl }}" target="_blank" rel="noopener noreferrer" class="group relative block w-full overflow-hidden rounded-xl border border-zinc-700/80 bg-black/40 shadow-md">
                        <img 
                            src="{{ $imageUrl }}" 
                            alt="{{ $record->file_name }}" 
                            class="max-h-60 w-full object-cover transition duration-300 group-hover:scale-105"
                        />
                        <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition flex items-center justify-center text-white text-xs font-semibold gap-1">
                            <span>🔍 Buka Foto Asli</span>
                        </div>
                    </a>
                    <span class="mt-2 text-xs font-mono text-zinc-400 truncate max-w-full" title="{{ $record->file_name }}">
                        {{ $record->file_name }}
                    </span>
                </div>
                <div class="md:col-span-7 space-y-3">
            @else
                <div class="md:col-span-12 space-y-3">
            @endif
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $index->status === 'indexed' ? 'bg-emerald-950 text-emerald-300 border border-emerald-800/80' : 'bg-amber-950 text-amber-300 border border-amber-800/80' }}">
                            ● {{ ucfirst($index->status) }}
                        </span>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-950 text-blue-300 border border-blue-800/80">
                            Vektor: {{ $index->embedding_dimensions ?? 'Unknown' }} dims
                        </span>
                    </div>

                    <div class="space-y-1.5 text-xs text-zinc-400">
                        <div><strong class="text-zinc-300">Vision Model:</strong> <span class="font-mono text-zinc-200">{{ $index->vision_model ?? '-' }}</span></div>
                        <div><strong class="text-zinc-300">Prompt Version:</strong> <span class="font-mono text-zinc-200">{{ $index->prompt_version ?? '-' }}</span></div>
                        <div><strong class="text-zinc-300">Indexed At:</strong> <span class="text-zinc-200">{{ $index->indexed_at?->format('d M Y, H:i:s') ?? '-' }}</span></div>
                        @if($record->folder)
                            <div><strong class="text-zinc-300">Folder:</strong> <span class="text-zinc-200">{{ $record->folder->relative_path ?: 'Root' }}</span></div>
                        @endif
                    </div>
                </div>
        </div>
    </div>

    {{-- Detail Grid: Motor --}}
    <div class="p-4 rounded-xl border border-zinc-800 bg-zinc-900/90 shadow-sm space-y-4">
        <h4 class="font-bold text-base text-zinc-100 flex items-center gap-2 border-b border-zinc-800 pb-2">
            🏍️ Analisis Identitas Motor
        </h4>

        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
            <div>
                <span class="block text-xs text-zinc-400">Brand</span>
                <span class="font-semibold text-zinc-100 text-sm">{{ $brand }}</span>
            </div>
            <div>
                <span class="block text-xs text-zinc-400">Model</span>
                <span class="font-semibold text-zinc-100 text-sm">{{ $model }}</span>
            </div>
            <div>
                <span class="block text-xs text-zinc-400">Kategori</span>
                <span class="font-medium text-zinc-200">{{ $category }}</span>
            </div>
            <div>
                <span class="block text-xs text-zinc-400">Warna Utama</span>
                <span class="font-medium text-zinc-200">{{ $primaryColor }}</span>
            </div>
            <div>
                <span class="block text-xs text-zinc-400">Warna Sekunder</span>
                <span class="font-medium text-zinc-200">{{ $secondaryColors }}</span>
            </div>
            <div>
                <span class="block text-xs text-zinc-400">Plat / Nomor</span>
                <span class="font-semibold text-emerald-400 tracking-wide">{{ $plate }}</span>
            </div>
        </div>

        @if(!empty($d['visible_text']))
            <div class="pt-2.5 border-t border-zinc-800">
                <span class="block text-xs text-zinc-400 mb-1.5">Teks / Stiker yang Terbaca:</span>
                <div class="flex flex-wrap gap-1.5">
                    @foreach($d['visible_text'] as $text)
                        <span class="px-2 py-0.5 rounded text-xs bg-zinc-800 text-zinc-200 border border-zinc-700/70 font-mono">{{ $text }}</span>
                    @endforeach
                </div>
            </div>
        @endif

        @if(!empty($d['fairing']) || !empty($d['windshield']) || !empty($d['wheel_color']))
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 pt-2 text-xs text-zinc-300">
                <div><strong class="text-zinc-400">Fairing:</strong> {{ $d['fairing'] ?? '-' }}</div>
                <div><strong class="text-zinc-400">Windshield:</strong> {{ $d['windshield'] ?? '-' }}</div>
                <div><strong class="text-zinc-400">Pelek:</strong> {{ $d['wheel_color'] ?? '-' }}</div>
            </div>
        @endif
    </div>

    {{-- Detail Grid: Helm & Rider --}}
    <div class="p-4 rounded-xl border border-zinc-800 bg-zinc-900/90 shadow-sm space-y-4">
        <h4 class="font-bold text-base text-zinc-100 flex items-center gap-2 border-b border-zinc-800 pb-2">
            🪖 Analisis Helm & Pengendara
        </h4>

        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
            <div>
                <span class="block text-xs text-zinc-400">Tipe Helm</span>
                <span class="font-medium text-zinc-200">{{ ucfirst($helmet['type'] ?? ($d['rider_helmet'] ?? '-')) }}</span>
            </div>
            <div>
                <span class="block text-xs text-zinc-400">Warna Helm</span>
                <span class="font-medium text-zinc-200">{{ ucfirst($helmet['primary_color'] ?? '-') }}</span>
            </div>
            <div>
                <span class="block text-xs text-zinc-400">Kaca / Visor</span>
                <span class="font-medium text-zinc-200">{{ ucfirst($helmet['visor'] ?? '-') }}</span>
            </div>
        </div>

        @if(!empty($d['rider_clothing']))
            <div class="text-xs text-zinc-300 pt-1">
                <strong class="text-zinc-400">Pakaian Pengendara:</strong> {{ $d['rider_clothing'] }}
            </div>
        @endif

        @if(!empty($d['distinctive_features']))
            <div class="pt-2.5 border-t border-zinc-800">
                <span class="block text-xs text-zinc-400 mb-1.5">Ciri Khas / Modifikasi Unik:</span>
                <ul class="list-disc list-inside text-xs text-zinc-300 space-y-0.5">
                    @foreach($d['distinctive_features'] as $feature)
                        <li>{{ $feature }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    {{-- Raw JSON --}}
    <details class="rounded-xl border border-zinc-800 overflow-hidden bg-zinc-950">
        <summary class="px-4 py-2.5 cursor-pointer text-xs font-semibold text-zinc-400 hover:text-zinc-200 select-none">
            Lihat Raw JSON Descriptor
        </summary>
        <div class="p-3 bg-black/60 border-t border-zinc-800 overflow-x-auto">
            <pre class="text-xs font-mono text-emerald-400">{{ json_encode($d, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
        </div>
    </details>
</div>