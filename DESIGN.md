---
name: KAZEVIEW Website — Cinematic Portfolio and Event Gallery
version: 2.0.0
status: implementation-ready
pages:
  - '/'
  - '/preview'
reference_viewports:
  home: 1536x1024
  preview: 1536x1024
design_direction: Cinematic Editorial Motorsport
theme: dark
language: en

colors:
  background: '#090909'
  background-deep: '#050506'
  surface: '#0D0D0F'
  surface-raised: '#131315'
  text: '#F3F2F4'
  text-strong: '#FFFFFF'
  text-muted: '#A6A4AA'
  text-subtle: '#747278'
  border: '#27272A'
  border-strong: '#3A3A3D'
  accent: '#EC1576'
  accent-hover: '#FF2A8B'
  accent-soft: 'rgba(236, 21, 118, 0.12)'
  image-overlay: 'rgba(5, 5, 6, 0.92)'

typography:
  display: 'Inter Tight, Arial Narrow, Arial, sans-serif'
  body: 'Inter, Arial, sans-serif'
  display_weights: [700, 800]
  body_weights: [400, 500, 600, 700]

geometry:
  header_height_home: 50px
  header_height_preview: 60px
  content_padding_desktop: 64px
  nav_padding_desktop: 40px
  home_media_grid_height: 617px
  home_filter_bar_height: 74px
  intro_height_desktop: 241px
  discovery_bar_height: 66px
  gallery_columns_desktop: 4
  gallery_column_gap: 8px
  gallery_row_gap: 16px
  event_card_height_desktop: 552px
  corner_radius: 0px
---

# KAZEVIEW Home & Preview Design Specification

## 1. Cakupan dan tujuan website

Dokumen ini adalah sumber kebenaran untuk dua halaman utama KAZEVIEW:

| Halaman | Route | Tujuan utama |
| --- | --- | --- |
| Home | `/` | Menampilkan kualitas portofolio foto dan film secepat mungkin melalui media grid layar penuh. |
| Preview | `/preview` | Membantu pengunjung mencari event, melihat preview, membeli foto, membayar lewat QRIS, lalu mengunduh hasilnya. |

Prioritas Home:

1. karya terlihat langsung setelah header tanpa intro kosong;
2. pengunjung langsung memahami bahwa KAZEVIEW mengerjakan foto dan video;
3. automotive, motorcycle, portrait/cosplay, dan event tampil dalam satu sistem visual;
4. featured film memiliki play icon dan durasi, tetapi tidak autoplay;
5. filter portofolio mudah dipindai dan grid berikutnya terlihat pada layar pertama.

Prioritas Preview:

1. mencari event berdasarkan nama, lokasi, atau tanggal;
2. membuka galeri event;
3. melihat preview foto atau film yang tersedia;
4. membeli foto seharga `Rp 6.000 / PHOTO`;
5. membayar melalui QRIS lalu mengunduh hasilnya.

Foto harus menjadi elemen dominan pada kedua halaman. Fungsi transaksi hanya dominan secara fungsional di Preview dan tidak boleh membuat situs terlihat seperti toko daring generik. Keseluruhan website harus terasa seperti portofolio motorsport premium yang juga memiliki sistem pembelian foto.

## 2. Aturan sumber kebenaran

- Tampilan desktop Home dan Preview pada viewport `1536 × 1024` adalah sumber kebenaran utama.
- Susunan desktop setiap halaman harus mengikuti ukuran, jarak, urutan, dan hierarki yang ditentukan di dokumen ini.
- Jangan mengganti tema menjadi putih, menambahkan gradient dekoratif, glassmorphism, kartu membulat, atau elemen bergaya SaaS.
- Home tidak menggunakan hero konvensional; featured film merupakan bagian dari media grid.
- Preview tidak boleh mempunyai hero tinggi yang mendorong event gallery keluar dari layar pertama.
- Pada Home, featured media grid, filter bar, dan sebagian baris portfolio kedua harus terlihat pada layar pertama.
- Pada Preview, baris pertama event harus terlihat penuh dan sedikit bagian baris kedua harus terlihat di bagian bawah layar.
- Gunakan logo KAZEVIEW asli. Jangan membuat ulang wordmark dengan font biasa apabila file SVG/PNG logo tersedia.
- Semua teks yang ditandai sebagai **exact copy** harus digunakan persis, termasuk kapitalisasi, tanda baca, dan spasi.

## 3. Karakter visual

Kata kunci visual:

- cinematic;
- editorial;
- motorsport;
- precise;
- premium;
- high contrast;
- image-first;
- compact utility;
- restrained magenta.

Website menggunakan latar hampir hitam, garis pembatas tipis, teks putih bersih, dan satu aksen magenta. Warna foto tetap hidup, tetapi antarmuka tidak menggunakan warna tambahan di luar foto. Seluruh komponen berbentuk tegas dan bersudut lurus. Home terasa lebih imersif dan image-led; Preview terasa lebih terstruktur dan utility-led, tetapi keduanya wajib terlihat sebagai satu website.

## 4. Design tokens

### 4.1 CSS custom properties

```css
:root {
  --color-bg: #090909;
  --color-bg-deep: #050506;
  --color-surface: #0d0d0f;
  --color-surface-raised: #131315;

  --color-text: #f3f2f4;
  --color-text-strong: #ffffff;
  --color-text-muted: #a6a4aa;
  --color-text-subtle: #747278;

  --color-border: #27272a;
  --color-border-strong: #3a3a3d;

  --color-accent: #ec1576;
  --color-accent-hover: #ff2a8b;
  --color-accent-soft: rgba(236, 21, 118, 0.12);

  --font-display: 'Inter Tight', 'Arial Narrow', Arial, sans-serif;
  --font-body: 'Inter', Arial, sans-serif;

  --page-padding: 64px;
  --nav-padding: 40px;
  --header-height-home: 50px;
  --header-height-preview: 60px;
  --gallery-gap-x: 8px;
  --gallery-gap-y: 16px;

  --transition-fast: 160ms ease;
  --transition-media: 420ms cubic-bezier(0.2, 0.7, 0.2, 1);
}
```

