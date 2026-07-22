# DESIGN DETAIL DRACIN V2

## 1. Ringkasan

**Detail Dracin V2** adalah pola halaman detail sekaligus pemutar video vertikal fullscreen untuk konten short drama. Implementasi referensi saat ini berada pada network **Shortmax** dan dirancang agar dapat digunakan kembali oleh network Dracin lain tanpa mengubah karakter visual maupun pengalaman pengguna.

Pola ini tidak mengubah video vertikal menjadi layout landscape pada desktop. Video tetap memakai komposisi **9:16**, ditempatkan di tengah layar, sementara ruang kosong di kiri dan kanan menggunakan warna hitam sebagai **pillarbox**. Pendekatan ini mengikuti pengalaman aplikasi short drama seperti ShortMax, ReelShort, dan DramaBox.

Referensi implementasi:

- View: `resources/views/filament/streaming/pages/shortmax-detail.blade.php`
- Page/Livewire: `app/Filament/Streaming/Pages/DracinDetail.php`
- HLS manifest proxy: `GET /api/proxy-hls`
- Media segment proxy: `GET /api/proxy-stream`
- Route detail: `/streaming/dracin/{network_slug}/drama/{id}`
- Route kembali network: `/streaming/dracin/{network_slug}`

---

## 2. Sasaran Desain

1. Mempertahankan rasio asli konten vertikal tanpa cropping atau transformasi landscape.
2. Menyamakan struktur pengalaman desktop dan mobile.
3. Menempatkan seluruh kontrol penting di dalam area video.
4. Mengurangi distraksi dari navigasi global ketika pengguna sedang menonton.
5. Memungkinkan perpindahan episode, kualitas, suara, dan favorit tanpa meninggalkan player.
6. Menjadi pola reusable untuk semua network Dracin yang menyediakan video vertikal.
7. Tetap kompatibel dengan Livewire, Alpine.js, Shaka Player, watch history, subscription check, dan sistem favorit yang sudah ada.

---

## 3. Prinsip Visual

### 3.1 Centered Vertical Player

Kontainer utama memenuhi viewport:

```html
<div class="fixed inset-0 z-[100] bg-black text-white overflow-hidden">
```

Stage menempatkan video di tengah:

```html
<div class="w-full h-full flex items-center justify-center relative">
```

Wrapper video mempertahankan rasio 9:16:

```html
<div class="relative h-full aspect-[9/16] max-w-full max-h-screen bg-black overflow-hidden">
```

Elemen video menggunakan:

```html
<video class="absolute inset-0 w-full h-full object-contain bg-black" playsinline></video>
```

Hasil yang diharapkan:

- **Desktop:** video vertikal berada di tengah; kiri dan kanan hitam.
- **Mobile:** video menggunakan lebar layar semaksimal mungkin dan tetap mempertahankan rasio/komposisinya.
- Tidak menggunakan `object-cover` karena dapat memotong bagian video.
- Tidak meregangkan video menjadi landscape.

### 3.2 Warna dan Material

- Latar utama: hitam `#000`.
- Panel/drawer: `#0e0e0f`.
- Accent utama: merah `#E50914` atau `bg-red-600`.
- Overlay memakai hitam transparan dan `backdrop-blur`.
- Border menggunakan putih transparan rendah (`border-white/10` atau `border-white/20`).
- Teks utama putih; metadata sekunder `text-white/50` sampai `text-white/80`.

### 3.3 Hierarki Z-Index

Rekomendasi:

| Lapisan | Z-index |
|---|---:|
| Video | default / 0 |
| Spinner dan indikator play/pause | 20 |
| Header, action panel, bottom info, unmute | 30 |
| Root fullscreen player | 100 |
| Episode drawer | 110 |

---

## 4. Anatomi Antarmuka

## 4.1 Root Fullscreen

Player V2 menutupi keseluruhan viewport dan navigasi global. View menggunakan `fixed inset-0` dengan z-index tinggi.

