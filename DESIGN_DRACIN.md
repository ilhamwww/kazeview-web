# Panduan Desain & Konsistensi UI Dracin Kazeview

Dokumen ini berisi standar panduan visual dan struktur markup HTML/Tailwind CSS yang digunakan pada halaman drama (Dracin & Bstation Networks) di platform Kazeview. Panduan ini dibuat untuk memastikan konsistensi antarmuka pengguna (UI) ketika membuat atau memodifikasi halaman jaringan (network) baru.

---

## 1. Pembungkus Halaman Utama (Main Wrapper)
Seluruh konten halaman dibungkus dengan satu container utama yang sudah memiliki padding dan lebar maksimum. **Tidak ada card/glassmorphism wrapper tambahan** di dalamnya agar konten (terutama grid drama) tidak terlalu sempit.

```html
<div class="min-h-screen max-w-[1800px] pt-28 pb-16 px-4 md:px-8 max-w-7xl mx-auto">
    <!-- Konten Halaman dimasukkan langsung di sini, tanpa card wrapper -->
</div>
```

> **Catatan:** Sebelumnya terdapat div pembungkus bergaya glassmorphism (`bg-zinc-900/40 border border-zinc-800 rounded-3xl p-8 md:p-12 backdrop-blur-md`) yang menambahkan padding ekstra dan membuat area konten menyempit. Pembungkus tersebut telah dihapus agar grid drama memiliki ruang lebih luas.

---

## 2. Struktur Header Jaringan (Network Header)
Header berisi logo jaringan di sebelah kiri dan informasi detail/navigasi tab di sebelah kanan. Gunakan flexbox yang responsif (`flex flex-col md:flex-row`).

### Komponen Header:
- **Logo Box**: Dimensi tetap `w-48 h-48` dengan sudut melengkung `rounded-2xl`, latar belakang hitam (`bg-black`), bergaris batas abu-abu (`border-zinc-800`), dan efek bayangan `shadow-2xl`. Gambar di dalamnya di-render dengan fit `object-contain`.
- **Detail Info**: Berisi badge label `"Network Detail"`, judul utama (`text-4xl md:text-5xl font-extrabold`), deskripsi warna abu-abu (`text-zinc-400`), dan badge ID Network (`bg-zinc-950 border border-zinc-800`).
- **Tab Kustom (Opsional)**: Letakkan tab di sebelah kanan atas / sejajar informasi dengan background gelap (`bg-zinc-950 border border-zinc-800`) dan tombol aktif berwarna merah (`bg-red-600`).

### Contoh Markup Header:
```html
<div class="flex flex-col md:flex-row items-center gap-8">
    <!-- Logo / Image -->
    <div class="w-48 h-48 rounded-2xl bg-black border border-zinc-800 flex items-center justify-center overflow-hidden flex-shrink-0 shadow-2xl">
        <img src="{{ $logoUrl }}" alt="{{ $name }}" class="w-full h-full object-contain p-4" referrerpolicy="no-referrer">
    </div>

    <!-- Detail Info -->
    <div class="flex-1 text-center md:text-left space-y-4">
        <div class="inline-flex items-center gap-2 px-3 py-1 bg-red-600/10 border border-red-500/25 rounded-full text-red-500 text-xs font-semibold uppercase tracking-wider">
            Network Detail
        </div>
        <h1 class="text-4xl md:text-5xl font-extrabold tracking-tight">{{ $name }}</h1>
        <p class="text-zinc-400 text-sm max-w-2xl">
            Deskripsi jaringan penyiaran drama...
        </p>

        <div class="flex flex-wrap items-center justify-center md:justify-start gap-4 pt-2">
            <div class="flex items-center gap-2 bg-zinc-950 border border-zinc-800 px-4 py-2 rounded-xl text-xs text-zinc-400">
                <span class="text-zinc-500 font-semibold uppercase">ID NETWORK:</span>
                <span>{{ $id }}</span>
            </div>
        </div>
    </div>
</div>

<!-- Pemisah Konten (Divider) -->
<hr class="border-zinc-800 my-10">
```

---

## 3. Komponen Pencarian (Search Bar)
Search bar diletakkan di bawah divider dan rata kiri pada resolusi medium (`md:mx-0`).

