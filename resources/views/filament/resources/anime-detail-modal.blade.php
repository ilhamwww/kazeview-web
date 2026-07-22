<div class="space-y-4" x-data="{ selectedEpisode: null, videoUrl: '', loadingVideo: false }">
    {{-- Anime Info --}}
    <div class="flex flex-col sm:flex-row gap-4">
        @if($anime->thumbnail)
            <div class="flex-shrink-0 self-center sm:self-start">
                <img src="{{ $anime->thumbnail }}" alt="{{ $anime->title }}" class="w-32 h-44 object-cover rounded-lg shadow">
            </div>
        @endif
        <div class="flex-1 space-y-2 min-w-0">
            <div>
                <span class="text-sm text-gray-500">Judul:</span>
                <p class="font-semibold text-lg break-words">{{ $detail['title'] ?? $anime->title }}</p>
            </div>
            <div class="flex flex-wrap gap-x-4 gap-y-1 text-sm">
                @if($anime->type)
                    <div>
                        <span class="text-gray-500">Tipe:</span>
                        <span class="font-medium">{{ $anime->type }}</span>
                    </div>
                @endif
                @if($anime->status)
                    <div>
                        <span class="text-gray-500">Status:</span>
                        <span class="font-medium">{{ $anime->status }}</span>
                    </div>
                @endif
                @if(!empty($detail['episodes']))
                    <div>
                        <span class="text-gray-500">Total Episode:</span>
                        <span class="font-medium">{{ count($detail['episodes']) }}</span>
                    </div>
                @endif
            </div>
            @if(!empty($detail['genres']))
                <div>
                    <span class="text-sm text-gray-500">Genre:</span>
                    <div class="flex flex-wrap gap-1 mt-1">
                        @foreach($detail['genres'] as $genre)
                            <span class="inline-block bg-gray-200 dark:bg-gray-700 text-white-700 dark:text-white-300 text-xs px-2 py-0.5 rounded">{{ $genre }}</span>
                        @endforeach
                    </div>
                </div>
            @endif
            <div>
                <span class="text-sm text-gray-500">Link Sumber:</span>
                <a href="{{ $anime->url }}" target="_blank" class="text-primary-600 hover:underline text-sm break-all">{{ $anime->url }}</a>
            </div>
        </div>
    </div>

    {{-- Description --}}
    @if(!empty($detail['description']))
        <div class="border-t pt-4">
            <h3 class="font-semibold text-lg mb-2">Sinopsis</h3>
            <p class="text-gray-700 dark:text-gray-300 text-sm leading-relaxed">{{ $detail['description'] }}</p>
        </div>
    @endif

    {{-- Video Player --}}
    <div class="border-t pt-4" x-show="videoUrl" x-cloak>
        <h3 class="font-semibold text-lg mb-3 flex items-center gap-2">
            <x-heroicon-o-play-circle class="w-5 h-5" />
            <span x-text="selectedEpisode ? 'Episode ' + selectedEpisode : 'Video Player'"></span>
        </h3>
        <div class="bg-black rounded-lg overflow-hidden shadow-lg" style="aspect-ratio: 16/9;">
            <iframe
                x-bind:src="videoUrl"
                class="w-full h-full"
                frameborder="0"
                allowfullscreen
                allow="autoplay; encrypted-media; picture-in-picture"
                sandbox="allow-scripts allow-same-origin allow-popups allow-forms"
            ></iframe>
        </div>
    </div>

    {{-- Loading indicator --}}
    <div class="border-t pt-4" x-show="loadingVideo" x-cloak>
        <div class="flex items-center justify-center p-8">
            <svg class="animate-spin h-8 w-8 text-primary-600 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-gray-500">Memuat video...</span>
        </div>
    </div>

    {{-- Episode List --}}
    @if(!empty($detail['episodes']))
        <div class="border-t pt-4">
            <h3 class="font-semibold text-lg mb-3 flex items-center gap-2">
                <x-heroicon-o-queue-list class="w-5 h-5" />
                Daftar Episode
            </h3>
            <div class="space-y-1 max-h-96 overflow-y-auto">
                @foreach($detail['episodes'] as $ep)
                    <div
                        class="flex items-center gap-3 p-3 rounded-lg cursor-pointer transition-colors hover:bg-primary-50 dark:hover:bg-gray-700 border border-transparent hover:border-primary-200 dark:hover:border-gray-600"
                        x-bind:class="{ 'bg-primary-100 dark:bg-gray-700 border-primary-300 dark:border-gray-500': selectedEpisode === '{{ $ep['episode'] }}' }"
                        @click="
                            loadingVideo = true;
                            selectedEpisode = '{{ $ep['episode'] }}';
                            videoUrl = '';
                            fetch('{{ route('api.anime.episode-video', ['url' => '']) }}' + encodeURIComponent('{{ $ep['url'] }}'))
                                .then(r => r.json())
                                .then(data => {
                                    videoUrl = data.video_url || '';
                                    loadingVideo = false;
                                })
                                .catch(() => { loadingVideo = false; });
                        "
                    >
                        <div class="flex-shrink-0 w-10 h-10 bg-primary-100 dark:bg-primary-900 text-primary-700 dark:text-primary-300 rounded-full flex items-center justify-center font-bold text-sm">
                            {{ $ep['episode'] }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100 break-words">{{ $ep['title'] }}</p>
                            <p class="text-xs text-gray-500">{{ $ep['date'] }}</p>
                        </div>
                        <div class="flex-shrink-0">
                            <x-heroicon-o-play-circle class="w-6 h-6 text-gray-400 hover:text-primary-600" />
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <div class="border-t pt-4">
            <div class="bg-gray-100 dark:bg-gray-800 rounded-lg p-8 text-center">
                <x-heroicon-o-queue-list class="w-12 h-12 mx-auto text-gray-400 mb-2" />
                <p class="text-gray-500">Daftar episode tidak ditemukan.</p>
                <p class="text-gray-400 text-sm mt-1">Coba kunjungi link sumber langsung.</p>
            </div>
        </div>
    @endif
</div>