Navbar global dapat disembunyikan khusus pada halaman ini:

```css
body > .fi-topbar,
body > header,
nav[aria-label="Global"] {
    display: none !important;
}
```

Catatan implementasi:

- Jangan mengubah layout global untuk semua halaman.
- Penyembunyian navbar hanya boleh berasal dari view V2.
- `Filament\Pages\BasePage::render()` harus tetap digunakan agar layout Livewire tidak hilang.
- Untuk memilih view dinamis, override `getView()`, bukan `render()`.

Contoh:

```php
public function getView(): string
{
    return $this->networkSlug === 'shortmax'
        ? 'filament.streaming.pages.shortmax-detail'
        : static::$view;
}
```

---

## 4.2 Header Overlay

Header berada di bagian atas video dan memiliki gradient transparan:

```html
<div class="absolute top-0 left-0 right-0 z-30 p-4
            flex items-start gap-3
            bg-gradient-to-b from-black/70 via-black/30 to-transparent">
```

Struktur:

1. **Kiri:** tombol kembali.
2. **Tengah:** judul drama, satu baris, truncate.
3. **Kanan:** indikator `Eps X/Y` dan pill pembuka daftar episode.

### Tombol Kembali

Tombol kembali harus menuju halaman network asal, bukan landing Dracin umum.

Pola URL:

```php
DracinNetwork::getUrl(
    ['slug' => $networkSlug],
    panel: 'streaming',
)
```

Contoh Shortmax:

```php
DracinNetwork::getUrl(
    ['slug' => 'shortmax'],
    panel: 'streaming',
)
```

Jangan arahkan ke `/streaming/dracin` kecuali memang merupakan tindakan “kembali ke semua network”.

### Judul

- Font bold.
- `truncate`.
- Tidak boleh mendorong area episode keluar viewport.
- Gunakan `flex-1 min-w-0`.

### Informasi Episode

Menampilkan:

```text
Eps 5/57
```

Pill di bawahnya menampilkan ikon daftar dan:

```text
5/57
```

Klik pill membuka drawer episode.

---

## 4.3 Unmute Prompt

Autoplay bersuara biasanya diblokir browser. Alur yang digunakan:

1. Coba `video.play()` dengan suara.
2. Jika ditolak, set `video.muted = true`.
3. Tampilkan prompt **“Ketuk Untuk Bersuara”**.
4. Jalankan ulang `video.play()` dalam mode muted.
5. Saat prompt diklik, set `video.muted = false`.

Prompt wajib berada tepat di tengah area video:

```html
<div x-show="isMutedAutoplay"
     @click.stop="unmute()"
     class="absolute inset-0 flex items-center justify-center z-30 cursor-pointer">
```

Prompt tidak boleh menggunakan `top-*` karena akan membuatnya terlalu dekat dengan header.

Tampilan tombol:

- Capsule merah.
- Icon volume.
- Animasi pulse ringan.
- Klik tidak boleh memicu toggle play/pause pada video (`@click.stop`).

---

## 4.4 Loading dan Indikator Play/Pause

### Loading Spinner

Ditampilkan ketika video mengirim event:

- `waiting`
- sebelum `player.load()`

Disembunyikan ketika:

- `canplay`
- `playing`
- load gagal dan state ditutup secara eksplisit

Posisi: tengah video.

### Indikator Play/Pause

Klik area video menjalankan `togglePlayPause()`.

Indikator icon play/pause:

- Berada di tengah.
- Tidak menerima pointer event.
- Hilang otomatis setelah sekitar 500 ms.
- Terpisah dari prompt unmute.

---

## 4.5 Floating Action Panel

Panel action berada di sisi kanan area video, bukan sisi kanan browser:

```html
<div class="absolute right-3 top-1/2 -translate-y-1/2 z-30">
```

Aksi utama:

1. **Episode**
   - Membuka drawer episode.