### 4.2 Pemakaian warna

| Token | Penggunaan |
| --- | --- |
| `--color-bg` | Latar utama halaman dan area galeri |
| `--color-bg-deep` | Header, discovery bar, dan overlay paling gelap |
| `--color-surface` | Panel informasi tipis dan latar input |
| `--color-text-strong` | Headline dan judul event |
| `--color-text` | Teks utama, navigation, dan CTA |
| `--color-text-muted` | Deskripsi, harga, dan label sekunder |
| `--color-text-subtle` | Metadata berprioritas rendah |
| `--color-border` | Divider global |
| `--color-border-strong` | Border input dan tombol sekunder |
| `--color-accent` | Active state, NEW badge, hover card, dan tanda titik headline |

Magenta tidak boleh mengisi bidang besar. Total luas magenta idealnya kurang dari 3% viewport.

## 5. Tipografi

Muat font berikut jika tersedia:

```css
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Inter+Tight:wght@600;700;800&display=swap');
```

| Peran | Font | Ukuran desktop | Weight | Line-height | Tracking |
| --- | --- | ---: | ---: | ---: | ---: |
| Headline halaman | Inter Tight | 60px | 800 | 0.94 | -0.035em |
| Home featured statement | Inter Tight | 61px | 800 | 0.95 | -0.035em |
| Eyebrow | Inter | 14px | 600 | 1 | 0.085em |
| Deskripsi intro | Inter | 17px | 400 | 1.45 | 0 |
| Nomor langkah | Inter Tight | 27px | 700 | 1 | -0.02em |
| Label langkah | Inter | 12px | 500 | 1.2 | 0.015em |
| Navigation | Inter | 13px | 500 | 1 | 0.01em |
| Filter | Inter | 13px | 500 | 1 | 0.01em |
| Judul event | Inter Tight | 20px | 700 | 1.1 | -0.015em |
| Lokasi | Inter | 14px | 500 | 1.2 | 0.01em |
| Harga | Inter | 15px | 400 | 1.2 | 0 |
| Badge | Inter | 12px | 600 | 1 | 0.025em |
| CTA card | Inter | 13px | 600 | 1 | 0.02em |

Semua judul, navigation, filter, badge, dan CTA menggunakan huruf kapital. Deskripsi intro menggunakan sentence case.

## 6. Ikon

Gunakan ikon outline yang konsisten, misalnya Lucide Icons:

- `Search` — 19px;
- `MapPin` — 16px;
- `QrCode` — 20px;
- `MessageCircle` atau ikon WhatsApp resmi — 20px;
- `Play` — 12px di dalam lingkaran 25px;
- `ArrowRight` — 17px;
- `ChevronDown` — 15px.

Ketebalan stroke ikon: `1.6px`. Ikon tidak memakai shadow. Jangan mencampur ikon outline dan ikon filled, kecuali logo WhatsApp resmi.

## 7. Arsitektur website

Urutan DOM dan visual Home:

```text
Home Page
├── Global header
├── Featured media grid
│   ├── Featured film tile
│   ├── Motorcycle photography tile
│   ├── Portrait photography tile
│   ├── Event photography tile
│   └── Automotive detail tile
├── Portfolio filter bar
└── Portfolio grid
    ├── Film tile
    ├── Portrait tile
    ├── Motorcycle tile
    └── Event tile
```

Urutan DOM dan visual Preview:

```text
Preview Page
├── Global header
├── Intro band
│   ├── Headline block
│   └── Purchase process + payment utilities
├── Discovery bar
│   ├── Search
│   ├── Event filters
│   └── Sort
└── Event gallery
    ├── Event card 01 — active/featured
    ├── Event card 02
    ├── Event card 03
    ├── Event card 04
    └── Event cards berikutnya
```

## 8. Home page specification

### 8.1 Tujuan dan aturan komposisi

Home adalah portofolio, bukan landing page pemasaran. Karya harus terlihat sejak piksel pertama setelah header. Jangan menambahkan blok perkenalan putih, headline yang terpisah dari foto, service cards, testimonial, atau section pembuka dengan ruang kosong.

Komposisi utama terdiri dari:

1. featured film berukuran besar di sisi kiri;
2. empat karya pendukung dalam grid 2 × 2 di sisi kanan;
3. filter bar horizontal;
4. portfolio grid berikutnya yang mulai terlihat pada viewport pertama.

Featured film dan seluruh tile lain adalah link menuju detail karya. Tile video wajib dibedakan menggunakan play icon, label, dan durasi. Jangan mengandalkan hover untuk memberi tahu bahwa sebuah tile adalah video.

### 8.2 Home desktop artboard — 1536 × 1024

Mockup Home menggunakan pembagian vertikal berikut:

| Area | Posisi Y | Tinggi |
| --- | ---: | ---: |
| Immersive header | `0–49px` | 50px |
| Featured media grid | `50–666px` | 617px |
| Portfolio filter bar | `667–740px` | 74px |
| Portfolio grid berikutnya | mulai `741px` | berlanjut di bawah viewport |

Pada viewport tinggi 1024px, sekitar 283px dari portfolio grid berikutnya terlihat. Tidak boleh ada margin vertikal di antara header, featured grid, filter bar, dan grid berikutnya.

Pembagian horizontal featured media grid:

```text
┌──────────────────────────────┬───────────────┬───────────────┐
│                              │ Motorcycle    │ Portrait      │
│ Featured Film                ├───────────────┼───────────────┤
│ 50% viewport width           │ Event         │ Auto Detail   │
└──────────────────────────────┴───────────────┴───────────────┘
```

