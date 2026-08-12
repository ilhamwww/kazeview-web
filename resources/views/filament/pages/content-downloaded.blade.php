<x-filament-panels::page>
    <div class="flex justify-end items-center gap-3">
        <x-filament::button id="refreshBtn" color="success" icon="heroicon-o-arrow-path"
            class="px-5 py-2 rounded-xl shadow-md hover:scale-105 transition transform duration-200"
            onclick="location.reload()">
            Refresh Halaman
        </x-filament::button>

    </div>


    {{ $this->table }}

</x-filament-panels::page>