2. **Suara/Bisu**
   - Toggle `video.muted`.
   - Label berubah mengikuti state.
3. **Simpan/Favorit**
   - Memanggil action Livewire `toggleFavorite`.
   - Icon/warna berubah jika sudah favorit.

Spesifikasi tombol:

- Ukuran sekitar `48 × 48 px`.
- Bentuk lingkaran.
- Latar `bg-black/40`.
- `backdrop-blur`.
- Border putih transparan.
- Label berukuran kecil di bawah tombol.
- Seluruh panel tetap berada di dalam wrapper video 9:16.

---

## 4.6 Bottom Information Panel

Panel informasi menempel di bawah video dengan gradient:

```html
<div class="absolute bottom-0 left-0 right-0 z-30
            bg-gradient-to-t from-black/95 via-black/70 to-transparent">
```

Urutan konten:

1. Judul drama dan episode aktif.
2. Deskripsi/sinopsis singkat.
3. Progress bar + current time + duration.
4. Pilihan kualitas.
5. Kartu/pintasan episode.

### Judul Episode

Format:

```text
{Judul Drama} - Eps {Nomor}
```

### Deskripsi

- Maksimal dua baris.
- Gunakan `line-clamp-2`.
- Warna putih transparan.
- Tidak boleh menutupi terlalu banyak video.

### Progress Bar

Komponen progress terdiri atas:

- Waktu berjalan di kiri.
- Seek track di tengah.
- Durasi total di kanan.
- Fill merah.
- Thumb dapat muncul saat hover di desktop.

Persentase:

```js
get progressPercent() {
    if (!this.duration) return 0;

    return Math.min(
        100,
        Math.max(0, (this.currentTime / this.duration) * 100),
    );
}
```

Seek:

```js
seek(event) {
    const rect = event.currentTarget.getBoundingClientRect();
    const percent = Math.min(
        1,
        Math.max(0, (event.clientX - rect.left) / rect.width),
    );

    if (this.$refs.video?.duration) {
        this.$refs.video.currentTime =
            percent * this.$refs.video.duration;
    }
}
```

Untuk dukungan mobile penuh, implementasi berikutnya disarankan mendukung `pointerdown`/`pointermove`, bukan hanya `click`.

### Quality Selector

Quality berasal dari payload event `init-player`:

```js
qualityList: [
    {
        label: '720p',
        url: '...',
        isDefault: true,
    },
]
```

Tampilan:

- Kualitas aktif: merah.
- Kualitas lain: gelap transparan.
- Contoh: `480p`, `720p`, `1080p`.

Saat mengganti kualitas:

1. Simpan `currentTime`.
2. Simpan status pause/play.
3. Load URL kualitas baru.
4. Pulihkan waktu.
5. Lanjutkan playback bila sebelumnya sedang berjalan.

### Episode Card

Kartu lebar penuh di bagian bawah:

```text
Episode 5                                      >
```

Klik kartu membuka episode drawer.

### 4.6.1 Auto-Hide Controls dan Subtitle Clearance

Saat video sedang diputar, seluruh UI operasional harus menghilang otomatis agar subtitle upstream tidak tertutup oleh header, action panel, panel kualitas, atau bottom information panel.

Perilaku:
1. Kontrol muncul saat halaman/player pertama kali aktif.
2. Saat video sedang berjalan normal, kontrol menghilang setelah **3 detik** tanpa aktivitas.
3. Pointer bergerak di area player, pointer/touch down, fokus keyboard, dan interaksi tombol harus memunculkan ulang kontrol sekaligus mereset timeout.
4. Kontrol tetap terlihat dan tidak boleh auto-hide saat:
   - video dijeda;
   - player buffering/loading;
   - episode drawer terbuka;
   - prompt **“Ketuk Untuk Bersuara”** aktif;
   - dialog error aktif.