- Featured film: 50% lebar viewport, seluruh tinggi grid.
- Sisi kanan: 50% lebar viewport.
- Masing-masing right tile: 50% lebar area kanan dan 50% tinggi area kanan.
- Dengan kata lain, setiap tile kanan berukuran kira-kira 25vw × 308px pada viewport acuan.
- Divider antartile: `1px solid #090909`; tidak ada white gutter.
- Tidak ada border radius.

```css
.home-featured-grid {
  height: 617px;
  display: grid;
  grid-template-columns: 1fr 1fr;
  background: var(--color-bg-deep);
}

.home-featured-grid__secondary {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  grid-template-rows: repeat(2, minmax(0, 1fr));
  gap: 1px;
  background: var(--color-bg-deep);
}
```

### 8.3 Home header

Home menggunakan varian header imersif:

- Tinggi: `50px` pada viewport desktop acuan.
- Posisi: sticky, `top: 0`, `z-index: 50`.
- Background: `#050506`.
- Padding horizontal: `30px`.
- Logo KAZEVIEW: sekitar `168 × 22px`.
- Navigation berada di kanan dengan tinggi penuh.

Exact navigation copy dan urutannya untuk seluruh website:

```text
WORK
FILMS
PREVIEW
ABOUT
CONTACT
BOOK A SHOOT
```

Home tidak memberi underline permanen pada salah satu navigation item. Logo berfungsi sebagai link Home. Ketika pengguna berada pada `/preview`, hanya `PREVIEW` yang memiliki underline magenta.

- Link gap Home: `38px`.
- Font navigation: 12px, weight 500.
- Tombol Book a Shoot: `174 × 36px`, border magenta 1px, radius maksimum 3px.
- Jangan menampilkan link bernama `HOME`; logo menggantikannya.

### 8.4 Featured film tile

#### Media

- Posisi: kolom kiri featured media grid.
- Foto/video poster: rolling shot mobil performa putih pada suasana blue hour atau malam.
- `object-fit: cover` dan `object-position: 52% 50%`.
- Gunakan poster image; file video tidak dimuat sebelum pengguna menekan play.
- Tambahkan overlay untuk keterbacaan tanpa menghilangkan detail kendaraan:

```css
background:
  linear-gradient(to top, rgba(5,5,6,0.92) 0%, rgba(5,5,6,0.28) 47%, rgba(5,5,6,0.10) 100%),
  linear-gradient(to right, rgba(5,5,6,0.30), transparent 55%);
```

#### Play and film metadata

Exact copy:

```text
FEATURED FILM
NIGHT RUN — SURABAYA
01:24
```

- Cluster berada di kiri, sekitar `100px` dari tepi kiri dan `267px` dari sisi atas tile.
- Play button: lingkaran `64 × 64px`, border putih `2px`, icon Play putih `18px`.
- Jarak play button ke label: `17px`.
- Label `FEATURED FILM`: 11px, weight 600, tracking 0.08em.
- Judul film: 21px, Inter Tight 600, tracking -0.01em.
- Durasi: 12px, muted white.
- Seluruh cluster merupakan satu clickable target menuju film.

#### Brand statement

Exact copy:

```text
MOTION IN
EVERY FRAME.
Photo + Film / Automotive · Portrait · Event
```

- Posisi kiri: `100px`.
- Posisi bawah: `67px`.
- Headline dua baris wajib.
- Ukuran headline: `61px`.
- Font: Inter Tight, weight 800.
- Line-height: `0.95`.
- Letter spacing: `-0.035em`.
- Titik pada `FRAME.` menggunakan `#EC1576`.
- Supporting line: 17px, regular, `#D2D0D4`, margin-top 17px.
- Jangan memindahkan headline ke luar foto.

#### Scroll indicator

Exact copy: `SCROLL TO EXPLORE`.

- Vertikal di sisi kiri tile.
- Posisi kiri sekitar `28px`; posisi bawah `65px`.
- Gunakan `writing-mode: vertical-rl` dan rotasi 180 derajat.
- Font 10px, weight 500, tracking 0.18em.
- Garis vertikal di bawah label: tinggi `75px`, lebar 1px, gradient putih ke magenta.
- Hanya dekoratif; beri `aria-hidden="true"`.

### 8.5 Secondary featured tiles

Keempat tile kanan menggunakan foto penuh tanpa panel terpisah. Text label berada di kiri bawah dengan padding `14px`.

Overlay label:

```css
linear-gradient(to top, rgba(5,5,6,0.58), transparent 40%)
```

Exact content dan urutan:

| Posisi | Subjek | Label | Year | Object position |
| --- | --- | --- | --- | --- |
| Top-left | Motorcycle rolling shot, motor hitam/hijau | `AUTOMOTIVE` | `2024` | `50% 48%` |
| Top-right | Portrait/cosplay bernuansa magenta | `PORTRAITS` | `2024` | `50% 30%` |
| Bottom-left | Car meet dengan beberapa mobil | `EVENTS` | `2024` | `50% 55%` |
| Bottom-right | Close-up lampu belakang dan badge mobil performa | `AUTOMOTIVE` | `2024` | `56% 52%` |

- Category: 10px, weight 600, tracking 0.075em.
- Year: 10px, muted, margin-top 3px.
- Hover: image scale 1.025; label naik maksimum 4px.
- Jangan menambahkan judul panjang pada featured secondary tiles.
- Tile foto tidak memiliki play icon.
- Bila konten tile diganti menjadi film, play icon dan durasi menjadi wajib.

### 8.6 Home portfolio filter bar

- Posisi langsung setelah featured media grid.
- Tinggi: `74px`.
- Background: `#050506`.
- Border atas/bawah: `1px solid rgba(255,255,255,0.035)`.
- Isi berada di tengah secara horizontal dan vertikal.

Exact copy dan urutannya:

```text
ALL
PHOTOGRAPHY
FILMS
AUTOMOTIVE
PORTRAITS
EVENTS
```