```html
<div class="mb-8">
    <div class="relative max-w-md mx-auto md:mx-0">
        <!-- Ikon Kaca Pembesar (Search) -->
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <svg class="h-5 w-5 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
        </div>
        <input 
            type="text" 
            wire:model.live.debounce.500ms="search" 
            placeholder="Cari drama..." 
            class="block w-full pl-10 pr-10 py-2.5 bg-zinc-950/60 border border-zinc-800 rounded-xl text-zinc-200 placeholder-zinc-500 focus:outline-none focus:border-red-600/50 focus:ring-1 focus:ring-red-600/50 transition-colors text-sm"
        >
        <!-- Tombol Hapus Pencarian (jika input terisi) -->
        @if(!empty($search))
            <button 
                wire:click="$set('search', '')" 
                class="absolute inset-y-0 right-0 pr-3 flex items-center text-zinc-500 hover:text-zinc-300 transition-colors"
            >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        @endif
    </div>
</div>
```

---

## 4. Grid & Desain Card Drama (Drama Card Grid)
Grid menggunakan layout kolom responsif (`grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3 md:gap-6`). Pada mode mobile, jarak antar card lebih rapat (`gap-3` / 12px) agar tampilan lebih padat, lalu kembali ke `gap-6` (24px) mulai dari breakpoint `md`.

Setiap card drama harus memiliki visualisasi yang konsisten:
- **Card Wrapper**: Sudut melengkung `rounded-2xl`, memiliki padding internal `p-3`, border tipis (`border-zinc-800`), hover border merah (`hover:border-red-600/50`), hover background lebih terang (`hover:bg-zinc-900/80`), efek transisi transisi-all, dan perbesaran card saat hover (`hover:scale-105`).
- **Cover Container**: Berukuran rasio poster bioskop (`aspect-[3/4]`), sudut melengkung `rounded-xl`, bergaris batas abu-abu (`border border-zinc-800`), dan memiliki efek mengecil saat di-hover (`group-hover:scale-95 transition-transform duration-300`) untuk memberikan nuansa premium dinamis.
- **Badge Episode**: Ditampilkan di pojok kanan bawah cover dengan format singkat: `"XX Eps"`. Berwarna merah (`text-red-500`) dengan font tebal (`font-bold`) berlatar belakang hitam transparan (`bg-black/80`).
- **Cover Image**: Pastikan untuk selalu menyertakan `referrerpolicy="no-referrer"` agar CDN gambar eksternal tidak memblokir muatan gambar.

### Contoh Markup Grid & Card:
```html
<div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3 md:gap-6">
    @foreach($dramas as $drama)
        <a href="{{ $detailUrl }}" 
           class="group bg-zinc-900/40 border border-zinc-800 hover:border-red-600/50 hover:bg-zinc-900/80 rounded-2xl p-3 flex flex-col justify-between gap-3 transition-all duration-300 hover:scale-105 shadow-lg">
            
            <!-- Cover Container -->
            <div class="w-full aspect-[3/4] rounded-xl bg-black border border-zinc-800 flex items-center justify-center overflow-hidden relative group-hover:scale-95 transition-transform duration-300">
                @if(!empty($drama['cover']))
                    <img src="{{ $drama['cover'] }}" alt="{{ $drama['name'] }}" class="w-full h-full object-cover" referrerpolicy="no-referrer">
                @else
                    <div class="text-zinc-700 flex flex-col items-center gap-1.5">
                        <svg class="w-10 h-10 opacity-40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 100-6 3 3 0 000 6z"/>
                        </svg>
                        <span class="text-[9px] uppercase tracking-widest font-bold">No Cover</span>
                    </div>
                @endif
                
                @if(!empty($drama['episodes']))
                    <span class="absolute bottom-2 right-2 px-2 py-0.5 bg-black/80 border border-zinc-800 text-[10px] font-bold text-red-500 rounded-md tracking-wider">
                        {{ $drama['episodes'] }} Eps
                    </span>
                @endif
            </div>

            <!-- Details -->
            <div class="text-left w-full space-y-1">
                <h3 class="text-sm font-bold text-zinc-200 group-hover:text-white line-clamp-2 transition-colors duration-200" title="{{ $drama['name'] }}">
                    {{ $drama['name'] }}
                </h3>
                @if(!empty($drama['intro']))
                    <p class="text-[11px] text-zinc-500 line-clamp-2 font-light">
                        {{ $drama['intro'] }}
                    </p>
                @endif
            </div>
        </a>
    @endforeach
</div>
```

---

## 5. Tombol Memuat Lebih Banyak (Load More Button)
Tombol Load More wajib konsisten menggunakan warna merah utama (`bg-red-600 hover:bg-red-700`), interaksi saat ditekan (`active:scale-95`), teks bahasa Inggris `"Load More"`, serta indikator animasi loading yang halus agar pengguna mengetahui proses pengambilan data sedang berjalan.