5. Header, floating action panel, dan bottom information panel memakai state yang sama (`controlsVisible`) agar menghilang/terlihat sebagai satu kelompok.
6. Spinner loading, indikator play/pause, drawer episode, prompt unmute, dan error overlay tidak termasuk dalam UI yang di-auto-hide.

State minimum:

```js
controlsVisible: true,
controlsTimer: null,
```

Pola implementasi:

```js
showControls() {
    this.controlsVisible = true;

    clearTimeout(this.controlsTimer);

    if (
        this.isPaused
        || this.isBuffering
        || this.showEpisodes
        || this.isMutedAutoplay
    ) {
        return;
    }

    this.controlsTimer = setTimeout(() => {
        this.controlsVisible = false;
    }, 3000);
}
```

Root player menangani aktivitas pointer dan keyboard:

```html
<div
    @pointermove.throttle.150ms="showControls()"
    @pointerdown="showControls()"
    @focusin="showControls()"
    @keydown.window="showControls()"
>
```

Untuk player berbasis iframe seperti WeAnim/Bstation, overlay kontrol parent tetap menerima pointer saat mode UI bersih sehingga iframe video dan subtitle tetap bebas dari panel UI.

---

## 4.7 Episode Drawer

Drawer muncul dari sisi kanan dengan backdrop gelap.

Struktur:

- Backdrop memenuhi viewport.
- Panel kanan `w-full max-w-md`.
- Header sticky/fixed di dalam drawer.
- Body scrollable.
- Grid episode.
- Animasi slide dari kanan.

Header drawer:

- Judul **Pilih Episode**.
- Jumlah total episode.
- Tombol close.

Opsional:

- Kartu “Terakhir Ditonton”.
- Tombol “Lanjutkan”.

Grid episode:

- Mobile: 4 kolom.
- Layar lebih lebar: 5 kolom.
- Episode aktif: merah.
- Episode belum aktif: gelap.
- Episode sudah ditonton: centang hijau.
- **Tidak menampilkan ikon lock**, meskipun API memiliki field `locked`/`is_lock`.
- Status lock boleh tetap tersedia pada data untuk kebutuhan bisnis mendatang, tetapi tidak divisualisasikan dalam desain V2 saat ini.

Perilaku pemilihan:

```js
pickEpisode(episode) {
    if (episode === this.currentEpisode) {
        this.showEpisodes = false;
        return;
    }

    this.showEpisodes = false;
    this.$wire.playEpisode(episode);
}
```

---

## 5. Responsive Behavior

## 5.1 Desktop

- Root memenuhi viewport.
- Video 9:16 berada di tengah.
- Ruang kiri dan kanan hitam.
- Overlay tetap terikat pada wrapper video.
- Drawer muncul dari sisi kanan browser.
- Lebar drawer maksimal sekitar 448 px (`max-w-md`).

## 5.2 Mobile

- Wrapper video dibatasi `max-w-full`.
- Video memenuhi lebar yang tersedia.
- Semua kontrol tetap berada di area video.
- Header, floating action, dan bottom panel memakai ukuran font/spacing lebih kecil.
- Drawer dapat memenuhi seluruh lebar layar.
- Elemen action tidak boleh bertabrakan dengan safe area perangkat.

Peningkatan yang disarankan:

```css
padding-top: max(1rem, env(safe-area-inset-top));
padding-bottom: max(1rem, env(safe-area-inset-bottom));
```

## 5.3 Orientasi Landscape Mobile

Jika perangkat diputar landscape:

- Video tetap `object-contain`.
- Wrapper boleh menggunakan tinggi viewport.
- Pillarbox/letterbox hitam dapat muncul.
- Kontrol tetap berada dalam area player.
- Jangan memaksa rotate video.

---

## 6. Kontrak Data Normalisasi

Agar desain dapat diterapkan ke network lain, backend harus menormalisasi response API ke struktur berikut.

### Drama