- Display flex, justify-content center.
- Gap: `62px`.
- Font: 14px, weight 500, tracking 0.025em.
- Default text: `#9B999F`.
- Active item: `ALL`, warna putih.
- Active underline: lebar 58px atau selebar label ditambah 22px, tinggi 3px, warna `#EC1576`.
- Underline berada 13px di bawah teks.
- Filter adalah button, bukan anchor kosong.
- Pada filter change, gunakan fade 160ms tanpa layout animation berlebihan.

### 8.7 Home portfolio grid

- Mulai tepat setelah filter bar.
- Padding: `0 24px` pada desktop acuan.
- Grid: empat kolom sama besar.
- Gap: `2px`.
- Background: `#050506`.
- Tinggi tile default: `320px`; pada screenshot hanya 283px bagian atas yang terlihat karena viewport berakhir.
- Gambar `object-fit: cover`.

Baris pertama setelah filter menggunakan urutan:

| Tile | Tipe | Visual | Required UI |
| --- | --- | --- | --- |
| 1 | Film | Mobil balap merah menghadap kamera | Play circle 50px dan durasi `01:37` |
| 2 | Photography | Portrait/cosplay rambut biru | Tidak ada play icon |
| 3 | Photography | Motorcycle rolling shot hitam/hijau | Tidak ada play icon |
| 4 | Photography | Car meet malam dengan crowd | Tidak ada play icon |

- Film tile mempunyai overlay hitam halus, circular play icon di kiri sekitar 38px dari tepi, serta durasi di kiri bawah.
- Durasi exact copy: `01:37`.
- Tile photography bersih tanpa badge permanen pada default state.
- Saat hover, tampilkan category dan project title di kiri bawah dengan fade; jangan menutupi lebih dari 25% foto.
- Klik tile membuka project detail atau lightbox sesuai data content.

```css
.home-portfolio-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 2px;
  padding-inline: 24px;
  background: var(--color-bg-deep);
}

.home-portfolio-card {
  position: relative;
  min-height: 320px;
  overflow: hidden;
  border-radius: 0;
}
```

### 8.8 Home interactions

- Tidak ada media autoplay.
- Featured film play membuka video dalam project page atau modal fullscreen berwarna hitam.
- Escape menutup video modal dan focus kembali ke play button.
- Tile hover: scale image 1.025 selama 420ms; overlay menjadi sedikit lebih gelap.
- Keyboard focus memakai outline magenta 2px di dalam tile agar tidak terpotong oleh overflow.
- Filter portfolio dapat diakses keyboard dan mengubah `aria-pressed`.
- Header tetap sticky; featured grid tidak memakai parallax berat.
- Jangan menggunakan smooth-scroll library yang mengganggu native scrolling.

### 8.9 Home responsive behavior

#### Large desktop — `≥ 1440px`

- Pertahankan layout 50/50 dan right grid 2 × 2.
- Featured grid 617px.
- Headline 61px.
- Portfolio grid empat kolom.

#### Laptop — `1200–1439px`

- Featured grid tinggi `580px`.
- Headline `54px`.
- Text inset featured film `72px`.
- Filter gap `42px`.
- Portfolio tetap empat kolom.

#### Tablet landscape — `900–1199px`

- Featured grid tetap dua kolom dengan rasio `55% 45%`.
- Secondary grid menjadi satu kolom dua tile yang terlihat; dua tile berikutnya pindah ke portfolio grid agar featured area tidak terlalu sempit.
- Headline `46px`.
- Portfolio grid tiga kolom.

#### Tablet portrait — `640–899px`

- Featured film menjadi full width dengan tinggi `70svh`, minimum 560px.
- Secondary tiles menjadi grid dua kolom setelah featured film.
- Masing-masing secondary tile memiliki aspect ratio 4/3.
- Filter horizontal scroll.
- Portfolio grid dua kolom.

#### Mobile — `< 640px`

- Header 56px dengan logo dan menu button.
- Featured film full width, tinggi `calc(100svh - 56px)`.
- Text inset 20px.
- Headline `43px`.
- Play button `56px`.
- Scroll indicator disembunyikan.
- Secondary featured tiles menjadi satu kolom dengan aspect ratio 4/3.
- Filter bar sticky di bawah header bila tidak menutupi menu; horizontal scroll tanpa scrollbar terlihat.
- Portfolio grid satu kolom dengan padding 0 dan tile aspect ratio 4/3.
- Semua label penting video selalu terlihat karena mobile tidak memiliki hover.

## 9. Preview page — desktop artboard 1536 × 1024

### 9.1 Pembagian vertikal

| Area | Posisi Y | Tinggi |
| --- | ---: | ---: |
| Header | `0–59px` | 60px |
| Intro band | `60–300px` | 241px |
| Discovery bar | `301–366px` | 66px |
| Baris kartu pertama | mulai `367px` | 552px |
| Gap antarbari | `920–935px` | 16px |
| Baris kartu kedua | mulai `936px` | berlanjut di bawah viewport |

Pada tinggi viewport 1024px, sekitar 88px bagian atas dari baris kedua harus terlihat.

### 9.2 Grid horizontal

- Header menggunakan padding kiri dan kanan `40px`.
- Intro menggunakan padding kiri dan kanan `72px` agar teks sedikit lebih masuk daripada galeri.
- Discovery bar dan gallery menggunakan padding kiri dan kanan `64px`.
- Lebar efektif gallery: `calc(100% - 128px)`.
- Grid event desktop: `repeat(4, minmax(0, 1fr))`.
- Gap horizontal: `8px`.
- Gap vertikal: `16px`.
- Tidak ada `max-width`; grid melebar mengikuti viewport.

```css
.event-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  column-gap: 8px;
  row-gap: 16px;
  padding-inline: 64px;
}
```

## 10. Global header variants

Home dan Preview menggunakan isi navigation, logo, warna, dan interaction state yang sama. Perbedaan tinggi dipertahankan untuk mengikuti kedua mockup: Home memakai varian imersif 50px, sedangkan Preview memakai varian utility 60px. Pada breakpoint di bawah 900px, keduanya menjadi satu mobile header setinggi 56px.

Shared exact navigation copy:

```text
WORK
FILMS
PREVIEW
ABOUT
CONTACT
BOOK A SHOOT
```

Logo selalu menjadi link menuju `/`. Jangan menambahkan link `HOME` karena akan menduplikasi fungsi logo.

### 10.1 Preview header — ukuran dan posisi

- Tinggi: `60px`.
- Posisi: `sticky; top: 0; z-index: 50`.
- Latar: `#050506` dengan opacity 100%; jangan memakai blur pada tampilan sumber.
- Border bawah: `1px solid rgba(255,255,255,0.04)`.
- Padding: `0 40px`.
- Layout: flex, logo kiri, navigation kanan.

### 10.2 Logo

- Gunakan wordmark KAZEVIEW asli berwarna putih dengan glyph kecil berwarna magenta.
- Bounding box desktop: sekitar `174 × 24px`.
- Posisi vertikal tepat di tengah header.
- Jangan memperbesar logo lebih dari 180px.

### 10.3 Navigation

Exact copy dan urutannya:

```text
WORK
FILMS
PREVIEW
ABOUT
CONTACT
BOOK A SHOOT
```

- Gap antarlink pada Preview: `42px`; Home menggunakan `38px`.
- `PREVIEW` adalah active state hanya pada route `/preview`.
- Pada route `/`, tidak ada underline permanen; featured media sudah menunjukkan konteks halaman Home/Work.
- Active underline: lebar sekitar `65px`, tinggi `2px`, warna `#EC1576`, berada tepat di bawah link sampai menyentuh sisi bawah header.
- Link default: `#F3F2F4`.
- Hover link: putih; tidak menggunakan background pill.

### 10.4 Book a Shoot

- Ukuran: `185 × 38px`.
- Border: `1px solid #EC1576`.
- Radius: `3px` maksimum.
- Background transparan.
- Font: 12px, weight 500.
- Hover: background `rgba(236,21,118,0.12)`.

## 11. Preview intro band

### 11.1 Container

- Tinggi desktop: `241px`.
- Padding: `31px 72px 28px`.
- Display grid dua kolom: `60% 40%`.
- Latar: `#090909`, dapat menggunakan vignette hitam sangat halus di sisi kanan tanpa warna lain.
- Border bawah tidak dibuat di sini karena discovery bar sudah memiliki border atas.

### 11.2 Headline block

Exact copy:

```text
EVENT GALLERIES / 2026
FIND YOUR
MOMENT.
Browse the event. Preview your shot. Make it yours.
```

- Eyebrow berwarna magenta pada baris paling atas.
- Jarak eyebrow ke headline: `14px`.
- Headline dibuat dua baris; jangan memaksanya menjadi satu baris.
- Lebar maksimum headline: `500px`.
- Titik setelah `MOMENT` harus magenta. Implementasikan titik sebagai span tersendiri:

```html
<h1>FIND YOUR<br>MOMENT<span class="accent">.</span></h1>
```

- Jarak headline ke deskripsi: `14px`.
- Deskripsi berwarna `--color-text-muted`.

### 11.3 Process block

Terletak di kolom kanan dan mulai sekitar `70px` dari atas intro.

Exact copy:

```text
01  SELECT EVENT
02  PREVIEW PHOTOS
03  PAY & DOWNLOAD
```

- Tiga langkah disusun horizontal.
- Lebar masing-masing sekitar `160px`.
- Langkah kedua dan ketiga memiliki divider kiri setinggi `48px` dengan warna `#3A3A3D`.
- Nomor berada di atas label.
- Nomor `01` menggunakan putih kuat; `02` dan `03` menggunakan muted gray agar urutannya terasa progresif.

### 11.4 Payment utilities

Terletak `42px` di bawah process block.

#### PAY / QRIS

- Exact copy: `PAY / QRIS`.
- Ukuran: `181 × 47px`.
- Border: `1px solid #3A3A3D`.
- Radius: `3px`.
- Ikon QR di kiri, teks di kanan.
- Padding horizontal: `27px`.
- Hover/focus: border magenta dan background accent-soft.

#### NEED HELP?

- Exact copy: `NEED HELP?`.
- Diletakkan `27px` di kanan tombol QRIS.
- Ikon WhatsApp di kiri.
- Tidak memiliki container atau background.
- Hover: teks dan ikon menjadi magenta.

## 12. Preview discovery bar

### 12.1 Container

- Tinggi: `66px`.
- Padding: `0 64px`.
- Display grid: `362px 1fr auto`.
- Column gap: `76px` antara search dan filters.
- Latar: `#050506`.
- Border atas dan bawah: `1px solid #27272A`.
- Seluruh isi center secara vertikal.

### 12.2 Search

- Lebar: `362px`.
- Tinggi: `42px`.
- Background: `#080809`.
- Border: `1px solid #3A3A3D`.
- Radius: `4px`.
- Padding kiri: `45px`; ikon Search berada `15px` dari kiri.
- Exact placeholder: `Search event, place, or date`.
- Placeholder: 12px, warna muted.
- Focus: border menjadi magenta; jangan menambahkan glow.

### 12.3 Filters

Exact copy dan urutannya:

```text
ALL EVENTS
LATEST
MOTORCYCLE
AUTOMOTIVE
WITH FILM
```

- Display flex.
- Gap: `43–48px`.
- Tinggi clickable area minimal `42px`.
- Active item: `ALL EVENTS`.
- Active underline: tinggi `2px`, lebar mengikuti teks, warna magenta, berada `11px` di bawah baseline label.
- Tidak ada pill background.

### 12.4 Sort

- Exact copy: `NEWEST FIRST`.
- Chevron-down di kanan dengan gap `9px`.
- Font 12px, muted.
- Align ke kanan.

## 13. Preview event gallery

### 13.1 Grid

- Mulai langsung pada `y = 367px`; tidak ada margin atas setelah discovery bar.
- Padding kiri dan kanan `64px`.
- Empat kolom dengan gap `8px`.
- Card height `552px`.
- Row gap `16px`.
- Seluruh card berbentuk kotak tanpa border radius.

