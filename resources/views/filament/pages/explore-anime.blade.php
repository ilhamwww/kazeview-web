<x-filament-panels::page>
    <style>
        .anime-custom-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
        }
        @media (min-width: 640px) {
            .anime-custom-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }
        @media (min-width: 1024px) {
            .anime-custom-grid {
                grid-template-columns: repeat(5, minmax(0, 1fr));
            }
        }
    </style>

    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between mb-6">
        <div>
            @if(filled($search))
                <h2 class="text-xl font-bold tracking-tight">Hasil Pencarian: "{{ $search }}"</h2>
                <p class="text-sm text-gray-500">Menampilkan hasil pencarian dari situs Anoboy tanpa disimpan ke database.</p>
            @elseif(filled($genre))
                @php
                    $selectedGenreName = collect($genres)->firstWhere('slug', $genre)['name'] ?? $genre;
                @endphp
                <h2 class="text-xl font-bold tracking-tight">Genre: {{ $selectedGenreName }} (Halaman {{ $pageNumber }})</h2>
                <p class="text-sm text-gray-500">Menampilkan daftar anime dalam genre {{ $selectedGenreName }} tanpa disimpan ke database.</p>
            @elseif(filled($status))
                <h2 class="text-xl font-bold tracking-tight">Status: {{ ucfirst($status) }} (Halaman {{ $pageNumber }})</h2>
                <p class="text-sm text-gray-500">Menampilkan daftar anime dengan status {{ $status }} tanpa disimpan ke database.</p>
            @else
                <h2 class="text-xl font-bold tracking-tight">Menampilkan Halaman {{ $pageNumber }}</h2>
                <p class="text-sm text-gray-500">Data diambil langsung dari situs Anoboy tanpa disimpan ke database.</p>
            @endif
        </div>
        
        @if(blank($search))
            <div class="flex items-center gap-2 self-start md:self-auto">
                <x-filament::button 
                    wire:click="previousPage" 
                    color="gray" 
                    :disabled="$pageNumber <= 1"
                    icon="heroicon-m-chevron-left"
                >
                    Sebelumnya
                </x-filament::button>

                <span class="px-4 py-2 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 font-medium">
                    {{ $pageNumber }}
                </span>

                <x-filament::button 
                    wire:click="nextPage"
                    icon="heroicon-m-chevron-right"
                    icon-position="after"
                >
                    Selanjutnya
                </x-filament::button>
            </div>
        @endif
    </div>

    <!-- Search & Filter Bar -->
    <div class="mb-6 flex flex-col sm:flex-row items-center gap-4">
        <!-- Search Input -->
        <div class="w-full sm:max-w-md">
            <x-filament::input.wrapper prefix-icon="heroicon-m-magnifying-glass">
                <x-filament::input
                    type="text"
                    placeholder="Cari anime..."
                    wire:model.live.debounce.500ms="search"
                />
            </x-filament::input.wrapper>
        </div>

        <!-- Genre Dropdown -->
        <div class="w-full sm:max-w-xs">
            <x-filament::input.wrapper>
                <x-filament::input.select wire:model.live="genre">
                    <option value="">Semua Genre</option>
                    @foreach($genres as $g)
                        <option value="{{ $g['slug'] }}">{{ $g['name'] }}</option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>
        </div>

        <!-- Status Dropdown -->
        <div class="w-full sm:max-w-xs">
            <x-filament::input.wrapper>
                <x-filament::input.select wire:model.live="status">
                    <option value="">Default (Semua Status)</option>
                    <option value="ongoing">Ongoing</option>
                    <option value="completed">Completed</option>
                </x-filament::input.select>
            </x-filament::input.wrapper>
        </div>

        @if(filled($search) || filled($genre) || filled($status))
            <x-filament::button wire:click="clearSearch" color="gray" icon="heroicon-m-x-mark" class="w-full sm:w-auto">
                Reset
            </x-filament::button>
        @endif
    </div>

    <!-- Loading Indicator -->
    <div wire:loading wire:target="loadAnimes, nextPage, previousPage" class="w-full">
        <div class="flex items-center justify-center p-12 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
            <svg class="animate-spin h-8 w-8 text-primary-600 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-gray-500 font-medium">Mengambil data dari server...</span>
        </div>
    </div>

    <div wire:loading.remove wire:target="loadAnimes, nextPage, previousPage">
        @if(empty($animes))
            <div class="flex flex-col items-center justify-center p-12 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 text-center">
                <x-heroicon-o-exclamation-triangle class="w-12 h-12 text-warning-500 mb-3" />
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Tidak Ada Data</h3>
                <p class="text-gray-500">Gagal mengambil data atau halaman ini kosong.</p>
            </div>
        @else
            <div class="anime-custom-grid">
                @foreach($animes as $anime)
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm hover:shadow-md transition-shadow flex flex-col">
                        <div class="relative aspect-[3/4] bg-gray-100 dark:bg-gray-900">
                            @if($anime['thumbnail'])
                                <img src="{{ $anime['thumbnail'] }}" alt="{{ $anime['title'] }}" class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-gray-400">
                                    <x-heroicon-o-photo class="w-12 h-12" />
                                </div>
                            @endif
                            
                            <div class="absolute top-2 left-2 flex flex-col gap-1">
                                @if($anime['type'])
                                    <span class="px-2 py-1 text-xs font-bold rounded bg-primary-500 text-white shadow-sm">
                                        {{ $anime['type'] }}
                                    </span>
                                @endif
                            </div>
                        </div>
                        
                        <div class="p-4 flex flex-col flex-1">
                            <h3 class="font-semibold text-sm line-clamp-2 mb-3 text-gray-900 dark:text-white" title="{{ $anime['title'] }}">
                                {{ $anime['title'] }}
                            </h3>
                            
                            <div class="mt-auto flex flex-col gap-2 sm:grid sm:grid-cols-2">
                                <button 
                                    type="button" 
                                    wire:click="mountAction('detailAction', { url: '{{ addslashes($anime['url']) }}', title: '{{ addslashes($anime['title']) }}', thumbnail: '{{ addslashes($anime['thumbnail']) }}', type: '{{ addslashes($anime['type']) }}', status: '{{ addslashes($anime['status']) }}' })"
                                    wire:loading.attr="disabled"
                                    wire:loading.class="opacity-75 pointer-events-none cursor-not-allowed"
                                    wire:target="mountAction"
                                    class="flex items-center justify-center gap-1.5 px-3 py-2 text-sm font-medium text-white bg-primary-600 hover:bg-primary-500 rounded-lg transition-colors w-full"
                                >
                                    <x-heroicon-o-queue-list wire:loading.remove wire:target="mountAction" class="w-4 h-4" />
                                    <svg wire:loading wire:target="mountAction" class="animate-spin -ml-1 mr-1.5 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Detail
                                </button>
                                
                                <a 
                                    href="{{ $anime['url'] }}" 
                                    target="_blank"
                                    class="flex items-center justify-center gap-1.5 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors w-full"
                                >
                                    <x-heroicon-o-arrow-top-right-on-square class="w-4 h-4" />
                                    Kunjungi
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            
            @if(blank($search))
                <div class="flex justify-center items-center gap-4" style="margin-top: 1rem;">
                    <x-filament::button 
                        wire:click="previousPage" 
                        color="gray" 
                        :disabled="$pageNumber <= 1"
                        icon="heroicon-m-chevron-left"
                    >
                        Halaman Sebelumnya
                    </x-filament::button>

                    <x-filament::button 
                        wire:click="nextPage"
                        icon="heroicon-m-chevron-right"
                        icon-position="after"
                    >
                        Halaman Selanjutnya
                    </x-filament::button>
                </div>
            @endif
        @endif
    </div>
    
    <!-- Render Filament Actions inside the page -->
    <x-filament-actions::modals />
</x-filament-panels::page>