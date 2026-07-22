<x-filament::page>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div class="p-6 bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-100 dark:border-gray-800 flex flex-col justify-between">
            <div>
                <div class="flex items-center gap-3 mb-4">
                    <div class="p-3 bg-danger-500/10 text-danger-500 rounded-lg">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">Streaming Platform</h2>
                </div>
                <p class="text-gray-600 dark:text-gray-400 mb-6">Akses dan tonton platform streaming film, serial TV, dan anime favorit Anda secara langsung.</p>
            </div>
            <div>
                <a href="{{ url('/streaming') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-amber-500 hover:bg-amber-600 dark:bg-amber-600 dark:hover:bg-amber-700 text-white font-semibold rounded-lg shadow transition-colors">
                    <span>Buka Streaming</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                    </svg>
                </a>
            </div>
        </div>
    </div>
</x-filament::page>