### 13.2 Foto cover

- Foto mengisi seluruh card dengan `object-fit: cover`.
- Gunakan foto portrait/vertical minimal 1200 × 1800px.
- Hindari crop yang memotong helm atau roda depan.
- Color grade: contrast sedang-tinggi, black tetap pekat, warna motor tetap natural, tidak terlalu HDR.
- Gunakan sumber berikut untuk baris pertama:

| Card | Subjek | Object position |
| --- | --- | --- |
| 1 | Rider dengan sportbike hijau di depan Tetra Cafe | `50% 42%` |
| 2 | Rider dengan sportbike turquoise | `50% 46%` |
| 3 | Rolling shot motor dengan pepohonan motion blur | `54% 50%` |
| 4 | Sportbike merah dengan rombongan di belakang | `50% 42%` |

### 13.3 Overlay

Setiap card memiliki gradient hitam hanya untuk keterbacaan teks:

```css
background:
  linear-gradient(
    to bottom,
    rgba(5, 5, 6, 0) 48%,
    rgba(5, 5, 6, 0.18) 61%,
    rgba(5, 5, 6, 0.78) 78%,
    rgba(5, 5, 6, 0.98) 100%
  );
```

Jangan menggunakan gradient berwarna magenta pada foto.

### 13.4 Card content

- Posisi: absolute di bawah card.
- Padding kiri dan kanan: `18px`.
- Card biasa: padding bawah `20px`.
- Card active dengan CTA bar: content berhenti `52px` di atas bagian bawah.
- Urutan informasi:

```text
[TYPE BADGE]
[EVENT TITLE]
[MAP PIN] [LOCATION]
[PRICE]
[OPTIONAL CTA]
```

- Gap badge ke judul: `15px`.
- Gap judul ke lokasi: `12px`.
- Gap lokasi ke harga: `14px`.

### 13.5 Type badges

#### Photography

- Exact copy: `PHOTOGRAPHY`.
- Background: `rgba(15,15,17,0.82)`.
- Teks putih.
- Padding: `5px 8px`.
- Radius: `2px` maksimum.

#### Photo + Film

- Exact copy: `PHOTO + FILM`.
- Small circular play icon berada di kiri badge, bukan di dalam bidang badge yang besar.
- Lingkaran: 25 × 25px, border putih 1.5px.
- Gap lingkaran ke teks: `9px`.
- Tidak boleh autoplay di halaman event list.

### 13.6 NEW badge

- Hanya card pertama.
- Exact copy: `NEW`.
- Posisi: top `14px`, left `14px`.
- Background: `#EC1576`.
- Teks putih, 12px, weight 600.
- Padding: `6px 9px`.
- Radius: `2px`.

### 13.7 Active/hover card

Card pertama pada mockup menunjukkan state active/hover dan harus tampak demikian pada screenshot implementasi:

- Border: `1.5px solid #EC1576`.
- Garis magenta tetap terlihat di semua sisi.
- CTA bar setinggi `43px` berada di bawah card.
- CTA exact copy: `VIEW GALLERY` diikuti ikon ArrowRight.
- CTA center secara horizontal.
- Border atas CTA: `1px solid rgba(236,21,118,0.65)`.
- Teks CTA magenta.
- Foto tidak diberi magenta overlay.

Pada interaksi nyata:

```css
.event-card:hover .event-card__media,
.event-card:focus-within .event-card__media {
  transform: scale(1.025);
}
```

- Durasi transform foto: `420ms`.
- Border berubah dalam `160ms`.
- CTA muncul dengan opacity dan translateY maksimum 6px; jangan memakai bounce.

## 14. Preview exact event content

Gunakan data berikut persis untuk empat card pertama:

```yaml
- id: ysquad-2026-07-19
  title: 'YSQUAD — 19 JUL 2026'
  location: 'TETRA CAFE'
  price: 'Rp 6.000 / PHOTO'
  type: 'PHOTO + FILM'
  is_new: true
  is_featured: true

- id: nyoride-suncity
  title: 'NYORIDE SUNCITY'
  location: 'SUN CITY'
  price: 'Rp 6.000 / PHOTO'
  type: 'PHOTOGRAPHY'
  is_new: false
  is_featured: false

- id: kaliurang-2026-06-14
  title: 'KALIURANG — 14 JUN 2026'
  location: 'KALIURANG'
  price: 'Rp 6.000 / PHOTO'
  type: 'PHOTO + FILM'
  is_new: false
  is_featured: false

- id: suncity-mcd-2026-06-07
  title: 'SUNCITY MCD — 07 JUN'
  location: 'MCD SUN CITY'
  price: 'Rp 6.000 / PHOTO'
  type: 'PHOTOGRAPHY'
  is_new: false
  is_featured: false
```

## 15. Preview states dan interaksi

### 15.1 Navigation

- Active page selalu `PREVIEW`.
- Hover link hanya mengubah opacity/warna; tidak memakai underline tambahan selain active state.

### 15.2 Search

- Filter terjadi setelah debounce `250ms`.
- Search mencocokkan event title, location, dan formatted date.
- Tampilkan clear icon setelah pengguna mengetik.
- Empty state tetap memakai background hitam dan copy singkat; jangan menambahkan ilustrasi generik.

### 15.3 Event filters

- Filter aktif mendapat underline magenta.
- Perubahan grid menggunakan fade `160ms`, tanpa animasi reorganisasi berlebihan.
- `WITH FILM` hanya menampilkan event bertipe `PHOTO + FILM`.

### 15.4 Sort

- Default: `NEWEST FIRST`.
- Dropdown minimal berisi `NEWEST FIRST` dan `OLDEST FIRST`.
- Menu menggunakan background surface dan border tipis; radius maksimum 3px.

### 15.5 QRIS

