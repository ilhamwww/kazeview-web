<x-filament::page>
    <form wire:submit.prevent="save">
        {{ $this->form }}

        <x-filament::button type="submit" style="margin-top: 1.5rem;">
            Simpan Perubahan
        </x-filament::button>
    </form>
</x-filament::page>