```php
$this->drama = [
    'title' => 'Judul Drama',
    'name' => 'Judul Drama',
    'cover' => 'https://...',
    'intro' => 'Sinopsis singkat...',
    'episodes' => 57,
    'videos' => [
        // ...
    ],
];
```

### Episode

```php
[
    'episode' => 1,
    'video_id' => 'upstream-id',
    'is_lock' => false,
    'duration' => 167,
    'video_urls' => [
        '480' => '/api/proxy-hls?url=...',
        '720' => '/api/proxy-hls?url=...',
        '1080' => '/api/proxy-hls?url=...',
    ],
];
```

Field minimum:

- `episode`
- Satu sumber video yang dapat diputar

Field opsional:

- `video_id`
- `is_lock`
- `duration`
- Beberapa kualitas

Jika API hanya menyediakan satu kualitas, payload tetap dinormalisasi sebagai satu item quality list.

---

## 7. Kontrak Event Livewire ke Alpine

Backend mengirim event bernama `init-player`:

```php
$this->dispatch(
    'init-player',
    videoUrl: $videoUrl,
    qualityList: $qualityList,
    episode: $episode,
    nextEpisode: $nextEpisode,
    subtitles: $subtitles,
);
```

Format quality list:

```php
[
    [
        'label' => '720p',
        'url' => $videoUrl,
        'isDefault' => true,
    ],
    [
        'label' => '1080p',
        'url' => $videoUrl1080,
        'isDefault' => false,
    ],
];
```

Alpine menerima event:

```js
window.addEventListener('init-player', (event) => {
    this.handleInitPlayer(event);
});
```

Untuk kompatibilitas Livewire:

```js
let payload = event.detail;

if (payload && payload[0]) {
    payload = payload[0];
}
```

State yang harus diperbarui:

- `videoUrl`
- `qualityList`
- `currentQualityLabel`
- `currentEpisode`
- `nextEpisode`
- watched episode lokal

---

## 8. State Alpine Minimum

```js
{
    player: null,
    videoUrl: '',
    qualityList: [],
    currentQualityLabel: '',
    currentEpisode: 1,
    nextEpisode: null,

    muted: false,
    isMutedAutoplay: false,
    isPaused: true,
    isBuffering: false,

    currentTime: 0,
    duration: 0,

    showEpisodes: false,
    showCenterIndicator: false,

    drama: {
        title: '',
        intro: '',
        totalEpisodes: 0,
        episodeList: [],
        watchedEpisodes: [],
        lastWatchedEpisode: null,
        isFavorited: false,
    },
}
```

Method minimum:

- `init()`
- `attachVideoEvents()`
- `handleInitPlayer(event)`
- `initPlayer()`
- `attemptPlay(video)`
- `unmute()`
- `toggleMute()`
- `togglePlayPause()`
- `seek(event)`
- `changeQuality(quality)`
- `pickEpisode(episode)`
- `isWatched(episode)`
- `fmtTime(seconds)`

---

## 9. Lifecycle Video

Event native video yang perlu didengarkan:

| Event | Dampak state |
|---|---|
| `timeupdate` | Perbarui `currentTime` |
| `durationchange` | Perbarui `duration` |
| `loadedmetadata` | Pastikan duration tersedia |
| `play` | `isPaused = false` |
| `pause` | `isPaused = true` |
| `waiting` | `isBuffering = true` |
| `canplay` | `isBuffering = false` |
| `playing` | `isBuffering = false` |
| `volumechange` | Sinkronkan `muted` |
| `ended` | Putar `nextEpisode` bila ada |

Saat mengganti episode:

1. Backend memilih episode.
2. Backend membentuk quality list.
3. Backend dispatch `init-player`.
4. Frontend destroy player lama.
5. Frontend membuat/load player baru.
6. Frontend mencoba autoplay.
7. Jika autoplay bersuara ditolak, fallback muted.
8. Watch history diperbarui oleh backend.

---