- Klik membuka modal pembayaran.
- Modal berisi QRIS, instruksi singkat, nominal atau langkah konfirmasi, serta WhatsApp help.
- Modal tidak boleh otomatis muncul saat page load.

### 15.6 Video

- Jangan autoplay video pada event list.
- Klik play membuka film preview atau event page.
- Gunakan thumbnail statis WebP/AVIF untuk menjaga performa.
- Play icon dan label `PHOTO + FILM` harus selalu terlihat tanpa hover.

## 16. Preview responsive behavior

Desktop 1536 × 1024 tetap menjadi prioritas utama. Responsive tidak boleh mengubah karakter visual.

### 16.1 Large desktop — `≥ 1440px`

- Gunakan seluruh nilai desktop tanpa perubahan.
- Empat kolom.
- Headline 60px.
- Page padding 64px.

### 16.2 Laptop — `1200–1439px`

- Page padding: 40px.
- Intro padding horizontal: 48px.
- Empat kolom tetap dipertahankan jika lebar card minimal 270px.
- Headline: 54px.
- Gap filter boleh dikurangi menjadi 28px.
- Navigation gap: 28px.

### 16.3 Tablet landscape — `900–1199px`

- Gallery menjadi tiga kolom.
- Intro grid: `52% 48%`.
- Process steps tetap horizontal tetapi label boleh menjadi dua baris.
- Discovery bar membungkus menjadi dua baris: search + sort di atas, filter di bawah.
- Tinggi discovery menjadi 112px.

### 16.4 Tablet portrait — `640–899px`

- Gallery dua kolom.
- Intro menjadi satu kolom.
- Process block diletakkan di bawah deskripsi.
- Filter menjadi horizontal scroll tanpa scrollbar terlihat.
- Header menampilkan logo dan menu button; navigation desktop disembunyikan.

### 16.5 Mobile — `< 640px`

- Padding horizontal: 16px.
- Header: 56px.
- Headline: 43px, line-height 0.96.
- Intro tidak memiliki fixed height.
- Process tetap tiga kolom sempit atau horizontal scroll; jangan mengubah menjadi kartu-kartu membulat.
- QRIS dan Need Help berada pada satu baris jika cukup, jika tidak ditumpuk.
- Search full width.
- Filter horizontal scroll.
- Gallery satu kolom.
- Card tinggi: `min(560px, 135vw)` dengan minimum 470px.
- Card CTA selalu terlihat karena tidak ada hover yang andal di touch device.

## 17. Shared accessibility

- Gunakan elemen `<nav>`, `<main>`, `<section>`, dan `<article>` yang semantik.
- Setiap Home portfolio tile berupa link yang memiliki accessible name berisi jenis media, judul karya, dan kategori.
- Setiap Preview event card berupa link yang memiliki accessible name berisi nama event dan lokasi.
- Setiap image memiliki alt text spesifik, bukan `event image`.
- Setiap play button menyebut judul film dan durasinya; contoh: `Play Night Run — Surabaya, duration 1 minute 24 seconds`.
- Label visual berulang yang sudah tercakup accessible name dapat diberi `aria-hidden="true"` agar screen reader tidak membacanya dua kali.
- Kontras teks utama minimal 4.5:1.
- Focus ring: `2px solid #EC1576` dengan offset 3px.
- Jangan menghilangkan outline tanpa pengganti.
- Semua clickable target minimal 44 × 44px.
- Hormati `prefers-reduced-motion`; matikan scale dan translate, tetapi pertahankan perubahan border/warna.

## 18. Shared assets dan performa

- Logo: SVG jika tersedia.
- Home featured film dan portfolio poster: AVIF utama, WebP fallback.
- Preview event cover: AVIF utama, WebP fallback.
- Ukuran cover desktop yang disarankan: 800–1200px lebar.
- Gunakan `srcset` untuk 480, 768, dan 1200px.
- Home featured film poster adalah kandidat LCP dan menggunakan `fetchpriority="high"`.
- Home secondary tiles dan Preview card pertama menggunakan priority normal atau high hanya bila hasil pengukuran membuktikan perlu; jangan memberi high priority pada semua gambar.
- Baris kedua dan seterusnya menggunakan lazy loading.
- Jangan memuat file video sebelum pengguna menekan play.
- Target LCP di koneksi mobile: di bawah 2.5 detik.
- Gunakan overlay CSS, bukan mengedit foto secara permanen.

## 19. Larangan desain

Jangan menambahkan:

- background putih;
- hero marketing berbasis teks yang terpisah dari karya;
- intro kosong sebelum media grid pada Home;
- intro tinggi yang mendorong event gallery pada Preview;
- testimonial;
- service cards;
- statistik pemasaran;
- gradient ungu/biru;
- glow neon;
- glassmorphism;
- border radius besar;
- shadow lembut ala dashboard;
- icon berwarna-warni;
- carousel otomatis;
- autoplay video;
- paragraf panjang;
- lorem ipsum;
- floating WhatsApp button besar yang menutupi foto;
- badge pada setiap sudut card;
- harga dalam tombol berwarna magenta besar.

## 20. Blueprint CSS layout

Blueprint berikut menunjukkan geometri wajib. Detail framework dapat menyesuaikan, tetapi nilai desktop jangan diubah tanpa alasan kuat.

