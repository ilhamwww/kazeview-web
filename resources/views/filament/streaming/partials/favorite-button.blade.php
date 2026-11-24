@php $favActive = (bool) ($isFavorited ?? false); @endphp
<button wire:click="toggleFavorite" type="button" wire:loading.attr="disabled"
        class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-lg font-bold text-sm transition border disabled:opacity-60 {{ $favActive ? 'bg-red-600/15 border-red-600/50 text-red-400' : 'bg-zinc-500/30 hover:bg-zinc-500/50 backdrop-blur-md border-white/10 text-white' }} {{ $favWidth ?? '' }}">
    <svg class="w-5 h-5 {{ $favActive ? 'fill-current' : 'fill-none stroke-current' }}" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
    </svg>
    <span>{{ $favActive ? 'Tersimpan' : 'Favorit' }}</span>
</button>