## 10. Integrasi Shaka Player

Konfigurasi referensi:

```js
this.player = new shaka.Player(video);

this.player.configure({
    streaming: {
        bufferingGoal: 60,
        rebufferingGoal: 2,
        bufferBehind: 30,
        retryParameters: {
            timeout: 30000,
            maxAttempts: 5,
            baseDelay: 500,
            backoffFactor: 2,
        },
    },
    manifest: {
        retryParameters: {
            timeout: 20000,
            maxAttempts: 3,
        },
    },
});
```

Tujuan:

- Buffer lebih panjang untuk mengurangi stutter.
- Resume lebih cepat setelah rebuffer.
- Retry tahan terhadap API/CDN yang tidak stabil.
- Cocok untuk proxy HLS Laravel saat ini.

Catatan:

- API `new shaka.Player(video)` menampilkan warning deprecation pada versi tertentu. Saat upgrade Shaka, migrasikan ke API attach terbaru.
- Jangan membuat beberapa instance player tanpa `destroy()`.
- Semua timer/listener global sebaiknya dibersihkan saat Livewire component dihancurkan pada refactor berikutnya.

---

## 11. HLS dan Proxy

Beberapa CDN upstream tidak menyediakan header CORS. Untuk sumber tersebut:

1. URL manifest dibungkus dengan `/api/proxy-hls`.
2. Proxy mengambil manifest.
3. URI relatif di dalam manifest diubah menjadi URL absolut.
4. Nested playlist kembali diarahkan melalui `/api/proxy-hls`.
5. Segmen media diarahkan melalui `/api/proxy-stream`.
6. Proxy menambahkan header CORS dan meneruskan Range request.

Contoh normalisasi:

```php
$videoUrls[$quality] = url(
    '/api/proxy-hls?url=' . urlencode($upstreamUrl),
);
```

Jangan selalu memaksa proxy untuk semua network. Gunakan aturan berikut:

- Jika CDN mendukung CORS dan playback langsung stabil, gunakan URL langsung.
- Jika manifest/segment diblokir CORS, gunakan proxy.
- Domain upstream harus masuk allowlist proxy.
- Hindari open proxy; validasi scheme dan host.

---

## 12. Watch History dan Auto Next

Identitas history:

```php
[
    'item_id' => $networkSlug . ':' . $dramaId,
    'item_type' => 'dracin',
]
```

Data yang disimpan:

```php
[
    'title' => $dramaTitle,
    'thumbnail' => $cover,
    'last_episode' => (string) $episode,
    'watched_episodes' => $watchedEpisodes,
    'url' => request()->url(),
]
```

Auto-play awal:

1. Jika ada `lastWatchedEpisode`, putar episode tersebut.
2. Jika tidak, ambil episode terkecil dari `videos`.
3. Jika hanya ada jumlah total episode, fallback ke episode 1.

Auto-next:

- Backend mencari item setelah episode aktif dalam array `videos`.
- Frontend mendengarkan event `ended`.
- Jika `nextEpisode` tersedia, panggil:

```js
this.$wire.playEpisode(this.nextEpisode);
```

---

## 13. Favorit dan Subscription

### Favorit

Floating action **Simpan/Favorit** memanggil:

```blade
wire:click="toggleFavorite"
```

State visual:

- Belum favorit: latar hitam transparan, outline heart.
- Favorit: latar merah, filled heart.

### Subscription

Subscription check tetap dilakukan server-side sebelum membentuk URL playback:

```php
if (
    auth()->check()
    && auth()->user()->isSubscriptionExpired()
) {
    // Kirim notification dan hentikan playback.
}
```

Jangan hanya mengandalkan pembatasan frontend.

---

## 14. Pemilihan View per Network

Selama migrasi bertahap, view V2 dapat diaktifkan per network melalui `getView()`:

