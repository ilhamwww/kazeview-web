<x-filament-panels::page>
    <x-filament::section
        heading="AI Photo Search per Content"
        description="Aktifkan hanya pada Content foto yang memerlukan pencarian motor. Saat OFF, Download Data dan galeri tetap berjalan seperti biasa."
        icon="fas-circle-info"
        icon-color="info"
    >
        <div class="grid gap-3 text-sm text-gray-600 dark:text-gray-300 md:grid-cols-3">
            <div>
                <span class="font-semibold text-gray-950 dark:text-white">OFF → ON</span>
                <p>Semua foto yang sudah di-download otomatis masuk antrean indexing.</p>
            </div>

            <div>
                <span class="font-semibold text-gray-950 dark:text-white">ON → OFF</span>
                <p>Request AI baru dihentikan. Index lama tetap disimpan agar dapat digunakan kembali.</p>
            </div>

            <div>
                <span class="font-semibold text-gray-950 dark:text-white">Download ulang</span>
                <p>Foto baru atau berubah diproses; file dengan hash dan versi AI yang sama dilewati.</p>
            </div>
        </div>
    </x-filament::section>

    {{ $this->table }}
</x-filament-panels::page>