```html
<div class="mt-12 text-center">
    <button wire:click="loadMore" wire:loading.attr="disabled" class="px-8 py-3 bg-red-600 hover:bg-red-700 active:scale-95 text-white font-bold rounded-xl transition duration-150 inline-flex items-center gap-2 text-sm">
        <span wire:loading.remove wire:target="loadMore">Load More</span>
        <span wire:loading wire:target="loadMore" class="inline-block animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></span>
        <span wire:loading wire:target="loadMore">Loading...</span>
    </button>
</div>
```
*(Catatan: Pastikan `wire:target` menargetkan nama fungsi controller/livewire yang sesuai, misalnya `loadDramas` atau `loadMore`)*.

---

## 6. Halaman Detail Drama (Detail Page Layout)
Untuk halaman ketika pengguna mengklik drama dan ingin menonton (misalnya `DracinDetail` atau `BstationDetail`), gunakan tata letak (layout) dua kolom yang responsif. Posisi pemutar video (player) dan daftar episode harus mematuhi struktur berikut:

- **Kolom Kiri (Lebar Fleksibel):** Berisi **Video Player** di posisi teratas, dan **Detail Meta Card** di bawahnya.
- **Kolom Kanan (Lebar Tetap / Sidebar):** Berisi **Daftar Episode** yang dibuat *sticky* dan dapat di-scroll secara independen.

### Struktur Pembungkus Halaman Detail
```html
<div class="min-h-screen bg-[#050505] text-white pt-28 pb-16 px-4 md:px-8 max-w-[1600px] mx-auto">
    <!-- Two Column Layout -->
    <div class="flex flex-col lg:flex-row gap-6 items-start">
        
        <!-- Kolom Kiri: Player & Detail Info -->
        <div class="flex-1 w-full min-w-0">
            
            <!-- 1. Video Player Container -->
            <div x-show="isPlaying" class="mb-6 w-full bg-black rounded-3xl overflow-hidden border border-zinc-800 shadow-2xl relative">
                <!-- Video Element/Iframe diletakkan di sini (Gunakan aspect-video atau aspect-[9/16] untuk portrait) -->
                
                <!-- Controls bar (Kualitas, Next Episode) -->
                <div class="flex flex-wrap items-center justify-between gap-4 bg-zinc-900/60 p-4 border-t border-zinc-800">
                    <!-- Tombol Kualitas / Subtitle / dll -->
                </div>
            </div>

            <!-- 2. Detail Meta Card -->
            <div class="bg-zinc-900/40 border border-zinc-800 rounded-3xl p-6 md:p-8 backdrop-blur-md">
                <div class="flex flex-col md:flex-row gap-8">
                    <!-- Cover Image (aspect-[3/4] w-full md:w-48) -->
                    <!-- Detail Informasi (Judul, Sinopsis, Badge) -->
                </div>
            </div>

        </div>

        <!-- Kolom Kanan: Sidebar Daftar Episode -->
        <div class="w-full lg:w-[400px] lg:shrink-0 lg:sticky lg:top-28">
            <div class="bg-[#0e0e0f] border border-zinc-800/80 rounded-3xl p-5 backdrop-blur-md max-h-[calc(100vh-10rem)] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-zinc-800 pb-3 mb-2">
                    <h2 class="text-sm font-bold tracking-wider text-zinc-300 uppercase">Pilih Episode</h2>
                    <span class="text-[10px] text-zinc-500 font-mono bg-zinc-900 px-2 py-0.5 rounded border border-zinc-800/50">Total Ep</span>
                </div>
                
                <!-- Grid Tombol Episode -->
                <div class="grid grid-cols-4 sm:grid-cols-6 lg:grid-cols-4 gap-2">
                    <!-- Loop Episode -->
                    <button class="p-2.5 rounded-xl border font-bold text-center text-xs transition-all duration-200 flex flex-col justify-center items-center h-14 relative overflow-hidden group bg-zinc-900/60 hover:bg-zinc-900 text-zinc-300 border-zinc-800/60 hover:border-zinc-700">
                        <!-- Teks Episode -->
                    </button>
                    <!-- /Loop Episode -->
                </div>
            </div>
        </div>

    </div>
</div>
```

### Panduan Interaksi Tombol Episode (Active State)
Ketika sebuah episode sedang diputar, tombol episode yang bersangkutan harus menyala (active state) menggunakan kelas Tailwind berikut agar konsisten:
`bg-red-600 text-white border-red-600 shadow-md shadow-red-600/20`