```css
body {
  margin: 0;
  color: var(--color-text);
  background: var(--color-bg);
  font-family: var(--font-body);
}

.site-header {
  position: sticky;
  inset-block-start: 0;
  z-index: 50;
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: var(--color-bg-deep);
  border-bottom: 1px solid rgba(255, 255, 255, 0.04);
}

.site-header--home {
  height: 50px;
  padding-inline: 30px;
}

.site-header--preview {
  height: 60px;
  padding-inline: 40px;
}

.home-featured-grid {
  height: 617px;
  display: grid;
  grid-template-columns: 1fr 1fr;
  background: var(--color-bg-deep);
}

.home-featured-grid__secondary {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  grid-template-rows: repeat(2, minmax(0, 1fr));
  gap: 1px;
}

.home-filter-bar {
  height: 74px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--color-bg-deep);
  border-block: 1px solid rgba(255, 255, 255, 0.035);
}

.home-portfolio-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 2px;
  padding-inline: 24px;
}

.preview-intro {
  box-sizing: border-box;
  min-height: 241px;
  padding: 31px 72px 28px;
  display: grid;
  grid-template-columns: 60% 40%;
  background: var(--color-bg);
}

.preview-title {
  margin: 14px 0 0;
  font-family: var(--font-display);
  font-size: 60px;
  font-weight: 800;
  line-height: 0.94;
  letter-spacing: -0.035em;
}

.discovery-bar {
  box-sizing: border-box;
  height: 66px;
  padding-inline: 64px;
  display: grid;
  grid-template-columns: 362px 1fr auto;
  align-items: center;
  column-gap: 76px;
  background: var(--color-bg-deep);
  border-block: 1px solid var(--color-border);
}

.event-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  column-gap: 8px;
  row-gap: 16px;
  padding-inline: 64px;
}

.event-card {
  position: relative;
  box-sizing: border-box;
  height: 552px;
  overflow: hidden;
  color: var(--color-text);
  background: var(--color-surface);
  border: 1px solid transparent;
  border-radius: 0;
}

.event-card.is-active,
.event-card:hover,
.event-card:focus-within {
  border-color: var(--color-accent);
}

.event-card__media {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform var(--transition-media);
}

.event-card__overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(
    to bottom,
    rgba(5, 5, 6, 0) 48%,
    rgba(5, 5, 6, 0.18) 61%,
    rgba(5, 5, 6, 0.78) 78%,
    rgba(5, 5, 6, 0.98) 100%
  );
}
```

## 21. Acceptance checklist

Implementasi dianggap sesuai hanya jika seluruh poin berikut terpenuhi:

### Home

- [ ] Viewport Home 1536 × 1024 menggunakan header 50px, featured grid 617px, dan filter bar 74px.
- [ ] Tidak ada intro kosong; media dimulai tepat setelah header.
- [ ] Featured film mengambil 50% lebar dan seluruh tinggi featured grid.
- [ ] Empat secondary tiles mengisi area kanan dalam grid 2 × 2.
- [ ] Featured film menampilkan play circle, `FEATURED FILM`, `NIGHT RUN — SURABAYA`, dan `01:24`.
- [ ] Headline `MOTION IN / EVERY FRAME.` berada di atas poster dan titiknya magenta.
- [ ] Supporting line berbunyi `Photo + Film / Automotive · Portrait · Event`.
- [ ] Secondary tiles menampilkan category dan year sesuai urutan yang ditentukan.
- [ ] Filter Home berisi enam item dan `ALL` aktif dengan underline magenta.
- [ ] Portfolio grid kedua mulai pada y=741px dan terlihat di viewport pertama.
- [ ] Tile film pertama menampilkan play icon dan durasi `01:37`.
- [ ] Tidak ada video autoplay.

### Preview

- [ ] Viewport 1536 × 1024 menghasilkan empat card penuh pada baris pertama.
- [ ] Header tepat 60px dan `PREVIEW` memiliki underline magenta.
- [ ] Intro tidak lebih tinggi dari 241px pada desktop.
- [ ] Headline tampil dua baris: `FIND YOUR` dan `MOMENT.`.
- [ ] Titik headline berwarna magenta.
- [ ] Tiga langkah pembelian terlihat di sisi kanan intro.
- [ ] QRIS dan bantuan WhatsApp terlihat tanpa scroll.
- [ ] Search, lima filter, dan sort berada di discovery bar.
- [ ] Gallery mulai langsung setelah discovery bar tanpa margin kosong.
- [ ] Empat event dan copy-nya sama persis dengan bagian Exact event content.
- [ ] Card pertama memiliki NEW badge, border magenta, dan CTA `VIEW GALLERY`.
- [ ] Card pertama dan ketiga memiliki indikator `PHOTO + FILM`.
- [ ] Card kedua dan keempat memiliki badge `PHOTOGRAPHY`.
- [ ] Sedikit bagian baris kedua terlihat pada tinggi 1024px.
- [ ] Tidak ada border radius besar, glow, glassmorphism, atau background putih.
- [ ] Tidak ada video autoplay.
- [ ] Tampilan mobile mempertahankan tipografi, warna, dan karakter editorial yang sama.

### Shared

- [ ] Kedua halaman memakai logo, font, token warna, icon style, dan navigation order yang sama.
- [ ] Navigation Home menyertakan `PREVIEW`, dan route Preview memberi underline magenta hanya pada `PREVIEW`.
- [ ] Tidak ada background putih, rounded SaaS cards, glassmorphism, glow, atau decorative gradient.
- [ ] Seluruh focus state dapat terlihat dan seluruh clickable target minimal 44 × 44px.
- [ ] Foto menggunakan AVIF/WebP dan video tidak dimuat sebelum play.

## 22. Prompt ringkas untuk coding agent

Gunakan prompt ini bersama file ini apabila implementasi dikerjakan oleh AI:

```text
Implement both the KAZEVIEW Home route `/` and Preview route `/preview` by treating DESIGN.md as the only design source of truth. Build the shared tokens, typography, icons, navigation, focus states, and media behavior first. Reproduce both 1536x1024 desktop geometries exactly before adding responsive behavior. Home must begin with the 50px immersive header and 617px image-first featured grid: one half-width featured film, four secondary tiles, a 74px filter bar, and the visible start of the next four-column portfolio row. Preview must use the 60px header, 241px compact intro, 66px discovery bar, four-column event gallery, exact event copy, active first card, and visible start of the second row. Use #EC1576 as the only UI accent, use the existing KAZEVIEW logo and real portfolio photos, keep video click-to-play, and do not introduce rounded SaaS cards, white sections, decorative gradients, autoplay video, or extra marketing sections. Implement every responsive and accessibility rule documented in DESIGN.md.
```