```php
public function getView(): string
{
    $v2Networks = [
        'shortmax',
        // 'netshort',
        // 'pinedrama',
    ];

    return in_array(
        $this->networkSlug,
        $v2Networks,
        true,
    )
        ? 'filament.streaming.pages.dracin-detail-v2'
        : static::$view;
}
```

Rekomendasi refactor sebelum menerapkan ke banyak network:

1. Ubah `shortmax-detail.blade.php` menjadi view generik:
   - `dracin-detail-v2.blade.php`
2. Jangan hard-code slug pada tombol kembali.
3. Gunakan `$networkSlug`.
4. Normalisasi semua data network di backend/service.
5. Pindahkan Alpine component ke file JavaScript terpisah.
6. Buat policy/capability map per network.

Contoh capability map:

```php
[
    'supports_quality_switch' => true,
    'supports_subtitles' => false,
    'supports_hls' => true,
    'requires_hls_proxy' => true,
    'vertical_content' => true,
]
```

---

## 15. Checklist Implementasi untuk Network Baru

### Backend

- [ ] Tambahkan/mapping network pada daftar platform.
- [ ] Ambil metadata detail.
- [ ] Normalisasi `title`, `cover`, `intro`, `episodes`.
- [ ] Normalisasi daftar episode ke `videos`.
- [ ] Tentukan cara memperoleh URL playback.
- [ ] Bentuk `qualityList`.
- [ ] Tentukan apakah URL harus melalui proxy.
- [ ] Hitung `nextEpisode`.
- [ ] Dispatch event `init-player`.
- [ ] Simpan watch history.
- [ ] Terapkan subscription check.
- [ ] Pastikan tombol kembali menggunakan slug network yang benar.

### Frontend

- [ ] Aktifkan network pada daftar V2.
- [ ] Uji player desktop dengan pillarbox.
- [ ] Uji player mobile portrait.
- [ ] Uji play/pause.
- [ ] Uji auto-hide setelah 3 detik saat video berjalan.
- [ ] Uji kontrol muncul kembali saat pointer/touch/keyboard digunakan.
- [ ] Pastikan kontrol tetap terlihat saat pause, loading, drawer, unmute, atau error.
- [ ] Pastikan subtitle tidak lagi tertutup setelah kontrol menghilang.
- [ ] Uji prompt unmute tepat di tengah.
- [ ] Uji toggle suara.
- [ ] Uji kualitas.
- [ ] Uji seek/progress.
- [ ] Uji episode drawer.
- [ ] Pastikan ikon lock tidak tampil.
- [ ] Uji episode aktif dan sudah ditonton.
- [ ] Uji favorit.
- [ ] Uji auto-next.
- [ ] Uji tombol kembali ke halaman network.
- [ ] Uji expired subscription.

### Network/CDN

- [ ] Cek CORS manifest.
- [ ] Cek CORS segment.
- [ ] Cek dukungan Range.
- [ ] Cek masa berlaku signed URL.
- [ ] Cek Referer/Origin yang diperlukan.
- [ ] Tambahkan host ke allowlist proxy jika diperlukan.
- [ ] Uji buffering pada kualitas tertinggi.
- [ ] Uji kegagalan upstream dan retry.

---

## 16. Kriteria Penerimaan

Sebuah network dianggap berhasil memakai Detail Dracin V2 jika:

