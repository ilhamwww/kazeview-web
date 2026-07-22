<x-filament::page>
    <form wire:submit.prevent="save" class="space-y-6">
        {{ $this->form }}

        <div class="flex items-center gap-3">
            <x-filament::button type="submit" color="primary">
                Save changes
            </x-filament::button>
            
            <x-filament::button type="button" color="gray" tag="a" href="{{ filament()->getUrl() }}">
                Cancel
            </x-filament::button>
        </div>
    </form>
</x-filament::page>