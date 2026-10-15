<x-filament-panels::page>
    @php
        $aiStats = $this->getAiStats();
    @endphp

    @if(!empty($aiStats) && $aiStats['enabled'])
        <div class="p-5 rounded-2xl bg-zinc-900 border border-zinc-800 shadow-lg text-zinc-200 space-y-3.5">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                <div class="flex items-start md:items-center gap-3">
                    <div class="flex h-3.5 w-3.5 relative mt-1 md:mt-0">
                        @if($aiStats['is_indexing'])
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-amber-500"></span>
                        @elseif($aiStats['percent'] >= 100)
                            <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.8)]"></span>
                        @else
                            <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-blue-500"></span>
                        @endif
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="font-bold text-sm text-zinc-100 flex items-center gap-1.5">
                                <span>🤖 AI Photo Search Indexing</span>
                            </h3>
                            @if($aiStats['is_indexing'])
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-500/15 text-amber-400 border border-amber-500/30 animate-pulse flex items-center gap-1">
                                    <span>⚡ 3 Worker Aktif</span>
                                </span>
                            @elseif($aiStats['percent'] >= 100)
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-500/15 text-emerald-400 border border-emerald-500/30 flex items-center gap-1">
                                    <span>✓ 100% Selesai</span>
                                </span>
                            @endif
                        </div>
                        <p class="text-xs text-zinc-400 mt-0.5">
                            {{ $aiStats['message'] ?: ($aiStats['percent'] >= 100 ? 'Semua foto berhasil di-index.' : 'Sedang memproses antrean foto...') }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-4 self-end md:self-auto">
                    <div class="text-right">
                        <span class="text-xl font-black {{ $aiStats['percent'] >= 100 ? 'text-emerald-400' : ($aiStats['is_indexing'] ? 'text-amber-400' : 'text-zinc-200') }}">
                            {{ $aiStats['percent'] }}%
                        </span>
                        <span class="block text-xs font-mono text-zinc-400">
                            {{ $aiStats['indexed'] }} / {{ $aiStats['downloaded'] }} foto
                        </span>
                    </div>
                    @if($aiStats['failed'] > 0)
                        <span class="px-2 py-1 rounded-md text-xs font-semibold bg-rose-500/15 text-rose-400 border border-rose-500/30">
                            {{ $aiStats['failed'] }} gagal
                        </span>
                    @endif
                </div>
            </div>

            {{-- Progress Bar --}}
            <div class="w-full bg-zinc-800 rounded-full h-3 overflow-hidden border border-zinc-700/60 shadow-inner">
                <div 
                    class="h-full rounded-full transition-all duration-500 {{ $aiStats['percent'] >= 100 ? 'bg-emerald-500 shadow-[0_0_12px_rgba(16,185,129,0.6)]' : ($aiStats['is_indexing'] ? 'bg-gradient-to-r from-amber-500 to-amber-300 shadow-[0_0_12px_rgba(251,191,36,0.6)] animate-pulse' : 'bg-blue-500') }}" 
                    style="width: {{ $aiStats['percent'] }}%"
                ></div>
            </div>
        </div>
    @endif

    <div class="flex justify-end items-center gap-3">
        <x-filament::button id="refreshBtn" color="success" icon="heroicon-o-arrow-path"
            class="px-5 py-2 rounded-xl shadow-md hover:scale-105 transition transform duration-200"
            onclick="location.reload()">
            Refresh Halaman
        </x-filament::button>
    </div>

    {{ $this->table }}
</x-filament-panels::page>