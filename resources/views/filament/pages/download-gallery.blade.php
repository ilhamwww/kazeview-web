<x-filament-panels::page>
    <div
        x-data="galleryManager({
            photos: @js($photosList ?? []),
            contentId: {{ (int) ($contentId ?? 0) }},
            downloadId: {{ (int) ($downloadId ?? 0) }}
        })"
        x-init="init()"
        @keydown.window="onKeyDown($event)"
        class="space-y-5 text-zinc-200"
    >
        {{-- TOP HEADER & CONTROLS --}}
        <div class="p-5 rounded-2xl bg-zinc-900/90 border border-zinc-800 shadow-xl space-y-4">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <a
                            href="{{ url('/admin/content-download-page/' . ($contentId ?? '')) }}"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-zinc-800 hover:bg-zinc-700 text-zinc-300 hover:text-white transition"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            <span>Kembali ke Download Data</span>
                        </a>
                        <span class="text-xs text-zinc-500">•</span>
                        <span class="text-xs font-mono text-zinc-400">Folder: <strong class="text-amber-400">{{ $folderName ?? 'Root' }}</strong></span>
                    </div>

                    <h1 class="text-xl md:text-2xl font-black text-white mt-1.5 flex items-center gap-2">
                        <span>🖼️ {{ $contentTitle }}</span>
                    </h1>
                    <p class="text-xs text-zinc-400 mt-0.5">
                        Mode Sortir Foto Cepat: Swipe atau gunakan tombol panah keyboard untuk memeriksa ketajaman foto & hapus foto blur.
                    </p>
                </div>

                {{-- Action Quick Buttons --}}
                <div class="flex flex-wrap items-center gap-2.5">
                    <button
                        type="button"
                        @click="openLightbox(0)"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl font-bold text-sm bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 text-black shadow-lg shadow-amber-500/20 hover:scale-[1.02] active:scale-[0.98] transition transform"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"></path>
                        </svg>
                        <span>Mulai Sortir (Mode Swipe Fullscreen)</span>
                    </button>
                </div>
            </div>

            {{-- SEARCH & FILTER BAR --}}
            <div class="pt-3 border-t border-zinc-800/80 flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-2.5 flex-1">
                    {{-- Search Input --}}
                    <div class="relative flex-1 min-w-[200px] max-w-md">
                        <input
                            type="text"
                            x-model="search"
                            placeholder="Cari nama file (cth: DSC09605)..."
                            class="w-full pl-9 pr-4 py-2 text-xs rounded-xl bg-zinc-950 border border-zinc-700/80 text-zinc-100 placeholder-zinc-500 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 transition"
                        />
                        <svg class="w-4 h-4 text-zinc-400 absolute left-3 top-2.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>

                    {{-- Subfolder Filter (if available) --}}
                    @if(!empty($folders))
                        <select
                            x-model="folderFilter"
                            class="py-2 px-3 text-xs rounded-xl bg-zinc-950 border border-zinc-700/80 text-zinc-200 focus:outline-none focus:border-amber-500 transition"
                        >
                            <option value="all">Semua Subfolder ({{ count($photosList) }})</option>
                            <option value="root">Root Folder</option>
                            @foreach($folders as $f)
                                <option value="{{ $f }}">{{ $f }}</option>
                            @endforeach
                        </select>
                    @endif

                    {{-- Urutan tampil: rating (default) atau nama file --}}
                    <select
                        x-model="sortMode"
                        class="py-2 px-3 text-xs rounded-xl bg-zinc-950 border border-zinc-700/80 text-zinc-200 focus:outline-none focus:border-amber-500 transition"
                        title="Urutkan foto"
                    >
                        <option value="rating_asc">Urut: Rating terendah</option>
                        <option value="rating">Urut: Rating tertinggi</option>
                        <option value="name">Urut: Nama file</option>
                    </select>

                    {{-- Quick Filter: Select All / Deselect --}}
                    <div class="flex items-center gap-1.5 text-xs text-zinc-400">
                        <button
                            type="button"
                            @click="toggleSelectAll()"
                            class="px-2.5 py-1.5 rounded-lg bg-zinc-800 hover:bg-zinc-700 text-zinc-300 transition"
                            x-text="selectedIds.length === filteredPhotos.length && filteredPhotos.length > 0 ? 'Batal Pilih Semua' : 'Pilih Semua'"
                        ></button>
                    </div>
                </div>

                {{-- Counter Badge --}}
                <div class="flex items-center gap-2 text-xs font-mono text-zinc-400 self-end md:self-auto">
                    @if(($unratedCount ?? 0) > 0)
                        <button
                            type="button"
                            wire:click="rateMissingPhotos"
                            wire:loading.attr="disabled"
                            wire:target="rateMissingPhotos"
                            class="px-3 py-1.5 rounded-lg bg-amber-600 hover:bg-amber-500 disabled:opacity-50 text-white text-xs font-semibold transition"
                            title="Hitung rating AI untuk foto yang belum dinilai"
                        >
                            Beri Rating AI ({{ $unratedCount }})
                        </button>
                    @endif
                    <span>Menampilkan:</span>
                    <span class="px-2.5 py-1 rounded-md bg-zinc-950 text-amber-400 border border-zinc-800 font-bold">
                        <span x-text="filteredPhotos.length"></span> / <span x-text="photos.length"></span> Foto
                    </span>
                </div>
            </div>
        </div>

        {{-- GRID OF PHOTOS --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8 gap-3">
            <template x-for="(photo, index) in filteredPhotos" :key="photo.id">
                <div
                    class="group relative rounded-xl overflow-hidden bg-zinc-900 border transition-all duration-200"
                    :class="selectedIds.includes(photo.id) ? 'border-amber-500 ring-2 ring-amber-500/50' : 'border-zinc-800 hover:border-zinc-700'"
                >
                    {{-- Checkbox for bulk delete --}}
                    <div class="absolute top-2 left-2 z-10">
                        <input
                            type="checkbox"
                            :value="photo.id"
                            x-model="selectedIds"
                            class="w-4 h-4 rounded bg-zinc-900/90 border-zinc-700 text-amber-500 focus:ring-amber-500 cursor-pointer shadow-md"
                        />
                    </div>

                    {{-- Quick Action Trash Icon --}}
                    <div class="absolute top-2 right-2 z-10 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button
                            type="button"
                            @click.stop="confirmDeletePhoto(photo)"
                            title="Hapus foto ini"
                            class="p-1.5 rounded-lg bg-rose-600/90 hover:bg-rose-500 text-white shadow-lg transition"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                    </div>

                    {{-- Image Thumbnail Container (Click to open Lightbox at this photo) --}}
                    <div
                        @click="openLightbox(index)"
                        class="aspect-square relative cursor-zoom-in bg-zinc-950 overflow-hidden"
                    >
                        <img
                            :src="photo.grid_thumb"
                            :alt="photo.file_name"
                            loading="lazy"
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                        />
                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex items-end p-2 pointer-events-none">
                            <span class="text-[10px] text-zinc-300 font-mono truncate" x-text="photo.file_name"></span>
                        </div>

                        {{-- Badge rating kualitas AI (fokus & kelengkapan objek) --}}
                        <div class="absolute bottom-1.5 right-1.5 flex items-center gap-1 pointer-events-none">
                            <template x-if="photo.rating_cut">
                                <span class="px-1 py-0.5 rounded bg-zinc-950/85 text-[9px] text-rose-300 border border-rose-500/40" title="Objek terpotong frame">POTONG</span>
                            </template>
                            <template x-if="photo.rating !== null && photo.rating !== undefined">
                                <span
                                    class="px-1.5 py-0.5 rounded-md text-[10px] font-bold shadow-lg"
                                    :class="photo.rating >= 8
                                        ? 'bg-emerald-600 text-white'
                                        : (photo.rating >= 5 ? 'bg-amber-600 text-white' : 'bg-rose-600 text-white')"
                                    :title="'Rating ' + photo.rating + '/10 — fokus ' + (photo.rating_focus ?? '-') + '/10, framing ' + (photo.rating_framing ?? '-') + '/10'"
                                    x-text="'★ ' + photo.rating"
                                ></span>
                            </template>
                            <template x-if="photo.rating === null || photo.rating === undefined">
                                <span class="px-1.5 py-0.5 rounded-md text-[10px] font-bold bg-zinc-900/85 text-zinc-500 border border-zinc-700/60" title="Belum dinilai AI">–</span>
                            </template>
                        </div>
                    </div>

                    {{-- Card Bottom Info --}}
                    <div class="p-2 text-[11px] bg-zinc-900/95 flex items-center justify-between border-t border-zinc-800/80">
                        <span class="font-mono text-zinc-400 font-bold" x-text="'#' + (index + 1)"></span>
                        <span class="text-[10px] text-zinc-500 truncate max-w-[85px]" x-text="photo.file_size || ''"></span>
                    </div>
                </div>
            </template>
        </div>

        {{-- EMPTY STATE --}}
        <div
            x-show="filteredPhotos.length === 0"
            class="p-12 text-center rounded-2xl bg-zinc-900/50 border border-zinc-800"
        >
            <p class="text-zinc-400 text-sm">Tidak ada foto yang cocok dengan pencarian atau filter.</p>
        </div>

        {{-- FLOATING BULK DELETE BAR --}}
        <div
            x-show="selectedIds.length > 0"
            x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="translate-y-10 opacity-0"
            x-transition:enter-end="translate-y-0 opacity-100"
            x-transition:leave="transition ease-in duration-200 transform"
            x-transition:leave-start="translate-y-0 opacity-100"
            x-transition:leave-end="translate-y-10 opacity-0"
            class="fixed bottom-6 inset-x-0 mx-auto max-w-lg z-40 px-4"
        >
            <div class="p-4 rounded-2xl bg-zinc-900/95 border border-zinc-700 shadow-2xl backdrop-blur-md flex items-center justify-between gap-4">
                <div class="flex items-center gap-2">
                    <span class="flex h-3 w-3 relative">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-rose-500"></span>
                    </span>
                    <span class="text-sm font-bold text-white">
                        <span x-text="selectedIds.length"></span> foto dipilih
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="selectedIds = []"
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-zinc-800 hover:bg-zinc-700 text-zinc-300 transition"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="confirmBulkDelete()"
                        class="px-4 py-1.5 rounded-lg text-xs font-bold bg-rose-600 hover:bg-rose-500 text-white shadow-lg transition flex items-center gap-1.5"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        <span>Hapus Terpilih</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- =============================================================== --}}
        {{-- FULLSCREEN SWIPE & SORTIR LIGHTBOX (CORE WORKFLOW)              --}}
        {{-- =============================================================== --}}
        <div
            x-show="lightboxOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 bg-black/95 flex flex-col select-none touch-none"
            style="display: none;"
            @touchstart="onTouchStart($event)"
            @touchend="onTouchEnd($event)"
        >
            {{-- LIGHTBOX TOP NAVIGATION BAR --}}
            <div class="px-4 py-3 bg-zinc-950/90 border-b border-zinc-800/80 flex items-center justify-between gap-3 shrink-0">
                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        @click="closeLightbox()"
                        class="p-2 rounded-xl bg-zinc-900 hover:bg-zinc-800 text-zinc-300 hover:text-white border border-zinc-800 transition flex items-center gap-1.5 text-xs font-semibold"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        <span class="hidden sm:inline">Tutup (Esc)</span>
                    </button>

                    <div class="text-xs">
                        <span class="font-mono font-bold text-amber-400" x-text="currentPhoto ? '#' + (currentIndex + 1) + ' / ' + filteredPhotos.length : ''"></span>
                        <span class="text-zinc-500 mx-1.5">•</span>
                        <span class="font-mono text-zinc-200 font-semibold truncate max-w-[150px] sm:max-w-xs inline-block align-bottom" x-text="currentPhoto ? currentPhoto.file_name : ''"></span>
                    </div>
                </div>

                {{-- Lightbox Actions (Detail, Zoom & Delete) --}}
                <div class="flex items-center gap-2">
                    {{-- Detail Index Button --}}
                    <button
                        type="button"
                        @click="openIndexDetail()"
                        title="Lihat detail index AI foto ini (I)"
                        :disabled="indexDetailLoading"
                        class="p-2 rounded-xl border transition text-xs font-semibold flex items-center gap-1.5 bg-zinc-900 text-zinc-300 hover:text-white border-zinc-800 disabled:opacity-50"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span class="hidden sm:inline" x-text="indexDetailLoading ? 'Memuat…' : 'Detail Index'"></span>
                    </button>
                    {{-- AI Metadata Pill (if available) --}}
                    <template x-if="currentPhoto && currentPhoto.ai_summary">
                        <span class="hidden md:inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs bg-zinc-900 border border-zinc-800 text-emerald-400 font-mono">
                            <span x-text="currentPhoto.ai_summary"></span>
                        </span>
                    </template>

                    {{-- Zoom Toggle Button --}}
                    <button
                        type="button"
                        @click="toggleZoom()"
                        :title="zoomMode ? 'Fit Screen' : 'Zoom 100% (Z)'"
                        class="p-2 rounded-xl border transition text-xs font-semibold flex items-center gap-1.5"
                        :class="zoomMode ? 'bg-amber-500 text-black border-amber-400' : 'bg-zinc-900 text-zinc-300 hover:text-white border-zinc-800'"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7"></path>
                        </svg>
                        <span class="hidden sm:inline" x-text="zoomMode ? 'Fit Layar' : 'Zoom 100%'"></span>
                    </button>

                    {{-- HAPUS FOTO: ikon saja --}}
                    <button
                        type="button"
                        @click="confirmDeleteCurrentPhoto()"
                        title="Hapus foto ini (Del)"
                        aria-label="Hapus foto ini"
                        class="p-2 rounded-xl bg-rose-600 hover:bg-rose-500 active:bg-rose-700 text-white shadow-lg shadow-rose-600/30 flex items-center justify-center transition transform active:scale-95"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </button>
                </div>
            </div>

            {{-- LIGHTBOX MAIN STAGE (SWIPE & VIEW) --}}
            <div class="relative flex-1 flex items-center justify-center p-2 sm:p-4 overflow-hidden">
                {{-- Previous Chevron Button --}}
                <button
                    type="button"
                    @click.stop="prevPhoto()"
                    title="Foto Sebelumnya (Panah Kiri)"
                    class="absolute left-3 top-1/2 -translate-y-1/2 z-20 w-12 h-14 rounded-2xl bg-zinc-900/80 hover:bg-zinc-800 text-white border border-zinc-700/60 shadow-2xl flex items-center justify-center hover:scale-105 active:scale-95 transition"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </button>

                {{-- Next Chevron Button --}}
                <button
                    type="button"
                    @click.stop="nextPhoto()"
                    title="Foto Selanjutnya (Panah Kanan / Spasi)"
                    class="absolute right-3 top-1/2 -translate-y-1/2 z-20 w-12 h-14 rounded-2xl bg-zinc-900/80 hover:bg-zinc-800 text-white border border-zinc-700/60 shadow-2xl flex items-center justify-center hover:scale-105 active:scale-95 transition"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path>
                    </svg>
                </button>

                {{-- The Image Viewer Stage --}}
                <div
                    class="w-full h-full flex items-center justify-center"
                    :class="zoomMode ? 'overflow-auto cursor-grab' : 'overflow-hidden'"
                >
                    {{-- Satu <img> tetap; src dikontrol JS: placeholder instan -> preview 1600px -> original saat zoom --}}
                    <img
                        x-show="currentPhoto"
                        :src="displaySrc"
                        :alt="currentPhoto ? currentPhoto.file_name : ''"
                        class="transition-[filter] duration-200"
                        :class="[
                            zoomMode ? 'max-w-none w-auto h-auto' : 'max-h-full max-w-full object-contain pointer-events-none',
                            imageLoading ? 'blur-[2px] brightness-90' : ''
                        ]"
                        draggable="false"
                    />

                    {{-- Indikator loading --}}
                    <div
                        x-show="imageLoading"
                        class="absolute top-4 left-1/2 -translate-x-1/2 z-20 px-3 py-1.5 rounded-full bg-black/70 border border-zinc-700 text-[11px] font-mono text-amber-400 flex items-center gap-2"
                        role="status"
                        aria-live="polite"
                    >
                        <span class="inline-block w-3 h-3 border-2 border-amber-400 border-t-transparent rounded-full animate-spin"></span>
                        <span x-text="zoomMode ? 'Memuat resolusi asli…' : 'Memuat foto…'"></span>
                    </div>
                </div>
            </div>

            {{-- BOTTOM FILMSTRIP & SHORTCUTS FOOTER --}}
            <div class="px-4 py-2.5 bg-zinc-950/95 border-t border-zinc-800/80 shrink-0 space-y-2">
                {{-- Mini Filmstrip --}}
                <div
                    class="flex items-center gap-1.5 overflow-x-auto py-1 scrollbar-thin scrollbar-thumb-zinc-700 scrollbar-track-transparent"
                    x-ref="filmstrip"
                >
                    <template x-for="(p, idx) in filteredPhotos" :key="p.id">
                        <button
                            type="button"
                            @click="openLightbox(idx)"
                            class="shrink-0 w-14 h-14 rounded-lg overflow-hidden border-2 transition-all relative"
                            :class="idx === currentIndex ? 'border-amber-500 scale-105 ring-2 ring-amber-500/50' : 'border-zinc-800 opacity-50 hover:opacity-100'"
                        >
                            <img :src="p.grid_thumb" loading="lazy" decoding="async" class="w-full h-full object-cover pointer-events-none" />
                        </button>
                    </template>
                </div>

                {{-- Keyboard Shortcuts Info --}}
                <div class="flex items-center justify-between text-[11px] text-zinc-500 font-mono">
                    <div class="hidden sm:flex items-center gap-3">
                        <span><kbd class="px-1.5 py-0.5 rounded bg-zinc-900 border border-zinc-800 text-zinc-300">←</kbd> <kbd class="px-1.5 py-0.5 rounded bg-zinc-900 border border-zinc-800 text-zinc-300">→</kbd> Geser Foto</span>
                        <span><kbd class="px-1.5 py-0.5 rounded bg-zinc-900 border border-zinc-800 text-zinc-300">Z</kbd> Zoom 100%</span>
                        <span><kbd class="px-1.5 py-0.5 rounded bg-zinc-900 border border-zinc-800 text-zinc-300">Del</kbd> Hapus Foto</span>
                        <span><kbd class="px-1.5 py-0.5 rounded bg-zinc-900 border border-zinc-800 text-zinc-300">Esc</kbd> Tutup</span>
                    </div>
                    <div class="ml-auto text-zinc-400">
                        <span x-text="currentPhoto ? (currentPhoto.folder !== 'Root' ? currentPhoto.folder + ' • ' : '') + (currentPhoto.file_size || '') : ''"></span>
                    </div>
                </div>
            </div>
            {{-- PANEL DETAIL INDEX AI (dipanggil dari lightbox) --}}
            <div
                x-show="indexDetailOpen"
                x-transition.opacity
                class="absolute inset-0 z-30 bg-black/85 flex items-end sm:items-center justify-center p-0 sm:p-6"
                style="display: none;"
                @click.self="closeIndexDetail()"
            >
                <div class="w-full sm:max-w-2xl bg-zinc-900 border border-zinc-700 rounded-t-2xl sm:rounded-2xl shadow-2xl overflow-y-auto" style="max-height: 85vh;">
                    <div class="sticky top-0 flex items-center justify-between gap-3 px-4 py-3 bg-zinc-900 border-b border-zinc-800">
                        <div class="min-w-0">
                            <h3 class="font-bold text-sm text-zinc-100">Detail Index AI</h3>
                            <p class="text-[11px] text-zinc-500 font-mono truncate" x-text="indexDetail ? indexDetail.file_name : currentPhoto ? currentPhoto.file_name : ''"></p>
                        </div>
                        <button
                            type="button"
                            @click="closeIndexDetail()"
                            class="px-3 py-1.5 rounded-lg bg-zinc-800 hover:bg-zinc-700 text-zinc-200 text-xs font-semibold shrink-0"
                        >Tutup</button>
                    </div>

                    <div class="p-4 space-y-3 text-xs">
                        <template x-if="!indexDetail">
                            <p class="text-zinc-400 py-6 text-center">Memuat detail…</p>
                        </template>

                        <template x-if="indexDetail">
                            <div class="space-y-3">
                                {{-- Rating kualitas --}}
                                <div class="rounded-xl bg-zinc-950 border border-zinc-800 p-3">
                                    <div class="flex items-center justify-between">
                                        <span class="text-zinc-400">Rating kualitas</span>
                                        <span
                                            class="font-bold text-base"
                                            :class="(indexDetail.rating ?? 0) >= 8 ? 'text-emerald-400' : ((indexDetail.rating ?? 0) >= 5 ? 'text-amber-400' : 'text-rose-400')"
                                            x-text="(indexDetail.rating !== null && indexDetail.rating !== undefined ? indexDetail.rating : '–') + ' / 10'"
                                        ></span>
                                    </div>
                                    <div class="mt-2 grid grid-cols-2 gap-2 text-[11px] text-zinc-300">
                                        <div>Fokus: <span class="font-semibold text-zinc-100" x-text="indexDetail.focus ?? '–'"></span>/10</div>
                                        <div>Framing: <span class="font-semibold text-zinc-100" x-text="indexDetail.framing ?? '–'"></span>/10</div>
                                        <div>Potongan objek: <span class="font-semibold text-zinc-100" x-text="indexDetail.crop_severity === 'major' ? 'Mayor' : (indexDetail.crop_severity === 'minor' ? 'Minor (tepi)' : (indexDetail.crop_severity === 'none' ? 'Tidak' : '–'))"></span></div>
                                        <div>Status: <span class="font-semibold text-zinc-100 uppercase" x-text="indexDetail.status ?? '–'"></span></div>
                                    </div>
                                    <template x-if="indexDetail.quality_note">
                                        <p class="mt-2 text-[11px] text-zinc-400 italic" x-text="indexDetail.quality_note"></p>
                                    </template>
                                </div>

                                {{-- Identitas motor --}}
                                <div class="rounded-xl bg-zinc-950 border border-zinc-800 p-3 space-y-1 text-[11px] text-zinc-300">
                                    <div class="text-zinc-400 mb-1">Identitas motor</div>
                                    <div>Merek: <span class="font-semibold text-zinc-100" x-text="indexDetail.brand ?? 'unknown'"></span></div>
                                    <div>Model: <span class="font-semibold text-zinc-100" x-text="indexDetail.model ?? 'unknown'"></span></div>
                                    <div>Kategori: <span class="font-semibold text-zinc-100" x-text="indexDetail.category ?? 'unknown'"></span></div>
                                    <div>Warna utama: <span class="font-semibold text-zinc-100" x-text="indexDetail.primary_color ?? 'unknown'"></span></div>
                                    <div>Plat: <span class="font-mono font-semibold text-zinc-100" x-text="indexDetail.license_plate ?? 'unknown'"></span></div>
                                    <template x-if="indexDetail.rider_helmet">
                                        <div>Helm: <span class="text-zinc-100" x-text="indexDetail.rider_helmet"></span></div>
                                    </template>
                                </div>

                                {{-- Metadata index --}}
                                <div class="rounded-xl bg-zinc-950 border border-zinc-800 p-3 space-y-1 font-mono text-[11px] text-zinc-300">
                                    <div class="text-zinc-400 font-sans mb-1">Metadata index</div>
                                    <div>vision: <span x-text="indexDetail.vision_model ?? '–'"></span></div>
                                    <div>embedding: <span x-text="indexDetail.embedding_model ?? '–'"></span></div>
                                    <div>prompt: <span x-text="indexDetail.prompt_version ?? '–'"></span></div>
                                    <div>dimensi vektor: <span x-text="indexDetail.dimensions ?? '–'"></span></div>
                                    <div>dinilai: <span x-text="indexDetail.quality_rated_at ?? '–'"></span></div>
                                    <div>diindex: <span x-text="indexDetail.indexed_at ?? '–'"></span></div>
                                </div>

                                <template x-if="indexDetail.error_message">
                                    <div class="rounded-xl bg-rose-950 border border-rose-700 p-3 text-[11px] text-rose-200">
                                        <span class="font-bold">Error:</span> <span x-text="indexDetail.error_message"></span>
                                    </div>
                                </template>

                                {{-- Descriptor mentah --}}
                                <details class="rounded-xl bg-zinc-950 border border-zinc-800 p-3">
                                    <summary class="cursor-pointer text-zinc-400 select-none">Descriptor mentah (JSON)</summary>
                                    <pre class="mt-2 text-[10px] leading-relaxed text-zinc-400 whitespace-pre-wrap break-words" x-text="JSON.stringify(indexDetail.descriptor ?? {}, null, 2)"></pre>
                                </details>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        {{-- CONFIRMATION DIALOG MODAL (ALPINE POPUP) --}}
        <div
            x-show="confirmDialog.open"
            x-transition.opacity
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
            style="display: none;"
        >
            <div
                @click.away="confirmDialog.open = false"
                class="w-full max-w-md p-6 rounded-2xl bg-zinc-900 border border-zinc-700 shadow-2xl text-zinc-100 space-y-4"
            >
                <div class="flex items-center gap-3 text-rose-500">
                    <div class="p-3 rounded-full bg-rose-500/15">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-base text-white" x-text="confirmDialog.title"></h3>
                        <p class="text-xs text-zinc-400 mt-0.5" x-text="confirmDialog.message"></p>
                    </div>
                </div>

                <div class="pt-2 flex justify-end items-center gap-2.5">
                    <button
                        type="button"
                        @click="confirmDialog.open = false"
                        class="px-4 py-2 rounded-xl text-xs font-semibold bg-zinc-800 hover:bg-zinc-700 text-zinc-300 transition"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="confirmDialog.action()"
                        class="px-4 py-2 rounded-xl text-xs font-bold bg-rose-600 hover:bg-rose-500 text-white shadow-lg transition"
                    >
                        Ya, Hapus Permanen
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- SCRIPT ALPINE GALLERY LOGIC --}}
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('galleryManager', (config) => ({
                photos: config.photos || [],
                contentId: config.contentId,
                downloadId: config.downloadId,
                currentIndex: 0,
                lightboxOpen: false,
                zoomMode: false,
                search: '',
                folderFilter: 'all',
                sortMode: 'rating_asc',
                selectedIds: [],
                touchStartX: 0,
                touchStartY: 0,
                displaySrc: '',
                imageLoading: false,
                indexDetailOpen: false,
                indexDetailLoading: false,
                indexDetail: null,
                loadToken: 0,
                loadedUrls: new Set(),
                confirmDialog: {
                    open: false,
                    title: '',
                    message: '',
                    action: () => {}
                },

                init() {
                    this.$watch('currentIndex', () => {
                        this.loadCurrent();
                        this.scrollFilmstrip();
                        this.preloadSurroundings();
                    });
                    this.$watch('zoomMode', () => this.loadCurrent());
                },

                // URL target: preview 1600px untuk sortir, original hanya saat zoom 100%
                targetUrl(photo) {
                    if (!photo) return '';
                    if (this.zoomMode) return photo.original_url || photo.preview_url || photo.grid_thumb;
                    return photo.preview_url || photo.original_url || photo.grid_thumb;
                },

                // Pre-download + decode; resolve true kalau sukses
                fetchImage(url) {
                    if (!url) return Promise.resolve(false);
                    if (this.loadedUrls.has(url)) return Promise.resolve(true);
                    return new Promise((resolve) => {
                        const img = new Image();
                        img.decoding = 'async';
                        img.onload = () => {
                            const done = () => { this.loadedUrls.add(url); resolve(true); };
                            img.decode ? img.decode().then(done, done) : done();
                        };
                        img.onerror = () => resolve(false);
                        img.src = url;
                    });
                },

                async loadCurrent() {
                    const photo = this.currentPhoto;
                    const token = ++this.loadToken;
                    if (!photo) { this.displaySrc = ''; this.imageLoading = false; return; }

                    const target = this.targetUrl(photo);
                    if (this.loadedUrls.has(target)) {
                        this.displaySrc = target;
                        this.imageLoading = false;
                        return;
                    }

                    // Placeholder instan: thumbnail tanpa crop (rasio asli), bukan
                    // kotak 1:1 hasil crop grid, supaya foto potret tidak tampak square.
                    if (!this.zoomMode) this.displaySrc = photo.fit_thumb || photo.grid_thumb;
                    this.imageLoading = true;

                    const ok = await this.fetchImage(target);
                    // Abaikan hasil kalau user sudah pindah foto (anti race condition)
                    if (token !== this.loadToken) return;
                    if (ok) this.displaySrc = target;
                    this.imageLoading = false;
                },

                get filteredPhotos() {
                    const q = this.search.trim().toLowerCase();
                    const list = this.photos.filter((p) => {
                        const matchSearch = !q || p.file_name.toLowerCase().includes(q);
                        const matchFolder = this.folderFilter === 'all' ||
                            (this.folderFilter === 'root' ? p.folder === 'Root' : p.folder === this.folderFilter);
                        return matchSearch && matchFolder;
                    });

                    // Urutan nama = urutan asli dari server (nomor file).
                    if (this.sortMode === 'name') {
                        return list;
                    }

                    const score = (p) => (p.rating === null || p.rating === undefined) ? null : Number(p.rating);
                    const sorted = [...list];

                    sorted.sort((a, b) => {
                        const ra = score(a);
                        const rb = score(b);

                        // Foto yang belum dinilai selalu di belakang.
                        if (ra === null && rb === null) return 0;
                        if (ra === null) return 1;
                        if (rb === null) return -1;

                        return this.sortMode === 'rating_asc' ? ra - rb : rb - ra;
                    });

                    return sorted;
                },

                get currentPhoto() {
                    return this.filteredPhotos[this.currentIndex] || null;
                },

                openLightbox(index) {
                    if (this.filteredPhotos.length === 0) return;
                    this.currentIndex = Math.max(0, Math.min(index, this.filteredPhotos.length - 1));
                    this.lightboxOpen = true;
                    this.zoomMode = false;
                    document.body.style.overflow = 'hidden';
                    // Watcher tidak jalan kalau index sama, jadi panggil manual
                    this.loadCurrent();
                    this.preloadSurroundings();
                    this.$nextTick(() => this.scrollFilmstrip());
                },

                closeLightbox() {
                    this.lightboxOpen = false;
                    this.zoomMode = false;
                    document.body.style.overflow = '';
                },

                nextPhoto() {
                    if (this.filteredPhotos.length <= 1) return;
                    this.zoomMode = false;
                    if (this.currentIndex < this.filteredPhotos.length - 1) {
                        this.currentIndex++;
                    } else {
                        this.currentIndex = 0; // Wrap around to start
                    }
                },

                prevPhoto() {
                    if (this.filteredPhotos.length <= 1) return;
                    this.zoomMode = false;
                    if (this.currentIndex > 0) {
                        this.currentIndex--;
                    } else {
                        this.currentIndex = this.filteredPhotos.length - 1; // Wrap around to end
                    }
                },

                toggleZoom() {
                    this.zoomMode = !this.zoomMode;
                },

                // Preload 2 foto berikutnya + 1 sebelumnya (versi preview, bukan original)
                preloadSurroundings() {
                    const photos = this.filteredPhotos;
                    const n = photos.length;
                    if (n <= 1) return;
                    [1, 2, -1].forEach((offset) => {
                        const p = photos[(this.currentIndex + offset + n) % n];
                        const url = p?.preview_url || p?.original_url;
                        if (url && !this.loadedUrls.has(url)) this.fetchImage(url);
                    });
                },

                scrollFilmstrip() {
                    if (!this.$refs.filmstrip) return;
                    const activeThumb = this.$refs.filmstrip.children[this.currentIndex];
                    if (activeThumb) {
                        activeThumb.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
                    }
                },

                onTouchStart(e) {
                    if (this.zoomMode) return;
                    this.touchStartX = e.changedTouches[0].screenX;
                    this.touchStartY = e.changedTouches[0].screenY;
                },

                onTouchEnd(e) {
                    if (this.zoomMode) return;
                    const diffX = e.changedTouches[0].screenX - this.touchStartX;
                    const diffY = e.changedTouches[0].screenY - this.touchStartY;
                    if (Math.abs(diffX) > 40 && Math.abs(diffX) > Math.abs(diffY)) {
                        if (diffX < 0) {
                            this.nextPhoto(); // Swipe kiri -> Next
                        } else {
                            this.prevPhoto(); // Swipe kanan -> Prev
                        }
                    }
                },

                onKeyDown(e) {
                    if (!this.lightboxOpen) return;
                    if (this.confirmDialog.open) {
                        if (e.key === 'Escape') this.confirmDialog.open = false;
                        return;
                    }

                    // Panel detail index menahan navigasi foto selama terbuka.
                    if (this.indexDetailOpen) {
                        if (e.key === 'Escape' || e.key === 'i' || e.key === 'I') {
                            e.preventDefault();
                            this.closeIndexDetail();
                        }
                        return;
                    }

                    if (e.key === 'ArrowRight' || e.key === ' ' || e.key === 'PageDown') {
                        e.preventDefault();
                        this.nextPhoto();
                    } else if (e.key === 'ArrowLeft' || e.key === 'PageUp') {
                        e.preventDefault();
                        this.prevPhoto();
                    } else if (e.key === 'Escape') {
                        this.closeLightbox();
                    } else if (e.key === 'z' || e.key === 'Z') {
                        this.toggleZoom();
                    } else if (e.key === 'i' || e.key === 'I') {
                        e.preventDefault();
                        this.openIndexDetail();
                    } else if (e.key === 'Delete' || e.key === 'Backspace') {
                        e.preventDefault();
                        this.confirmDeleteCurrentPhoto();
                    }
                },

                openIndexDetail() {
                    const photo = this.currentPhoto;
                    if (!photo || this.indexDetailLoading) return;

                    this.indexDetailLoading = true;
                    this.indexDetail = null;

                    Promise.resolve(this.$wire.photoIndexDetail(photo.id))
                        .then((data) => {
                            this.indexDetail = data;
                            this.indexDetailOpen = true;
                        })
                        .catch(() => { /* diamkan, tombol kembali aktif */ })
                        .finally(() => { this.indexDetailLoading = false; });
                },

                closeIndexDetail() {
                    this.indexDetailOpen = false;
                },

                toggleSelectAll() {
                    const allFilteredIds = this.filteredPhotos.map((p) => p.id);
                    if (this.selectedIds.length === allFilteredIds.length) {
                        this.selectedIds = [];
                    } else {
                        this.selectedIds = allFilteredIds;
                    }
                },

                confirmDeletePhoto(photo) {
                    this.confirmDialog = {
                        open: true,
                        title: 'Hapus Foto Ini?',
                        message: `File ${photo.file_name} akan dihapus permanen dari storage dan database.`,
                        action: async () => {
                            this.confirmDialog.open = false;
                            await this.executeDelete(photo.id);
                        }
                    };
                },

                confirmDeleteCurrentPhoto() {
                    const photo = this.currentPhoto;
                    if (!photo) return;
                    this.confirmDialog = {
                        open: true,
                        title: 'Hapus Foto Terpilih?',
                        message: `Hapus file ${photo.file_name} sekarang? Cocok untuk membersihkan foto blur.`,
                        action: async () => {
                            this.confirmDialog.open = false;
                            await this.executeDelete(photo.id);
                        }
                    };
                },

                async executeDelete(photoId) {
                    const idx = this.photos.findIndex((p) => p.id === photoId);
                    if (idx === -1) return;

                    // Hapus via Livewire
                    const success = await this.$wire.deletePhoto(photoId);
                    if (success) {
                        this.photos.splice(idx, 1);
                        this.selectedIds = this.selectedIds.filter((id) => id !== photoId);

                        if (this.filteredPhotos.length === 0) {
                            this.closeLightbox();
                        } else if (this.currentIndex >= this.filteredPhotos.length) {
                            this.currentIndex = this.filteredPhotos.length - 1;
                        }
                        // Index bisa tetap sama tapi fotonya sudah berganti
                        this.loadCurrent();
                        this.preloadSurroundings();
                    }
                },

                confirmBulkDelete() {
                    const count = this.selectedIds.length;
                    if (count === 0) return;
                    this.confirmDialog = {
                        open: true,
                        title: `Hapus ${count} Foto Sekaligus?`,
                        message: `Seluruh ${count} file foto terpilih akan dihapus permanen dari storage dan database.`,
                        action: async () => {
                            this.confirmDialog.open = false;
                            const idsToDelete = [...this.selectedIds];
                            const deletedCount = await this.$wire.bulkDeletePhotos(idsToDelete);
                            if (deletedCount > 0) {
                                this.photos = this.photos.filter((p) => !idsToDelete.includes(p.id));
                                this.selectedIds = [];
                                if (this.filteredPhotos.length === 0) {
                                    this.closeLightbox();
                                } else if (this.currentIndex >= this.filteredPhotos.length) {
                                    this.currentIndex = Math.max(0, this.filteredPhotos.length - 1);
                                }
                                if (this.lightboxOpen) this.loadCurrent();
                            }
                        }
                    };
                }
            }));
        });
    </script>
</x-filament-panels::page>