1. Halaman membuka player fullscreen tanpa navbar global.
2. Video vertikal tetap 9:16 dan berada di tengah pada desktop.
3. Video memenuhi area mobile tanpa crop.
4. Header menampilkan back, judul, dan episode aktif.
5. Tombol kembali menuju halaman network yang tepat.
6. Prompt unmute muncul di tengah saat autoplay bersuara diblokir.
7. Floating actions tetap melekat pada area video.
8. Progress dan durasi tersinkron dengan video.
9. Pilihan kualitas dapat mempertahankan waktu playback.
10. Drawer menampilkan semua episode tanpa icon lock.
11. Pemilihan episode mengganti video tanpa full page reload.
12. Episode selesai dapat melanjutkan ke episode berikutnya.
13. Watch history dan favorit berfungsi.
14. Header, floating actions, dan bottom panel menghilang setelah 3 detik tanpa aktivitas ketika video berjalan.
15. Kontrol muncul kembali saat pointer bergerak, layar disentuh, atau keyboard digunakan.
16. Kontrol tidak menghilang ketika video pause, loading, drawer terbuka, prompt unmute aktif, atau error tampil.
17. Subtitle dapat dibaca tanpa tertutup panel UI saat controls berada dalam keadaan tersembunyi.
18. Tidak ada Blade parse error, missing layout, atau error console fatal.
19. View berhasil dikompilasi dengan:

```bash
php artisan view:clear
php artisan view:cache
```

---

## 17. Risiko dan Catatan Teknis

### 17.1 Livewire Layout

Jangan override `render()` hanya untuk memilih view. Hal tersebut dapat melewati:

```php
->layout($this->getLayout(), ...)
```

dan memicu:

```text
MissingLayoutException:
Livewire page component layout view not found
```

Gunakan `getView()`.

### 17.2 Blade JSON

Hindari `@json([...])` multiline jika ExtendedBlade compiler pada environment memunculkan parse error. Bentuk array dalam `@php`, lalu gunakan `json_encode()` dengan flag HEX yang aman untuk `<script>`.

### 17.3 Autoplay

Autoplay dengan suara tidak dapat dijamin karena kebijakan browser. UI harus selalu menyediakan fallback muted + tombol unmute.

### 17.4 Proxy Throughput

Proxy media melalui PHP menambah beban server. Untuk trafik tinggi, pertimbangkan:

- Reverse proxy Nginx.
- CDN/internal caching.
- Segment cache.
- Pemisahan endpoint media dari worker aplikasi.

### 17.5 Signed URL

Jika URL kualitas memiliki expiry:

- Jangan simpan URL playback jangka panjang di database.
- Refresh detail/episode URL saat membuka halaman.
- Pertimbangkan endpoint refresh jika episode berdurasi panjang.

---

## 18. Arah Refactor Selanjutnya

Struktur target:

```text
app/
  Services/
    Dracin/
      Contracts/
        DracinProvider.php
      DTO/
        DramaDetailData.php
        EpisodeData.php
        StreamSourceData.php
      Providers/
        ShortmaxProvider.php
        NetshortProvider.php
        PinedramaProvider.php

resources/
  views/
    filament/
      streaming/
        pages/
          dracin-detail-v2.blade.php

resources/
  js/
    streaming/
      dracin-vertical-player.js
```

Tujuannya:

- Page Livewire tidak lagi berisi banyak branch `if ($networkSlug === ...)`.
- Setiap provider bertanggung jawab atas mapping API.
- View hanya menerima data terstandarisasi.
- Player V2 dapat digunakan lintas network tanpa duplikasi.
- Testing provider dapat dilakukan tanpa render UI.

---

## 19. Ringkasan Implementasi Referensi Shortmax

Fitur yang sudah diwujudkan pada referensi Shortmax:

- Centered 9:16 vertical player.
- Pillarbox hitam pada desktop.
- Fullscreen overlay.
- Header back/title/episode.
- Tombol kembali ke network Shortmax.
- Prompt unmute di tengah.
- Floating action Episode, Suara, dan Favorit.
- Bottom title, intro, progress, quality, dan episode card.
- Drawer episode.
- Indikator episode aktif dan sudah ditonton.
- Tidak menampilkan icon lock.
- Multi-quality 480p, 720p, 1080p.
- Auto-next.
- Watch history.
- Favorite.
- Subscription check.
- HLS CORS proxy.
- Retry dan buffer tuning Shaka Player.

Dokumen ini menjadi acuan utama saat Detail Dracin V2 diterapkan ke network lain.